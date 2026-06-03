<?php
/**
 * MyProtector Platform - Review Model
 * 
 * Database operations for reviews
 * 
 * @package MyProtector\Modules\Reviews\Models
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews\Models;

class ReviewModel {
    /**
     * Database table name
     * 
     * @var string
     */
    protected $table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'mp_reviews';
    }

    /**
     * Create a new review
     * 
     * @param array $data
     * @return int|\WP_Error
     */
    public function create(array $data): int|\WP_Error {
        global $wpdb;
        
        // Sanitize data
        $sanitized = $this->sanitizeReviewData($data);
        
        if (is_wp_error($sanitized)) {
            return $sanitized;
        }

        $result = $wpdb->insert(
            $this->table,
            [
                'company_id' => $sanitized['company_id'],
                'user_id' => $sanitized['user_id'],
                'review_title' => $sanitized['review_title'],
                'review_content' => $sanitized['review_content'],
                'review_rating' => $sanitized['review_rating'],
                'review_status' => $sanitized['review_status'] ?? 'pending',
                'trust_level' => $sanitized['trust_level'] ?? 'unverified',
                'ip_address' => $sanitized['ip_address'] ?? '',
                'user_agent' => $sanitized['user_agent'] ?? '',
                'verified_purchase' => $sanitized['verified_purchase'] ?? 0,
                'verified_order_id' => $sanitized['verified_order_id'] ?? null,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        if ($result === false) {
            return new \WP_Error('db_insert_error', __('Failed to create review.', 'myprotector-platform'));
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get review by ID
     * 
     * @param int $reviewId
     * @return array|null
     */
    public function getById(int $reviewId): ?array {
        global $wpdb;
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT r.*, u.display_name as user_name, u.user_email,
                        c.company_name, c.company_slug
                 FROM {$this->table} r
                 LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                 LEFT JOIN {$wpdb->prefix}mp_companies c ON r.company_id = c.company_id
                 WHERE r.review_id = %d",
                $reviewId
            ),
            ARRAY_A
        );

        return $result ?: null;
    }

    /**
     * Get reviews with filters
     * 
     * @param array $args
     * @return array
     */
    public function getReviews(array $args = []): array {
        global $wpdb;
        
        $defaults = [
            'company_id' => null,
            'user_id' => null,
            'status' => 'approved',
            'trust_level' => null,
            'min_rating' => null,
            'max_rating' => null,
            'search' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 10,
            'offset' => 0,
            'include_images' => true,
            'include_response' => true,
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $where = ['1=1'];
        $params = [];

        if ($args['company_id']) {
            $where[] = 'r.company_id = %d';
            $params[] = $args['company_id'];
        }

        if ($args['user_id']) {
            $where[] = 'r.user_id = %d';
            $params[] = $args['user_id'];
        }

        if ($args['status']) {
            $where[] = 'r.review_status = %s';
            $params[] = $args['status'];
        }

        if ($args['trust_level']) {
            $where[] = 'r.trust_level = %s';
            $params[] = $args['trust_level'];
        }

        if ($args['min_rating']) {
            $where[] = 'r.review_rating >= %d';
            $params[] = $args['min_rating'];
        }

        if ($args['max_rating']) {
            $where[] = 'r.review_rating <= %d';
            $params[] = $args['max_rating'];
        }

        if ($args['search']) {
            $where[] = '(r.review_title LIKE %s OR r.review_content LIKE %s)';
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = implode(' AND ', $where);
        $order = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "
            SELECT r.*, u.display_name as user_name, c.company_name, c.company_slug,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}mp_review_images WHERE review_id = r.review_id) as image_count,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}mp_review_responses WHERE review_id = r.review_id AND status = 'published') as has_response
            FROM {$this->table} r
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}mp_companies c ON r.company_id = c.company_id
            WHERE {$whereClause}
            ORDER BY {$order}
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $args['limit'];
        $params[] = $args['offset'];

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }

    /**
     * Update review
     * 
     * @param int $reviewId
     * @param array $data
     * @return bool|\WP_Error
     */
    public function update(int $reviewId, array $data): bool|\WP_Error {
        global $wpdb;
        
        $sanitized = $this->sanitizeReviewData($data, true);
        
        if (is_wp_error($sanitized)) {
            return $sanitized;
        }

        $updateData = array_filter([
            'review_title' => $sanitized['review_title'] ?? null,
            'review_content' => $sanitized['review_content'] ?? null,
            'review_rating' => $sanitized['review_rating'] ?? null,
            'review_status' => $sanitized['review_status'] ?? null,
            'trust_level' => $sanitized['trust_level'] ?? null,
            'verified_purchase' => $sanitized['verified_purchase'] ?? null,
            'is_featured' => $sanitized['is_featured'] ?? null,
            'is_published' => $sanitized['is_published'] ?? null,
            'published_at' => $sanitized['is_published'] ? current_time('mysql') : null,
        ], fn($v) => $v !== null);

        $updateData['updated_at'] = current_time('mysql');

        $result = $wpdb->update(
            $this->table,
            $updateData,
            ['review_id' => $reviewId],
            array_values($updateData),
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Update review status
     * 
     * @param int $reviewId
     * @param string $status
     * @param string|null $reason
     * @return bool
     */
    public function updateStatus(int $reviewId, string $status, ?string $reason = null): bool {
        global $wpdb;
        
        $data = [
            'review_status' => $status,
            'updated_at' => current_time('mysql'),
        ];

        if ($status === 'approved') {
            $data['is_published'] = 1;
            $data['published_at'] = current_time('mysql');
        }

        if ($reason) {
            $data['rejection_reason'] = $reason;
        }

        $result = $wpdb->update(
            $this->table,
            $data,
            ['review_id' => $reviewId],
            array_keys($data),
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Delete review
     * 
     * @param int $reviewId
     * @return bool
     */
    public function delete(int $reviewId): bool {
        global $wpdb;
        
        return $wpdb->delete($this->table, ['review_id' => $reviewId], ['%d']) !== false;
    }

    /**
     * Increment helpful count
     * 
     * @param int $reviewId
     * @return int
     */
    public function incrementHelpful(int $reviewId): int {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table} SET helpful_count = helpful_count + 1 WHERE review_id = %d",
                $reviewId
            )
        );

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT helpful_count FROM {$this->table} WHERE review_id = %d", $reviewId)
        );
    }

    /**
     * Increment report count
     * 
     * @param int $reviewId
     * @return int
     */
    public function incrementReport(int $reviewId): int {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table} SET report_count = report_count + 1 WHERE review_id = %d",
                $reviewId
            )
        );

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT report_count FROM {$this->table} WHERE review_id = %d", $reviewId)
        );
    }

    /**
     * Check if user already reviewed company
     * 
     * @param int $userId
     * @param int $companyId
     * @return bool
     */
    public function userHasReviewed(int $userId, int $companyId): bool {
        global $wpdb;
        
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d AND company_id = %d",
                $userId,
                $companyId
            )
        );

        return $count > 0;
    }

    /**
     * Count reviews by company
     * 
     * @param int $companyId
     * @param string|null $status
     * @return int
     */
    public function countByCompany(int $companyId, ?string $status = null): int {
        global $wpdb;
        
        if ($status) {
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table} WHERE company_id = %d AND review_status = %s",
                    $companyId,
                    $status
                )
            );
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE company_id = %d",
                $companyId
            )
        );
    }

    /**
     * Get average rating for company
     * 
     * @param int $companyId
     * @return float
     */
    public function getAverageRating(int $companyId): float {
        global $wpdb;
        
        $avg = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(review_rating) FROM {$this->table} 
                WHERE company_id = %d AND review_status = 'approved'",
                $companyId
            )
        );

        return (float) ($avg ?? 0);
    }

    /**
     * Get rating distribution for company
     * 
     * @param int $companyId
     * @return array
     */
    public function getRatingDistribution(int $companyId): array {
        global $wpdb;
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT review_rating, COUNT(*) as count 
                FROM {$this->table} 
                WHERE company_id = %d AND review_status = 'approved'
                GROUP BY review_rating
                ORDER BY review_rating DESC",
                $companyId
            ),
            ARRAY_A
        );

        $distribution = array_fill(1, 5, 0);
        foreach ($results as $row) {
            $distribution[(int) $row['review_rating']] = (int) $row['count'];
        }

        return $distribution;
    }

    /**
     * Get pending reviews count
     * 
     * @return int
     */
    public function getPendingCount(): int {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table} WHERE review_status = 'pending'"
        );
    }

    /**
     * Get flagged reviews count
     * 
     * @return int
     */
    public function getFlaggedCount(): int {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table} WHERE report_count >= 3"
        );
    }

    /**
     * Sanitize review data
     * 
     * @param array $data
     * @param bool $isUpdate
     * @return array|\WP_Error
     */
    protected function sanitizeReviewData(array $data, bool $isUpdate = false): array|\WP_Error {
        $sanitized = [];

        // Company ID
        if (!$isUpdate || isset($data['company_id'])) {
            $companyId = isset($data['company_id']) ? (int) $data['company_id'] : 0;
            if ($companyId <= 0 && !$isUpdate) {
                return new \WP_Error('invalid_company', __('Invalid company.', 'myprotector-platform'));
            }
            $sanitized['company_id'] = $companyId;
        }

        // User ID
        if (!$isUpdate || isset($data['user_id'])) {
            $userId = isset($data['user_id']) ? (int) $data['user_id'] : get_current_user_id();
            if ($userId <= 0 && !$isUpdate) {
                return new \WP_Error('invalid_user', __('Invalid user.', 'myprotector-platform'));
            }
            $sanitized['user_id'] = $userId;
        }

        // Rating
        if (!$isUpdate || isset($data['rating'])) {
            $rating = isset($data['rating']) ? (int) $data['rating'] : (isset($data['review_rating']) ? (int) $data['review_rating'] : 0);
            if ($rating < 1 || $rating > 5) {
                return new \WP_Error('invalid_rating', __('Rating must be between 1 and 5.', 'myprotector-platform'));
            }
            $sanitized['review_rating'] = $rating;
        }

        // Title
        if (isset($data['title'])) {
            $title = sanitize_text_field($data['title']);
            if (empty($title)) {
                return new \WP_Error('empty_title', __('Review title is required.', 'myprotector-platform'));
            }
            if (strlen($title) > 255) {
                $title = substr($title, 0, 255);
            }
            $sanitized['review_title'] = $title;
        } elseif (!$isUpdate) {
            return new \WP_Error('missing_title', __('Review title is required.', 'myprotector-platform'));
        }

        // Content
        if (isset($data['content'])) {
            $content = wp_kses_post($data['content']);
            if (empty(strip_tags($content))) {
                return new \WP_Error('empty_content', __('Review content is required.', 'myprotector-platform'));
            }
            if (strlen(strip_tags($content)) < 20) {
                return new \WP_Error('short_content', __('Review must be at least 20 characters.', 'myprotector-platform'));
            }
            $sanitized['review_content'] = $content;
        } elseif (!$isUpdate) {
            return new \WP_Error('missing_content', __('Review content is required.', 'myprotector-platform'));
        }

        // Status
        if (isset($data['status'])) {
            $validStatuses = ['pending', 'approved', 'rejected', 'flagged'];
            if (!in_array($data['status'], $validStatuses)) {
                return new \WP_Error('invalid_status', __('Invalid status.', 'myprotector-platform'));
            }
            $sanitized['review_status'] = $data['status'];
        }

        // Trust level
        if (isset($data['trust_level'])) {
            $validLevels = ['unverified', 'verified', 'premium'];
            if (!in_array($data['trust_level'], $validLevels)) {
                return new \WP_Error('invalid_trust_level', __('Invalid trust level.', 'myprotector-platform'));
            }
            $sanitized['trust_level'] = $data['trust_level'];
        }

        // Verified purchase
        if (isset($data['verified_purchase'])) {
            $sanitized['verified_purchase'] = (int) $data['verified_purchase'] ? 1 : 0;
        }

        // Verified order ID
        if (isset($data['verified_order_id'])) {
            $sanitized['verified_order_id'] = sanitize_text_field($data['verified_order_id']);
        }

        // IP address
        if (isset($data['ip_address'])) {
            $sanitized['ip_address'] = sanitize_text_field($data['ip_address']);
        } elseif (!$isUpdate) {
            $sanitized['ip_address'] = $this->getClientIp();
        }

        // User agent
        if (isset($data['user_agent'])) {
            $sanitized['user_agent'] = sanitize_text_field($data['user_agent']);
        } elseif (!$isUpdate) {
            $sanitized['user_agent'] = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        }

        // Featured
        if (isset($data['is_featured'])) {
            $sanitized['is_featured'] = (int) $data['is_featured'] ? 1 : 0;
        }

        return $sanitized;
    }

    /**
     * Get client IP address
     * 
     * @return string
     */
    protected function getClientIp(): string {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Handle comma-separated IPs
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