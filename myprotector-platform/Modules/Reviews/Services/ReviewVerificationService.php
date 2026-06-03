<?php
/**
 * MyProtector Platform - Review Verification Service
 * 
 * Handles review verification (verified purchase, trust levels)
 * 
 * @package MyProtector\Modules\Reviews\Services
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews\Services;

use MyProtector\Modules\Reviews\Models\ReviewModel;

class ReviewVerificationService {
    /**
     * Review model
     * 
     * @var ReviewModel
     */
    protected ReviewModel $reviewModel;

    /**
     * Trust level thresholds
     * 
     * @var array
     */
    protected array $trustThresholds = [
        'verified' => 1,      // 1+ approved review
        'premium' => 5,       // 5+ approved reviews
        'trusted' => 10,      // 10+ approved reviews (manual)
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->reviewModel = new ReviewModel();
    }

    /**
     * Verify purchase for review
     * 
     * @param int $reviewId
     * @param string $orderId
     * @param int $verifiedBy
     * @return bool|\WP_Error
     */
    public function verifyPurchase(int $reviewId, string $orderId, int $verifiedBy = 0): bool|\WP_Error {
        $verifiedBy = $verifiedBy ?: get_current_user_id();

        if (!current_user_can('manage_myprotector') && !current_user_can('edit_posts')) {
            return new \WP_Error('unauthorized', __('You do not have permission to verify purchases.', 'myprotector-platform'));
        }

        $review = $this->reviewModel->getById($reviewId);
        if (!$review) {
            return new \WP_Error('not_found', __('Review not found.', 'myprotector-platform'));
        }

        // Validate order exists (integration with WooCommerce or custom orders)
        if (!$this->validateOrder($orderId, $review['company_id'], $review['user_id'])) {
            return new \WP_Error('invalid_order', __('Order not found or does not match review.', 'myprotector-platform'));
        }

        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'mp_reviews',
            [
                'verified_purchase' => 1,
                'verified_order_id' => sanitize_text_field($orderId),
                'verified_by' => $verifiedBy,
                'verified_at' => current_time('mysql'),
                'trust_level' => 'verified',
                'updated_at' => current_time('mysql'),
            ],
            ['review_id' => $reviewId],
            ['%d', '%s', '%d', '%s', '%s', '%s'],
            ['%d']
        );

        if ($result !== false) {
            do_action('mp_review_purchase_verified', $reviewId, $orderId);
        }

        return $result !== false;
    }

    /**
     * Update trust level for user based on reviews
     * 
     * @param int $userId
     * @return string
     */
    public function updateUserTrustLevel(int $userId): string {
        global $wpdb;

        $approvedCount = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews 
                WHERE user_id = %d AND review_status = 'approved'",
                $userId
            )
        );

        // Calculate trust level
        $trustLevel = 'unverified';
        
        if ($approvedCount >= $this->trustThresholds['trusted']) {
            $trustLevel = 'trusted';
        } elseif ($approvedCount >= $this->trustThresholds['premium']) {
            $trustLevel = 'premium';
        } elseif ($approvedCount >= $this->trustThresholds['verified']) {
            $trustLevel = 'verified';
        }

        // Update user's reviews to new trust level
        $wpdb->update(
            $wpdb->prefix . 'mp_reviews',
            ['trust_level' => $trustLevel, 'updated_at' => current_time('mysql')],
            ['user_id' => $userId, 'review_status' => 'approved'],
            ['%s', '%s'],
            ['%d', '%s']
        );

        // Update or create user trust record
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT trust_id, trust_level FROM {$wpdb->prefix}mp_user_trust_levels WHERE user_id = %d",
                $userId
            )
        );

        if ($existing) {
            $wpdb->update(
                $wpdb->prefix . 'mp_user_trust_levels',
                [
                    'trust_level' => $trustLevel,
                    'verified_at' => $trustLevel !== 'unverified' ? current_time('mysql') : null,
                    'updated_at' => current_time('mysql'),
                ],
                ['user_id' => $userId],
                ['%s', '%s', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'mp_user_trust_levels',
                [
                    'user_id' => $userId,
                    'trust_level' => $trustLevel,
                    'verified_at' => $trustLevel !== 'unverified' ? current_time('mysql') : null,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                ],
                ['%d', '%s', '%s', '%s', '%s']
            );
        }

        return $trustLevel;
    }

    /**
     * Manually set trust level (admin only)
     * 
     * @param int $userId
     * @param string $trustLevel
     * @param int $adminId
     * @return bool|\WP_Error
     */
    public function setTrustLevel(int $userId, string $trustLevel, int $adminId = 0): bool|\WP_Error {
        $adminId = $adminId ?: get_current_user_id();

        if (!current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You do not have permission to set trust levels.', 'myprotector-platform'));
        }

        $validLevels = ['unverified', 'verified', 'premium', 'trusted'];
        if (!in_array($trustLevel, $validLevels)) {
            return new \WP_Error('invalid_level', __('Invalid trust level.', 'myprotector-platform'));
        }

        global $wpdb;

        // Update user's reviews
        $wpdb->update(
            $wpdb->prefix . 'mp_reviews',
            ['trust_level' => $trustLevel, 'updated_at' => current_time('mysql')],
            ['user_id' => $userId, 'review_status' => 'approved'],
            ['%s', '%s'],
            ['%d', '%s']
        );

        // Update or create user trust record
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT trust_id FROM {$wpdb->prefix}mp_user_trust_levels WHERE user_id = %d",
                $userId
            )
        );

        $data = [
            'trust_level' => $trustLevel,
            'verification_method' => 'admin_manual',
            'updated_at' => current_time('mysql'),
        ];

        if ($trustLevel !== 'unverified') {
            $data['verified_at'] = current_time('mysql');
        }

        if ($existing) {
            $wpdb->update(
                $wpdb->prefix . 'mp_user_trust_levels',
                $data,
                ['user_id' => $userId],
                array_keys($data),
                ['%d']
            );
        } else {
            $data['user_id'] = $userId;
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($wpdb->prefix . 'mp_user_trust_levels', $data, array_keys($data));
        }

        // Log the action
        $this->logTrustChange($userId, $trustLevel, $adminId);

        do_action('mp_user_trust_level_changed', $userId, $trustLevel, $adminId);

        return true;
    }

    /**
     * Get trust level for user
     * 
     * @param int $userId
     * @return string
     */
    public function getUserTrustLevel(int $userId): string {
        global $wpdb;

        $level = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT trust_level FROM {$wpdb->prefix}mp_user_trust_levels WHERE user_id = %d",
                $userId
            )
        );

        return $level ?: 'unverified';
    }

    /**
     * Get trust badge for user
     * 
     * @param int $userId
     * @return array
     */
    public function getTrustBadge(int $userId): array {
        $level = $this->getUserTrustLevel($userId);

        $badges = [
            'unverified' => [
                'label' => __('Unverified', 'myprotector-platform'),
                'icon' => '🔒',
                'color' => '#999',
            ],
            'verified' => [
                'label' => __('Verified', 'myprotector-platform'),
                'icon' => '✓',
                'color' => '#28a745',
            ],
            'premium' => [
                'label' => __('Premium', 'myprotector-platform'),
                'icon' => '⭐',
                'color' => '#f0ad4e',
            ],
            'trusted' => [
                'label' => __('Trusted', 'myprotector-platform'),
                'icon' => '🏆',
                'color' => '#17a2b8',
            ],
        ];

        return $badges[$level] ?? $badges['unverified'];
    }

    /**
     * Check if user has verified purchase reviews
     * 
     * @param int $userId
     * @return bool
     */
    public function hasVerifiedPurchaseReviews(int $userId): bool {
        global $wpdb;

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews 
                WHERE user_id = %d AND verified_purchase = 1 AND review_status = 'approved'",
                $userId
            )
        );

        return $count > 0;
    }

    /**
     * Get verification stats
     * 
     * @return array
     */
    public function getStats(): array {
        global $wpdb;

        return [
            'verified_users' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_user_trust_levels WHERE trust_level != 'unverified'"
            ),
            'verified_purchases' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews WHERE verified_purchase = 1"
            ),
            'trust_distribution' => [
                'unverified' => (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mp_user_trust_levels WHERE trust_level = 'unverified'"
                ),
                'verified' => (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mp_user_trust_levels WHERE trust_level = 'verified'"
                ),
                'premium' => (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mp_user_trust_levels WHERE trust_level = 'premium'"
                ),
                'trusted' => (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mp_user_trust_levels WHERE trust_level = 'trusted'"
                ),
            ],
        ];
    }

    /**
     * Validate order for verification
     * 
     * @param string $orderId
     * @param int $companyId
     * @param int $userId
     * @return bool
     */
    protected function validateOrder(string $orderId, int $companyId, int $userId): bool {
        global $wpdb;

        // Check for WooCommerce orders if available
        if (class_exists('WooCommerce')) {
            $order = wc_get_order($orderId);
            if ($order) {
                // Verify order belongs to user and contains company products
                if ((int) $order->get_user_id() !== $userId) {
                    return false;
                }
                // Additional validation could check product/company association
                return true;
            }
        }

        // Check for custom orders table
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_orders 
                WHERE order_id = %s AND user_id = %d AND company_id = %d AND status = 'completed'",
                $orderId,
                $userId,
                $companyId
            )
        );

        return $count > 0;
    }

    /**
     * Log trust level change
     * 
     * @param int $userId
     * @param string $newLevel
     * @param int $adminId
     * @return void
     */
    protected function logTrustChange(int $userId, string $newLevel, int $adminId): void {
        global $wpdb;

        $wpdb->insert(
            $wpdb->prefix . 'mp_trust_level_history',
            [
                'user_id' => $userId,
                'new_level' => $newLevel,
                'changed_by' => $adminId,
                'ip_address' => $this->getClientIp(),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Get client IP
     * 
     * @return string
     */
    protected function getClientIp(): string {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}