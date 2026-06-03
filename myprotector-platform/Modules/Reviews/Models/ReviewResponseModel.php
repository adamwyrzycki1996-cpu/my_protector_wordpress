<?php
/**
 * MyProtector Platform - Review Response Model
 * 
 * Database operations for business review responses
 * 
 * @package MyProtector\Modules\Reviews\Models
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews\Models;

class ReviewResponseModel {
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
        $this->table = $wpdb->prefix . 'mp_review_responses';
    }

    /**
     * Create a response
     * 
     * @param array $data
     * @return int|\WP_Error
     */
    public function create(array $data): int|\WP_Error {
        global $wpdb;
        
        // Validate data
        $validation = $this->validate($data);
        if (is_wp_error($validation)) {
            return $validation;
        }

        $result = $wpdb->insert(
            $this->table,
            [
                'review_id' => (int) $data['review_id'],
                'company_id' => (int) $data['company_id'],
                'user_id' => (int) $data['user_id'],
                'response_content' => wp_kses_post($data['content']),
                'is_official' => isset($data['is_official']) ? 1 : 1,
                'status' => $data['status'] ?? 'pending',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s']
        );

        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to create response.', 'myprotector-platform'));
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get response by review ID
     * 
     * @param int $reviewId
     * @param bool $publishedOnly
     * @return array|null
     */
    public function getByReview(int $reviewId, bool $publishedOnly = true): ?array {
        global $wpdb;
        
        $where = 'review_id = %d';
        $params = [$reviewId];

        if ($publishedOnly) {
            $where .= " AND status = 'published'";
        }

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT r.*, u.display_name as responder_name, c.company_name
                 FROM {$this->table} r
                 LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                 LEFT JOIN {$wpdb->prefix}mp_companies c ON r.company_id = c.company_id
                 WHERE {$where}
                 ORDER BY r.created_at DESC
                 LIMIT 1",
                $params
            ),
            ARRAY_A
        );
    }

    /**
     * Get responses by company
     * 
     * @param int $companyId
     * @param array $args
     * @return array
     */
    public function getByCompany(int $companyId, array $args = []): array {
        global $wpdb;
        
        $defaults = [
            'status' => 'published',
            'limit' => 20,
            'offset' => 0,
        ];
        $args = wp_parse_args($args, $defaults);

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, rev.review_title, u.display_name as responder_name
                 FROM {$this->table} r
                 LEFT JOIN {$wpdb->prefix}mp_reviews rev ON r.review_id = rev.review_id
                 LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                 WHERE r.company_id = %d AND r.status = %s
                 ORDER BY r.created_at DESC
                 LIMIT %d OFFSET %d",
                $companyId,
                $args['status'],
                $args['limit'],
                $args['offset']
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Update response
     * 
     * @param int $responseId
     * @param array $data
     * @return bool|\WP_Error
     */
    public function update(int $responseId, array $data): bool|\WP_Error {
        global $wpdb;
        
        $updateData = [];

        if (isset($data['content'])) {
            $content = wp_kses_post($data['content']);
            if (empty(strip_tags($content))) {
                return new \WP_Error('empty_content', __('Response content is required.', 'myprotector-platform'));
            }
            if (strlen(strip_tags($content)) < 10) {
                return new \WP_Error('short_content', __('Response must be at least 10 characters.', 'myprotector-platform'));
            }
            $updateData['response_content'] = $content;
        }

        if (isset($data['status'])) {
            $validStatuses = ['pending', 'published', 'hidden', 'deleted'];
            if (!in_array($data['status'], $validStatuses)) {
                return new \WP_Error('invalid_status', __('Invalid status.', 'myprotector-platform'));
            }
            $updateData['status'] = $data['status'];
        }

        if (isset($data['is_official'])) {
            $updateData['is_official'] = $data['is_official'] ? 1 : 0;
        }

        if (empty($updateData)) {
            return true;
        }

        $updateData['updated_at'] = current_time('mysql');

        $result = $wpdb->update(
            $this->table,
            $updateData,
            ['response_id' => $responseId],
            array_keys($updateData),
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Delete response
     * 
     * @param int $responseId
     * @return bool
     */
    public function delete(int $responseId): bool {
        global $wpdb;
        
        return $wpdb->delete($this->table, ['response_id' => $responseId], ['%d']) !== false;
    }

    /**
     * Approve response
     * 
     * @param int $responseId
     * @return bool
     */
    public function approve(int $responseId): bool {
        return $this->update($responseId, ['status' => 'published']);
    }

    /**
     * Hide response
     * 
     * @param int $responseId
     * @return bool
     */
    public function hide(int $responseId): bool {
        return $this->update($responseId, ['status' => 'hidden']);
    }

    /**
     * Check if response exists for review
     * 
     * @param int $reviewId
     * @return bool
     */
    public function existsForReview(int $reviewId): bool {
        global $wpdb;
        
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE review_id = %d AND status != 'deleted'",
                $reviewId
            )
        );

        return $count > 0;
    }

    /**
     * Count responses by company
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
                    "SELECT COUNT(*) FROM {$this->table} WHERE company_id = %d AND status = %s",
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
     * Validate response data
     * 
     * @param array $data
     * @return true|\WP_Error
     */
    protected function validate(array $data): true|\WP_Error {
        // Review ID required
        if (empty($data['review_id']) || (int) $data['review_id'] <= 0) {
            return new \WP_Error('invalid_review', __('Invalid review ID.', 'myprotector-platform'));
        }

        // Company ID required
        if (empty($data['company_id']) || (int) $data['company_id'] <= 0) {
            return new \WP_Error('invalid_company', __('Invalid company ID.', 'myprotector-platform'));
        }

        // User ID required
        if (empty($data['user_id']) || (int) $data['user_id'] <= 0) {
            return new \WP_Error('invalid_user', __('Invalid user ID.', 'myprotector-platform'));
        }

        // Content required
        if (empty($data['content'])) {
            return new \WP_Error('missing_content', __('Response content is required.', 'myprotector-platform'));
        }

        // Verify user belongs to company
        if (!$this->userBelongsToCompany($data['user_id'], $data['company_id'])) {
            return new \WP_Error('unauthorized', __('You are not authorized to respond to this review.', 'myprotector-platform'));
        }

        // Check no existing response
        if ($this->existsForReview($data['review_id'])) {
            return new \WP_Error('already_responded', __('A response already exists for this review.', 'myprotector-platform'));
        }

        return true;
    }

    /**
     * Check if user belongs to company
     * 
     * @param int $userId
     * @param int $companyId
     * @return bool
     */
    protected function userBelongsToCompany(int $userId, int $companyId): bool {
        global $wpdb;
        
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_companies WHERE company_id = %d AND user_id = %d",
                $companyId,
                $userId
            )
        );

        return $count > 0;
    }
}