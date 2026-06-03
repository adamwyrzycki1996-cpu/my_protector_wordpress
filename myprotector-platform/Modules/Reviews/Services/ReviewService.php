<?php
/**
 * MyProtector Platform - Review Service
 * 
 * Main service for review operations
 * 
 * @package MyProtector\Modules\Reviews\Services
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews\Services;

use MyProtector\Modules\Reviews\Models\ReviewModel;
use MyProtector\Modules\Reviews\Models\ReviewImageModel;
use MyProtector\Modules\Reviews\Models\ReviewResponseModel;

class ReviewService {
    /**
     * Review model
     * 
     * @var ReviewModel
     */
    protected ReviewModel $reviewModel;

    /**
     * Image model
     * 
     * @var ReviewImageModel
     */
    protected ReviewImageModel $imageModel;

    /**
     * Response model
     * 
     * @var ReviewResponseModel
     */
    protected ReviewResponseModel $responseModel;

    /**
     * Rate limiting: max reviews per user per hour
     * 
     * @var int
     */
    protected int $rateLimitPerHour = 3;

    /**
     * Constructor
     */
    public function __construct() {
        $this->reviewModel = new ReviewModel();
        $this->imageModel = new ReviewImageModel();
        $this->responseModel = new ReviewResponseModel();
    }

    /**
     * Create a new review
     * 
     * @param array $data
     * @return int|\WP_Error
     */
    public function create(array $data): int|\WP_Error {
        // Rate limiting check
        if (!$this->checkRateLimit()) {
            return new \WP_Error(
                'rate_limited',
                __('You are submitting reviews too quickly. Please try again later.', 'myprotector-platform')
            );
        }

        // Check for duplicate review
        $userId = $data['user_id'] ?? get_current_user_id();
        $companyId = $data['company_id'] ?? 0;

        if ($this->reviewModel->userHasReviewed($userId, $companyId)) {
            return new \WP_Error(
                'duplicate_review',
                __('You have already reviewed this business.', 'myprotector-platform')
            );
        }

        // Create review
        $reviewId = $this->reviewModel->create($data);

        if (is_wp_error($reviewId)) {
            return $reviewId;
        }

        // Handle image uploads
        if (!empty($data['images']) && is_array($data['images'])) {
            $this->processImages($reviewId, $data['images'], $userId);
        }

        // Send notification
        do_action('mp_review_submitted', $reviewId, $data);

        return $reviewId;
    }

    /**
     * Get reviews with filters
     * 
     * @param array $args
     * @return array
     */
    public function getReviews(array $args = []): array {
        $reviews = $this->reviewModel->getReviews($args);

        // Enrich with images and responses
        foreach ($reviews as &$review) {
            $review['images'] = $this->imageModel->getByReview($review['review_id'], true);
            
            $response = $this->responseModel->getByReview($review['review_id']);
            if ($response) {
                $review['response'] = $response;
            }
        }

        return $reviews;
    }

    /**
     * Get single review
     * 
     * @param int $reviewId
     * @return array|null
     */
    public function getReview(int $reviewId): ?array {
        $review = $this->reviewModel->getById($reviewId);

        if (!$review) {
            return null;
        }

        // Enrich with images
        $review['images'] = $this->imageModel->getByReview($reviewId, true);
        
        // Enrich with response
        $response = $this->responseModel->getByReview($reviewId);
        if ($response) {
            $review['response'] = $response;
        }

        return $review;
    }

    /**
     * Update review
     * 
     * @param int $reviewId
     * @param array $data
     * @return bool|\WP_Error
     */
    public function update(int $reviewId, array $data): bool|\WP_Error {
        // Verify ownership
        $review = $this->reviewModel->getById($reviewId);
        
        if (!$review) {
            return new \WP_Error('not_found', __('Review not found.', 'myprotector-platform'));
        }

        // Only allow update by review owner or admin
        $userId = get_current_user_id();
        if ($review['user_id'] != $userId && !current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You cannot edit this review.', 'myprotector-platform'));
        }

        // Only allow edit on pending reviews (unless admin)
        if ($review['review_status'] !== 'pending' && !current_user_can('manage_myprotector')) {
            return new \WP_Error(
                'not_editable',
                __('This review cannot be edited.', 'myprotector-platform')
            );
        }

        $result = $this->reviewModel->update($reviewId, $data);

        if ($result !== false) {
            do_action('mp_review_updated', $reviewId, $data);
        }

        return $result;
    }

    /**
     * Delete review
     * 
     * @param int $reviewId
     * @return bool|\WP_Error
     */
    public function delete(int $reviewId): bool|\WP_Error {
        $review = $this->reviewModel->getById($reviewId);
        
        if (!$review) {
            return new \WP_Error('not_found', __('Review not found.', 'myprotector-platform'));
        }

        // Verify ownership or admin
        $userId = get_current_user_id();
        if ($review['user_id'] != $userId && !current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You cannot delete this review.', 'myprotector-platform'));
        }

        // Delete images
        $this->imageModel->deleteByReview($reviewId);

        // Delete review
        $result = $this->reviewModel->delete($reviewId);

        if ($result) {
            do_action('mp_review_deleted', $reviewId, $review);
        }

        return $result;
    }

    /**
     * Mark review as helpful
     * 
     * @param int $reviewId
     * @param int $userId
     * @return int|\WP_Error
     */
    public function markHelpful(int $reviewId, int $userId = 0): int|\WP_Error {
        $userId = $userId ?: get_current_user_id();

        if ($userId <= 0) {
            return new \WP_Error('invalid_user', __('Invalid user.', 'myprotector-platform'));
        }

        // Check if already marked helpful
        if ($this->hasMarkedHelpful($userId, $reviewId)) {
            return new \WP_Error('already_marked', __('You have already marked this as helpful.', 'myprotector-platform'));
        }

        // Record the helpful mark
        $this->recordHelpfulMark($userId, $reviewId);

        // Increment count
        return $this->reviewModel->incrementHelpful($reviewId);
    }

    /**
     * Report review
     * 
     * @param int $reviewId
     * @param int $userId
     * @param string $reason
     * @return bool|\WP_Error
     */
    public function reportReview(int $reviewId, int $userId, string $reason): bool|\WP_Error {
        $userId = $userId ?: get_current_user_id();

        if ($userId <= 0) {
            return new \WP_Error('invalid_user', __('Invalid user.', 'myprotector-platform'));
        }

        if (empty(trim($reason))) {
            return new \WP_Error('missing_reason', __('Please provide a reason for the report.', 'myprotector-platform'));
        }

        // Check if already reported
        if ($this->hasReported($userId, $reviewId)) {
            return new \WP_Error('already_reported', __('You have already reported this review.', 'myprotector-platform'));
        }

        // Record report
        $this->recordReport($userId, $reviewId, $reason);

        // Increment report count
        $count = $this->reviewModel->incrementReport($reviewId);

        // Auto-flag if too many reports
        if ($count >= 3) {
            $this->reviewModel->updateStatus($reviewId, 'flagged');
        }

        do_action('mp_review_reported', $reviewId, $userId, $reason);

        return true;
    }

    /**
     * Add image to review
     * 
     * @param int $reviewId
     * @param array $imageData
     * @return int|\WP_Error
     */
    public function addImage(int $reviewId, array $imageData): int|\WP_Error {
        $review = $this->reviewModel->getById($reviewId);
        
        if (!$review) {
            return new \WP_Error('not_found', __('Review not found.', 'myprotector-platform'));
        }

        // Verify ownership
        $userId = get_current_user_id();
        if ($review['user_id'] != $userId && !current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You cannot add images to this review.', 'myprotector-platform'));
        }

        // Check image limit
        $currentCount = $this->imageModel->countByReview($reviewId, false);
        if ($currentCount >= 5) {
            return new \WP_Error('image_limit', __('Maximum 5 images allowed per review.', 'myprotector-platform'));
        }

        $imageData['review_id'] = $reviewId;
        $imageData['uploaded_by'] = $userId;

        return $this->imageModel->add($reviewId, $imageData);
    }

    /**
     * Get review stats for company
     * 
     * @param int $companyId
     * @return array
     */
    public function getCompanyStats(int $companyId): array {
        return [
            'total_reviews' => $this->reviewModel->countByCompany($companyId, 'approved'),
            'average_rating' => $this->reviewModel->getAverageRating($companyId),
            'rating_distribution' => $this->reviewModel->getRatingDistribution($companyId),
            'pending_count' => $this->reviewModel->countByCompany($companyId, 'pending'),
        ];
    }

    /**
     * Process image uploads
     * 
     * @param int $reviewId
     * @param array $images
     * @param int $userId
     * @return void
     */
    protected function processImages(int $reviewId, array $images, int $userId): void {
        foreach ($images as $imageData) {
            if (!is_array($imageData)) {
                continue;
            }

            $imageData['review_id'] = $reviewId;
            $imageData['uploaded_by'] = $userId;

            $this->imageModel->add($reviewId, $imageData);
        }
    }

    /**
     * Check rate limit
     * 
     * @return bool
     */
    protected function checkRateLimit(): bool {
        $userId = get_current_user_id();
        if ($userId <= 0) {
            return false;
        }

        $key = 'mp_review_rate_' . $userId;
        $count = get_transient($key) ?: 0;

        return $count < $this->rateLimitPerHour;
    }

    /**
     * Record helpful mark
     * 
     * @param int $userId
     * @param int $reviewId
     * @return void
     */
    protected function recordHelpfulMark(int $userId, int $reviewId): void {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'mp_review_helpful',
            [
                'review_id' => $reviewId,
                'user_id' => $userId,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s']
        );

        // Update rate limit counter
        $key = 'mp_review_rate_' . $userId;
        $count = get_transient($key) ?: 0;
        set_transient($key, $count + 1, HOUR_IN_SECONDS);
    }

    /**
     * Check if user marked review as helpful
     * 
     * @param int $userId
     * @param int $reviewId
     * @return bool
     */
    protected function hasMarkedHelpful(int $userId, int $reviewId): bool {
        global $wpdb;
        
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_review_helpful 
                WHERE user_id = %d AND review_id = %d",
                $userId,
                $reviewId
            )
        );

        return $count > 0;
    }

    /**
     * Record report
     * 
     * @param int $userId
     * @param int $reviewId
     * @param string $reason
     * @return void
     */
    protected function recordReport(int $userId, int $reviewId, string $reason): void {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'mp_review_reports',
            [
                'review_id' => $reviewId,
                'user_id' => $userId,
                'reason' => sanitize_text_field($reason),
                'ip_address' => $this->getClientIp(),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );
    }

    /**
     * Check if user reported review
     * 
     * @param int $userId
     * @param int $reviewId
     * @return bool
     */
    protected function hasReported(int $userId, int $reviewId): bool {
        global $wpdb;
        
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_review_reports 
                WHERE user_id = %d AND review_id = %d",
                $userId,
                $reviewId
            )
        );

        return $count > 0;
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