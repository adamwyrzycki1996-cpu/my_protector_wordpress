<?php
/**
 * MyProtector Platform - Reviews Admin Controller
 * 
 * Admin interface for managing reviews
 * 
 * @package MyProtector\Modules\Reviews\Admin
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews\Admin;

use MyProtector\Modules\Reviews\Reviews;
use MyProtector\Modules\Reviews\Services\ReviewModerationService;
use MyProtector\Modules\Reviews\Services\ReviewAnalyticsService;

class ReviewsAdminController {
    /**
     * Module reference
     * 
     * @var Reviews
     */
    protected Reviews $module;

    /**
     * Moderation service
     * 
     * @var ReviewModerationService
     */
    protected ReviewModerationService $moderationService;

    /**
     * Analytics service
     * 
     * @var ReviewAnalyticsService
     */
    protected ReviewAnalyticsService $analyticsService;

    /**
     * Constructor
     * 
     * @param Reviews $module
     */
    public function __construct(Reviews $module) {
        $this->module = $module;
        $this->moderationService = new ReviewModerationService();
        $this->analyticsService = new ReviewAnalyticsService();
        
        $this->registerAdminMenu();
        $this->enqueueAssets();
    }

    /**
     * Register admin menu items
     * 
     * @return void
     */
    protected function registerAdminMenu(): void {
        add_submenu_page(
            'mp-businesses',
            __('Reviews', 'myprotector-platform'),
            __('Reviews', 'myprotector-platform'),
            'manage_myprotector',
            'mp-reviews',
            [$this, 'renderListPage']
        );

        add_submenu_page(
            'mp-reviews',
            __('All Reviews', 'myprotector-platform'),
            __('All Reviews', 'myprotector-platform'),
            'manage_myprotector',
            'mp-reviews',
            [$this, 'renderListPage']
        );

        add_submenu_page(
            'mp-reviews',
            __('Pending Reviews', 'myprotector-platform'),
            __('Pending', 'myprotector-platform'),
            'manage_myprotector',
            'mp-reviews-pending',
            [$this, 'renderPendingPage']
        );

        add_submenu_page(
            'mp-reviews',
            __('Review Analytics', 'myprotector-platform'),
            __('Analytics', 'myprotector-platform'),
            'manage_myprotector',
            'mp-reviews-analytics',
            [$this, 'renderAnalyticsPage']
        );
    }

    /**
     * Enqueue admin assets
     * 
     * @return void
     */
    protected function enqueueAssets(): void {
        add_action('admin_enqueue_scripts', function($hook) {
            if (strpos($hook, 'mp-reviews') === false) {
                return;
            }

            wp_enqueue_style(
                'mp-reviews-admin',
                $this->module->getUrl('assets/css/reviews-admin.css'),
                [],
                $this->module->getVersion()
            );

            wp_enqueue_script(
                'mp-reviews-admin',
                $this->module->getUrl('assets/js/reviews-admin.js'),
                ['jquery'],
                $this->module->getVersion(),
                true
            );

            wp_localize_script('mp-reviews-admin', 'mpReviewsAdmin', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mp_reviews_admin'),
                'strings' => [
                    'confirmApprove' => __('Are you sure you want to approve this review?', 'myprotector-platform'),
                    'confirmReject' => __('Are you sure you want to reject this review?', 'myprotector-platform'),
                    'confirmDelete' => __('Are you sure you want to delete this review?', 'myprotector-platform'),
                    'approveSuccess' => __('Review approved.', 'myprotector-platform'),
                    'rejectSuccess' => __('Review rejected.', 'myprotector-platform'),
                    'deleteSuccess' => __('Review deleted.', 'myprotector-platform'),
                ],
            ]);
        });
    }

    /**
     * Render reviews list page
     * 
     * @return void
     */
    public function renderListPage(): void {
        $statusFilter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $perPage = 20;

        $args = [
            'status' => $statusFilter ?: null,
            'search' => $search ?: null,
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];

        $reviews = $this->moderationService->getPendingReviews($args);
        $stats = $this->moderationService->getStats();

        // Adjust args for all statuses if not filtering
        if ($statusFilter) {
            $args['status'] = $statusFilter;
        } else {
            unset($args['status']);
        }

        // Get reviews with the filter
        global $wpdb;
        $where = "1=1";
        $params = [];

        if ($statusFilter) {
            $where .= " AND r.review_status = %s";
            $params[] = $statusFilter;
        }

        if ($search) {
            $where .= " AND (r.review_title LIKE %s OR r.review_content LIKE %s OR c.company_name LIKE %s)";
            $searchTerm = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql = "SELECT r.*, u.display_name as user_name, c.company_name, c.company_slug
                FROM {$wpdb->prefix}mp_reviews r
                LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                LEFT JOIN {$wpdb->prefix}mp_companies c ON r.company_id = c.company_id
                WHERE {$where}
                ORDER BY r.created_at DESC
                LIMIT %d OFFSET %d";

        $params[] = $perPage;
        $params[] = ($page - 1) * $perPage;

        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $reviews = $wpdb->get_results($sql, ARRAY_A) ?: [];

        include $this->module->getPath('templates/admin/reviews-list.php');
    }

    /**
     * Render pending reviews page
     * 
     * @return void
     */
    public function renderPendingPage(): void {
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $perPage = 20;

        global $wpdb;

        $sql = "SELECT r.*, u.display_name as user_name, c.company_name, c.company_slug
                FROM {$wpdb->prefix}mp_reviews r
                LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                LEFT JOIN {$wpdb->prefix}mp_companies c ON r.company_id = c.company_id
                WHERE r.review_status = 'pending'
                ORDER BY r.created_at ASC
                LIMIT %d OFFSET %d";

        $sql = $wpdb->prepare($sql, $perPage, ($page - 1) * $perPage);
        $reviews = $wpdb->get_results($sql, ARRAY_A) ?: [];

        $totalPending = $this->moderationService->getStats()['pending'];

        include $this->module->getPath('templates/admin/reviews-pending.php');
    }

    /**
     * Render single review edit page
     * 
     * @return void
     */
    public function renderEditPage(): void {
        $reviewId = isset($_GET['review_id']) ? (int) $_GET['review_id'] : 0;

        if (!$reviewId) {
            wp_die(__('Invalid review ID.', 'myprotector-platform'));
        }

        global $wpdb;

        $review = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT r.*, u.display_name as user_name, u.user_email, 
                        c.company_name, c.company_slug
                 FROM {$wpdb->prefix}mp_reviews r
                 LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
                 LEFT JOIN {$wpdb->prefix}mp_companies c ON r.company_id = c.company_id
                 WHERE r.review_id = %d",
                $reviewId
            ),
            ARRAY_A
        );

        if (!$review) {
            wp_die(__('Review not found.', 'myprotector-platform'));
        }

        // Get images
        $images = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mp_review_images WHERE review_id = %d",
                $reviewId
            ),
            ARRAY_A
        ) ?: [];

        // Get response
        $response = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}mp_review_responses WHERE review_id = %d AND status = 'published'",
                $reviewId
            ),
            ARRAY_A
        );

        // Get moderation history
        $history = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ml.*, u.display_name as moderator_name
                 FROM {$wpdb->prefix}mp_review_moderation_log ml
                 LEFT JOIN {$wpdb->users} u ON ml.moderated_by = u.ID
                 WHERE ml.review_id = %d
                 ORDER BY ml.created_at DESC
                 LIMIT 20",
                $reviewId
            ),
            ARRAY_A
        ) ?: [];

        include $this->module->getPath('templates/admin/reviews-edit.php');
    }

    /**
     * Render analytics page
     * 
     * @return void
     */
    public function renderAnalyticsPage(): void {
        $analytics = $this->analyticsService->getPlatformAnalytics();
        $stats = $this->moderationService->getStats();

        include $this->module->getPath('templates/admin/reviews-analytics.php');
    }

    /**
     * AJAX: Approve review
     * 
     * @return void
     */
    public function ajaxApprove(): void {
        check_ajax_referer('mp_reviews_admin', 'nonce');

        $reviewId = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;

        if (!$reviewId) {
            wp_send_json_error(['message' => __('Invalid review ID.', 'myprotector-platform')]);
        }

        $result = $this->moderationService->approve($reviewId);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => __('Review approved.', 'myprotector-platform')]);
    }

    /**
     * AJAX: Reject review
     * 
     * @return void
     */
    public function ajaxReject(): void {
        check_ajax_referer('mp_reviews_admin', 'nonce');

        $reviewId = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        if (!$reviewId) {
            wp_send_json_error(['message' => __('Invalid review ID.', 'myprotector-platform')]);
        }

        if (empty($reason)) {
            wp_send_json_error(['message' => __('Rejection reason is required.', 'myprotector-platform')]);
        }

        $result = $this->moderationService->reject($reviewId, $reason);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => __('Review rejected.', 'myprotector-platform')]);
    }

    /**
     * AJAX: Batch approve
     * 
     * @return void
     */
    public function ajaxBatchApprove(): void {
        check_ajax_referer('mp_reviews_admin', 'nonce');

        $reviewIds = isset($_POST['review_ids']) ? array_map('intval', $_POST['review_ids']) : [];

        if (empty($reviewIds)) {
            wp_send_json_error(['message' => __('No reviews selected.', 'myprotector-platform')]);
        }

        $result = $this->moderationService->batchApprove($reviewIds);

        wp_send_json_success([
            'message' => sprintf(__('%d reviews approved.', 'myprotector-platform'), $result['approved']),
            'failed' => $result['failed'],
        ]);
    }
}