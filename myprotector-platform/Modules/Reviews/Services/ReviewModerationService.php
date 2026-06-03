<?php
/**
 * MyProtector Platform - Review Moderation Service
 * 
 * Handles review moderation (approve, reject, edit)
 * 
 * @package MyProtector\Modules\Reviews\Services
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews\Services;

use MyProtector\Modules\Reviews\Models\ReviewModel;
use MyProtector\Modules\Reviews\Models\ReviewImageModel;
use MyProtector\Modules\Reviews\Models\ReviewResponseModel;

class ReviewModerationService {
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
     * Constructor
     */
    public function __construct() {
        $this->reviewModel = new ReviewModel();
        $this->imageModel = new ReviewImageModel();
        $this->responseModel = new ReviewResponseModel();
    }

    /**
     * Approve review
     * 
     * @param int $reviewId
     * @param int $adminId
     * @return bool|\WP_Error
     */
    public function approve(int $reviewId, int $adminId = 0): bool|\WP_Error {
        $adminId = $adminId ?: get_current_user_id();

        if (!current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You do not have permission to moderate reviews.', 'myprotector-platform'));
        }

        $review = $this->reviewModel->getById($reviewId);
        if (!$review) {
            return new \WP_Error('not_found', __('Review not found.', 'myprotector-platform'));
        }

        if ($review['review_status'] === 'approved') {
            return new \WP_Error('already_approved', __('Review is already approved.', 'myprotector-platform'));
        }

        $result = $this->reviewModel->updateStatus($reviewId, 'approved');

        if ($result) {
            // Log moderation action
            $this->logModeration($reviewId, 'approved', null, $adminId);
            
            // Update company stats
            $this->updateCompanyStats($review['company_id']);
            
            // Send notification
            $this->notifyReviewer($reviewId, 'approved');
            
            do_action('mp_review_approved', $reviewId, $adminId);
        }

        return $result;
    }

    /**
     * Reject review
     * 
     * @param int $reviewId
     * @param string $reason
     * @param int $adminId
     * @return bool|\WP_Error
     */
    public function reject(int $reviewId, string $reason, int $adminId = 0): bool|\WP_Error {
        $adminId = $adminId ?: get_current_user_id();

        if (!current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You do not have permission to moderate reviews.', 'myprotector-platform'));
        }

        if (empty(trim($reason))) {
            return new \WP_Error('missing_reason', __('Rejection reason is required.', 'myprotector-platform'));
        }

        $review = $this->reviewModel->getById($reviewId);
        if (!$review) {
            return new \WP_Error('not_found', __('Review not found.', 'myprotector-platform'));
        }

        $result = $this->reviewModel->updateStatus($reviewId, 'rejected', $reason);

        if ($result) {
            // Log moderation action
            $this->logModeration($reviewId, 'rejected', $reason, $adminId);
            
            // Send notification
            $this->notifyReviewer($reviewId, 'rejected', $reason);
            
            do_action('mp_review_rejected', $reviewId, $adminId, $reason);
        }

        return $result;
    }

    /**
     * Edit review (admin)
     * 
     * @param int $reviewId
     * @param array $data
     * @param int $adminId
     * @return bool|\WP_Error
     */
    public function edit(int $reviewId, array $data, int $adminId = 0): bool|\WP_Error {
        $adminId = $adminId ?: get_current_user_id();

        if (!current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You do not have permission to edit reviews.', 'myprotector-platform'));
        }

        $review = $this->reviewModel->getById($reviewId);
        if (!$review) {
            return new \WP_Error('not_found', __('Review not found.', 'myprotector-platform'));
        }

        // Validate data
        $validation = $this->validateEditData($data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $result = $this->reviewModel->update($reviewId, $data);

        if ($result) {
            // Log moderation action
            $editSummary = $this->createEditSummary($data);
            $this->logModeration($reviewId, 'edited', $editSummary, $adminId);
            
            do_action('mp_review_edited', $reviewId, $adminId, $data);
        }

        return $result;
    }

    /**
     * Approve image
     * 
     * @param int $imageId
     * @param int $adminId
     * @return bool|\WP_Error
     */
    public function approveImage(int $imageId, int $adminId = 0): bool|\WP_Error {
        $adminId = $adminId ?: get_current_user_id();

        if (!current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You do not have permission to moderate images.', 'myprotector-platform'));
        }

        $image = $this->imageModel->getById($imageId);
        if (!$image) {
            return new \WP_Error('not_found', __('Image not found.', 'myprotector-platform'));
        }

        return $this->imageModel->approve($imageId);
    }

    /**
     * Reject/delete image
     * 
     * @param int $imageId
     * @param int $adminId
     * @return bool|\WP_Error
     */
    public function rejectImage(int $imageId, int $adminId = 0): bool|\WP_Error {
        $adminId = $adminId ?: get_current_user_id();

        if (!current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You do not have permission to moderate images.', 'myprotector-platform'));
        }

        return $this->imageModel->reject($imageId);
    }

    /**
     * Approve response
     * 
     * @param int $responseId
     * @param int $adminId
     * @return bool|\WP_Error
     */
    public function approveResponse(int $responseId, int $adminId = 0): bool|\WP_Error {
        $adminId = $adminId ?: get_current_user_id();

        if (!current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You do not have permission to moderate responses.', 'myprotector-platform'));
        }

        return $this->responseModel->approve($responseId);
    }

    /**
     * Hide response
     * 
     * @param int $responseId
     * @param int $adminId
     * @return bool|\WP_Error
     */
    public function hideResponse(int $responseId, int $adminId = 0): bool|\WP_Error {
        $adminId = $adminId ?: get_current_user_id();

        if (!current_user_can('manage_myprotector')) {
            return new \WP_Error('unauthorized', __('You do not have permission to moderate responses.', 'myprotector-platform'));
        }

        return $this->responseModel->hide($responseId);
    }

    /**
     * Batch approve reviews
     * 
     * @param array $reviewIds
     * @param int $adminId
     * @return array
     */
    public function batchApprove(array $reviewIds, int $adminId = 0): array {
        $adminId = $adminId ?: get_current_user_id();

        $results = [
            'approved' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($reviewIds as $reviewId) {
            $result = $this->approve((int) $reviewId, $adminId);
            
            if ($result === true) {
                $results['approved']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'review_id' => $reviewId,
                    'error' => is_wp_error($result) ? $result->get_error_message() : 'Unknown error',
                ];
            }
        }

        return $results;
    }

    /**
     * Batch reject reviews
     * 
     * @param array $reviewIds
     * @param string $reason
     * @param int $adminId
     * @return array
     */
    public function batchReject(array $reviewIds, string $reason, int $adminId = 0): array {
        $adminId = $adminId ?: get_current_user_id();

        $results = [
            'rejected' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($reviewIds as $reviewId) {
            $result = $this->reject((int) $reviewId, $reason, $adminId);
            
            if ($result === true) {
                $results['rejected']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'review_id' => $reviewId,
                    'error' => is_wp_error($result) ? $result->get_error_message() : 'Unknown error',
                ];
            }
        }

        return $results;
    }

    /**
     * Get pending reviews
     * 
     * @param array $args
     * @return array
     */
    public function getPendingReviews(array $args = []): array {
        return $this->reviewModel->getReviews(array_merge($args, ['status' => 'pending']));
    }

    /**
     * Get flagged reviews
     * 
     * @param array $args
     * @return array
     */
    public function getFlaggedReviews(array $args = []): array {
        return $this->reviewModel->getReviews(array_merge($args, ['status' => 'flagged']));
    }

    /**
     * Get moderation stats
     * 
     * @return array
     */
    public function getStats(): array {
        global $wpdb;

        return [
            'pending' => $this->reviewModel->getPendingCount(),
            'flagged' => $this->reviewModel->getFlaggedCount(),
            'approved_today' => (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews 
                    WHERE review_status = 'approved' AND DATE(updated_at) = %s",
                    date('Y-m-d')
                )
            ),
            'rejected_today' => (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews 
                    WHERE review_status = 'rejected' AND DATE(updated_at) = %s",
                    date('Y-m-d')
                )
            ),
        ];
    }

    /**
     * Log moderation action
     * 
     * @param int $reviewId
     * @param string $action
     * @param string|null $notes
     * @param int $adminId
     * @return void
     */
    protected function logModeration(int $reviewId, string $action, ?string $notes, int $adminId): void {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'mp_review_moderation_log',
            [
                'review_id' => $reviewId,
                'action' => $action,
                'notes' => $notes,
                'moderated_by' => $adminId,
                'ip_address' => $this->getClientIp(),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Update company statistics after review approval
     * 
     * @param int $companyId
     * @return void
     */
    protected function updateCompanyStats(int $companyId): void {
        global $wpdb;
        
        $avgRating = $this->reviewModel->getAverageRating($companyId);
        $totalReviews = $this->reviewModel->countByCompany($companyId, 'approved');

        $wpdb->update(
            $wpdb->prefix . 'mp_companies',
            [
                'avg_rating' => $avgRating,
                'total_reviews' => $totalReviews,
                'updated_at' => current_time('mysql'),
            ],
            ['company_id' => $companyId],
            ['%f', '%d', '%s'],
            ['%d']
        );

        do_action('mp_company_reviews_updated', $companyId);
    }

    /**
     * Notify reviewer about moderation decision
     * 
     * @param int $reviewId
     * @param string $decision
     * @param string|null $reason
     * @return void
     */
    protected function notifyReviewer(int $reviewId, string $decision, ?string $reason = null): void {
        $review = $this->reviewModel->getById($reviewId);
        
        if (!$review || empty($review['user_id'])) {
            return;
        }

        $user = get_userdata($review['user_id']);
        if (!$user) {
            return;
        }

        $subject = sprintf(
            __('Your review on %s has been %s', 'myprotector-platform'),
            $review['company_name'] ?? 'MyProtector',
            $decision
        );

        $message = sprintf(
            "Hello %s,\n\nYour review titled \"%s\" has been %s.\n\n",
            $user->display_name,
            $review['review_title'],
            $decision
        );

        if ($decision === 'rejected' && $reason) {
            $message .= sprintf("Reason: %s\n\n", $reason);
        }

        $message .= "Thank you for your feedback.\n\nBest regards,\nMyProtector Team";

        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Validate edit data
     * 
     * @param array $data
     * @return true|\WP_Error
     */
    protected function validateEditData(array $data): true|\WP_Error {
        if (isset($data['rating'])) {
            $rating = (int) $data['rating'];
            if ($rating < 1 || $rating > 5) {
                return new \WP_Error('invalid_rating', __('Rating must be between 1 and 5.', 'myprotector-platform'));
            }
        }

        if (isset($data['title'])) {
            $title = sanitize_text_field($data['title']);
            if (empty($title)) {
                return new \WP_Error('empty_title', __('Review title is required.', 'myprotector-platform'));
            }
            if (strlen($title) > 255) {
                return new \WP_Error('title_too_long', __('Review title is too long.', 'myprotector-platform'));
            }
        }

        if (isset($data['content'])) {
            $content = wp_kses_post($data['content']);
            if (empty(strip_tags($content))) {
                return new \WP_Error('empty_content', __('Review content is required.', 'myprotector-platform'));
            }
        }

        return true;
    }

    /**
     * Create edit summary
     * 
     * @param array $data
     * @return string
     */
    protected function createEditSummary(array $data): string {
        $changes = [];

        if (isset($data['rating'])) {
            $changes[] = sprintf('Rating changed to %d', $data['rating']);
        }
        if (isset($data['title'])) {
            $changes[] = 'Title updated';
        }
        if (isset($data['content'])) {
            $changes[] = 'Content updated';
        }
        if (isset($data['review_status'])) {
            $changes[] = sprintf('Status changed to %s', $data['review_status']);
        }
        if (isset($data['trust_level'])) {
            $changes[] = sprintf('Trust level changed to %s', $data['trust_level']);
        }

        return implode(', ', $changes);
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