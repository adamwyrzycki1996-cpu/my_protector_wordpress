<?php
/**
 * MyProtector Platform - Reviews Module
 * 
 * Handles all review-related functionality
 * 
 * @package MyProtector\Modules\Reviews
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews;

use MyProtector\Core\Module;

class Reviews extends Module {
    /**
     * Module name
     * 
     * @var string
     */
    protected $name = 'reviews';

    /**
     * Module dependencies
     * 
     * @var array
     */
    protected $dependencies = ['business-profiles'];

    /**
     * Get module directory
     * 
     * @return string
     */
    protected function getModuleDirectory(): string {
        return 'Reviews';
    }

    /**
     * Boot the module
     * 
     * @return void
     */
    public function boot(): void {
        // Register review services
        $this->registerServices();
        
        // Initialize review handlers
        $this->initHandlers();
        
        // Setup AJAX endpoints
        $this->setupAjaxEndpoints();
    }

    /**
     * Register module hooks
     * 
     * @return void
     */
    public function registerHooks(): void {
        // Review submission
        $this->addAction('wp_ajax_mp_submit_review', [$this, 'handleReviewSubmission']);
        $this->addAction('wp_ajax_nopriv_mp_submit_review', [$this, 'handleReviewSubmission']);
        
        // Helpful marking
        $this->addAction('wp_ajax_mp_mark_helpful', [$this, 'handleMarkHelpful']);
        $this->addAction('wp_ajax_nopriv_mp_mark_helpful', [$this, 'handleMarkHelpful']);
        
        // Report review
        $this->addAction('wp_ajax_mp_report_review', [$this, 'handleReportReview']);
        $this->addAction('wp_ajax_nopriv_mp_report_review', [$this, 'handleReportReview']);
        
        // Admin actions
        $this->addAction('wp_ajax_mp_reviews_approve', [$this->adminController, 'ajaxApprove']);
        $this->addAction('wp_ajax_mp_reviews_reject', [$this, 'handleAdminReject']);
        $this->addAction('wp_ajax_mp_reviews_batch_approve', [$this->adminController, 'ajaxBatchApprove']);
        $this->addAction('wp_ajax_mp_reviews_update', [$this, 'handleAdminUpdate']);
        $this->addAction('wp_ajax_mp_reviews_action', [$this, 'handleAdminAction']);
        
        // Content filter
        $this->addFilter('the_content', [$this, 'filterReviewContent'], 20);
        
        // Post type hooks
        $this->addAction('init', [$this, 'registerPostType']);
        
        // Status transition
        $this->addAction('transition_post_status', [$this, 'handleStatusChange'], 10, 3);
        
        // REST API
        $this->registerApiRoutes();
    }

    /**
     * Register REST API routes
     * 
     * @return void
     */
    protected function registerApiRoutes(): void {
        // Public routes
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/reviews', [
            'methods' => 'GET',
            'callback' => [$this, 'getReviewsApi'],
            'permission_callback' => '__return_true',
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/reviews/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'getReviewApi'],
            'permission_callback' => '__return_true',
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/reviews', [
            'methods' => 'POST',
            'callback' => [$this, 'createReviewApi'],
            'permission_callback' => '__return_true',
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/reviews/(?P<id>\d+)/helpful', [
            'methods' => 'POST',
            'callback' => [$this, 'markHelpfulApi'],
            'permission_callback' => '__return_true',
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/reviews/(?P<id>\d+)/report', [
            'methods' => 'POST',
            'callback' => [$this, 'reportReviewApi'],
            'permission_callback' => '__return_true',
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/reviews/(?P<id>\d+)/images', [
            'methods' => 'POST',
            'callback' => [$this, 'uploadImagesApi'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);
        
        // Company reviews
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/companies/(?P<company_id>\d+)/reviews', [
            'methods' => 'GET',
            'callback' => [$this, 'getCompanyReviewsApi'],
            'permission_callback' => '__return_true',
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/companies/(?P<company_id>\d+)/reviews/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'getCompanyReviewStatsApi'],
            'permission_callback' => '__return_true',
        ]);
        
        // Admin routes
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/admin/reviews/(?P<id>\d+)/approve', [
            'methods' => 'POST',
            'callback' => [$this, 'adminApproveApi'],
            'permission_callback' => function() {
                return current_user_can('manage_myprotector');
            },
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/admin/reviews/(?P<id>\d+)/reject', [
            'methods' => 'POST',
            'callback' => [$this, 'adminRejectApi'],
            'permission_callback' => function() {
                return current_user_can('manage_myprotector');
            },
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/admin/reviews/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [$this, 'adminUpdateApi'],
            'permission_callback' => function() {
                return current_user_can('manage_myprotector');
            },
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/admin/reviews/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'adminDeleteApi'],
            'permission_callback' => function() {
                return current_user_can('manage_myprotector');
            },
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/admin/reviews/pending', [
            'methods' => 'GET',
            'callback' => [$this, 'getPendingReviewsApi'],
            'permission_callback' => function() {
                return current_user_can('manage_myprotector');
            },
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/admin/reviews/batch-approve', [
            'methods' => 'POST',
            'callback' => [$this, 'batchApproveApi'],
            'permission_callback' => function() {
                return current_user_can('manage_myprotector');
            },
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/admin/reviews/batch-reject', [
            'methods' => 'POST',
            'callback' => [$this, 'batchRejectApi'],
            'permission_callback' => function() {
                return current_user_can('manage_myprotector');
            },
        ]);
        
        // Business response routes
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/reviews/(?P<review_id>\d+)/responses', [
            'methods' => 'POST',
            'callback' => [$this, 'createResponseApi'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/admin/responses/(?P<id>\d+)/approve', [
            'methods' => 'POST',
            'callback' => [$this, 'approveResponseApi'],
            'permission_callback' => function() {
                return current_user_can('manage_myprotector');
            },
        ]);
        
        $this->registerApiRoute(MYPROTECTOR_API_NAMESPACE, '/admin/responses/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'deleteResponseApi'],
            'permission_callback' => function() {
                return current_user_can('manage_myprotector');
            },
        ]);
    }

    /**
     * Register services
     * 
     * @return void
     */
    protected function registerServices(): void {
        // Review service
        $this->registerService('reviews.service', new Services\ReviewService(
            $this->plugin()->getContainer()
        ));
        
        // Moderation service
        $this->registerService('reviews.moderation', new Services\ReviewModerationService(
            $this->plugin()->getContainer()
        ));
        
        // Analytics service
        $this->registerService('reviews.analytics', new Services\ReviewAnalyticsService(
            $this->plugin()->getContainer()
        ));
    }

    /**
     * Initialize handlers
     * 
     * @return void
     */
    protected function initHandlers(): void {
        // Admin handlers
        if (is_admin()) {
            $this->adminController = new Admin\ReviewsAdminController($this);
        }
        
        // Public handlers
        $this->publicController = new Public\ReviewsPublicController($this);
    }

    /**
     * Setup AJAX endpoints
     * 
     * @return void
     */
    protected function setupAjaxEndpoints(): void {
        // Additional AJAX endpoints can be registered here
    }

    /**
     * Handle review submission
     * 
     * @return void
     */
    public function handleReviewSubmission(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mp_submit_review')) {
            wp_send_json_error(['message' => __('Security check failed.', 'myprotector-platform')]);
        }

        // Get service
        $service = $this->getService('reviews.service');
        
        // Validate and create review
        $result = $service->create($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Review submitted successfully!', 'myprotector-platform'),
            'review_id' => $result,
        ]);
    }

    /**
     * Handle mark helpful
     * 
     * @return void
     */
    public function handleMarkHelpful(): void {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mp_mark_helpful')) {
            wp_send_json_error(['message' => __('Security check failed.', 'myprotector-platform')]);
        }

        $service = $this->getService('reviews.service');
        $result = $service->markHelpful($_POST['review_id'] ?? 0);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Marked as helpful!', 'myprotector-platform'),
            'count' => $result,
        ]);
    }

    /**
     * Handle report review
     * 
     * @return void
     */
    public function handleReportReview(): void {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mp_report_review')) {
            wp_send_json_error(['message' => __('Security check failed.', 'myprotector-platform')]);
        }

        $service = $this->getService('reviews.service');
        $result = $service->reportReview($_POST['review_id'] ?? 0, $_POST['reason'] ?? '');
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Report submitted. Thank you!', 'myprotector-platform'),
        ]);
    }

    /**
     * Filter review content
     * 
     * @param string $content
     * @return string
     */
    public function filterReviewContent(string $content): string {
        if (!is_singular('mp_review')) {
            return $content;
        }

        // Add review metadata
        $reviewId = get_queried_object_id();
        $rating = get_post_meta($reviewId, '_mp_rating', true);
        
        if ($rating) {
            $stars = str_repeat('⭐', (int) $rating);
            $content = '<div class="mp-review-rating">' . $stars . '</div>' . $content;
        }

        return $content;
    }

    /**
     * Register post type
     * 
     * @return void
     */
    public function registerPostType(): void {
        register_post_type('mp_review', [
            'labels' => [
                'name' => __('Reviews', 'myprotector-platform'),
                'singular_name' => __('Review', 'myprotector-platform'),
                'add_new' => __('Write Review', 'myprotector-platform'),
                'add_new_item' => __('Write New Review', 'myprotector-platform'),
                'edit_item' => __('Edit Review', 'myprotector-platform'),
                'new_item' => __('New Review', 'myprotector-platform'),
                'view_item' => __('View Review', 'myprotector-platform'),
                'search_items' => __('Search Reviews', 'myprotector-platform'),
                'not_found' => __('No reviews found', 'myprotector-platform'),
            ],
            'public' => true,
            'has_archive' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => get_option('mp_review_slug_base', 'reviews')],
            'supports' => ['title', 'editor', 'author', 'custom-fields', 'comments'],
            'menu_icon' => 'dashicons-star-filled',
        ]);
    }

    /**
     * Handle post status change
     * 
     * @param string $new_status
     * @param string $old_status
     * @param \WP_Post $post
     * @return void
     */
    public function handleStatusChange(string $new_status, string $old_status, \WP_Post $post): void {
        if ($post->post_type !== 'mp_review') {
            return;
        }

        // Notify on approval
        if ($new_status === 'publish' && $old_status !== 'publish') {
            do_action('mp_review_approved', $post->ID);
        }
        
        // Notify on rejection
        if ($new_status === 'trash' && $old_status !== 'trash') {
            do_action('mp_review_rejected', $post->ID);
        }
    }

    /**
     * REST API - Get reviews
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function getReviewsApi(\WP_REST_Request $request): \WP_REST_Response {
        $service = $this->getService('reviews.service');
        
        $args = [
            'company_id' => $request->get_param('company_id'),
            'status' => 'approved',
            'limit' => $request->get_param('per_page') ?? 10,
            'page' => $request->get_param('page') ?? 1,
        ];
        
        $reviews = $service->getReviews($args);
        
        return new \WP_REST_Response([
            'success' => true,
            'data' => $reviews,
        ], 200);
    }

    /**
     * REST API - Create review
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function createReviewApi(\WP_REST_Request $request): \WP_REST_Response {
        $service = $this->getService('reviews.service');
        
        $data = [
            'company_id' => $request->get_param('company_id'),
            'user_id' => get_current_user_id(),
            'rating' => $request->get_param('rating'),
            'title' => $request->get_param('title'),
            'content' => $request->get_param('content'),
        ];
        
        $result = $service->create($data);
        
        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }
        
        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Review submitted successfully!', 'myprotector-platform'),
            'review_id' => $result,
        ], 201);
    }

    /**
     * REST API - Get single review
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function getReviewApi(\WP_REST_Request $request): \WP_REST_Response {
        $service = $this->getService('reviews.service');
        $review = $service->getReview($request->get_param('id'));

        if (!$review) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Review not found.', 'myprotector-platform'),
            ], 404);
        }

        return new \WP_REST_Response([
            'success' => true,
            'data' => $review,
        ], 200);
    }

    /**
     * REST API - Mark as helpful
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function markHelpfulApi(\WP_REST_Request $request): \WP_REST_Response {
        $service = $this->getService('reviews.service');
        $result = $service->markHelpful($request->get_param('id'));

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'count' => $result,
        ], 200);
    }

    /**
     * REST API - Report review
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function reportReviewApi(\WP_REST_Request $request): \WP_REST_Response {
        $service = $this->getService('reviews.service');
        $result = $service->reportReview(
            $request->get_param('id'),
            get_current_user_id(),
            $request->get_param('reason') ?? ''
        );

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Report submitted.', 'myprotector-platform'),
        ], 200);
    }

    /**
     * REST API - Upload images
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function uploadImagesApi(\WP_REST_Request $request): \WP_REST_Response {
        $service = $this->getService('reviews.service');
        
        // Handle file upload from request
        $images = [];
        if (!empty($_FILES['images'])) {
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                $images[] = [
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i],
                    'error' => $_FILES['images']['error'][$i],
                    'size' => $_FILES['images']['size'][$i],
                ];
            }
        }

        $result = $service->addImage($request->get_param('id'), [
            'file' => base64_encode(file_get_contents('php://input')),
            'type' => 'image/jpeg',
        ]);

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'image_id' => $result,
        ], 201);
    }

    /**
     * REST API - Get company reviews
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function getCompanyReviewsApi(\WP_REST_Request $request): \WP_REST_Response {
        $service = $this->getService('reviews.service');
        
        $args = [
            'company_id' => $request->get_param('company_id'),
            'status' => 'approved',
            'limit' => $request->get_param('per_page') ?? 10,
            'page' => $request->get_param('page') ?? 1,
        ];

        $reviews = $service->getReviews($args);

        return new \WP_REST_Response([
            'success' => true,
            'data' => $reviews,
        ], 200);
    }

    /**
     * REST API - Get company review stats
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function getCompanyReviewStatsApi(\WP_REST_Request $request): \WP_REST_Response {
        $service = $this->getService('reviews.service');
        $stats = $service->getCompanyStats($request->get_param('company_id'));

        return new \WP_REST_Response([
            'success' => true,
            'data' => $stats,
        ], 200);
    }

    /**
     * REST API - Admin approve review
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function adminApproveApi(\WP_REST_Request $request): \WP_REST_Response {
        $moderation = new \MyProtector\Modules\Reviews\Services\ReviewModerationService();
        $result = $moderation->approve($request->get_param('id'));

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Review approved.', 'myprotector-platform'),
        ], 200);
    }

    /**
     * REST API - Admin reject review
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function adminRejectApi(\WP_REST_Request $request): \WP_REST_Response {
        $moderation = new \MyProtector\Modules\Reviews\Services\ReviewModerationService();
        $result = $moderation->reject(
            $request->get_param('id'),
            $request->get_param('reason') ?? ''
        );

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Review rejected.', 'myprotector-platform'),
        ], 200);
    }

    /**
     * REST API - Admin update review
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function adminUpdateApi(\WP_REST_Request $request): \WP_REST_Response {
        $moderation = new \MyProtector\Modules\Reviews\Services\ReviewModerationService();
        
        $data = [
            'rating' => $request->get_param('rating'),
            'title' => $request->get_param('title'),
            'content' => $request->get_param('content'),
            'status' => $request->get_param('status'),
            'trust_level' => $request->get_param('trust_level'),
        ];

        $result = $moderation->edit($request->get_param('id'), $data);

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Review updated.', 'myprotector-platform'),
        ], 200);
    }

    /**
     * REST API - Admin delete review
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function adminDeleteApi(\WP_REST_Request $request): \WP_REST_Response {
        $service = $this->getService('reviews.service');
        $result = $service->delete($request->get_param('id'));

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Review deleted.', 'myprotector-platform'),
        ], 200);
    }

    /**
     * REST API - Get pending reviews
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function getPendingReviewsApi(\WP_REST_Request $request): \WP_REST_Response {
        $moderation = new \MyProtector\Modules\Reviews\Services\ReviewModerationService();
        $reviews = $moderation->getPendingReviews([
            'limit' => $request->get_param('per_page') ?? 20,
            'page' => $request->get_param('page') ?? 1,
        ]);

        return new \WP_REST_Response([
            'success' => true,
            'data' => $reviews,
        ], 200);
    }

    /**
     * REST API - Batch approve
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function batchApproveApi(\WP_REST_Request $request): \WP_REST_Response {
        $moderation = new \MyProtector\Modules\Reviews\Services\ReviewModerationService();
        $reviewIds = $request->get_param('review_ids') ?? [];
        
        $result = $moderation->batchApprove($reviewIds);

        return new \WP_REST_Response([
            'success' => true,
            'approved' => $result['approved'],
            'failed' => $result['failed'],
        ], 200);
    }

    /**
     * REST API - Batch reject
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function batchRejectApi(\WP_REST_Request $request): \WP_REST_Response {
        $moderation = new \MyProtector\Modules\Reviews\Services\ReviewModerationService();
        $reviewIds = $request->get_param('review_ids') ?? [];
        $reason = $request->get_param('reason') ?? '';
        
        $result = $moderation->batchReject($reviewIds, $reason);

        return new \WP_REST_Response([
            'success' => true,
            'rejected' => $result['rejected'],
            'failed' => $result['failed'],
        ], 200);
    }

    /**
     * REST API - Create business response
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function createResponseApi(\WP_REST_Request $request): \WP_REST_Response {
        global $wpdb;
        
        $reviewId = $request->get_param('review_id');
        $review = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT company_id FROM {$wpdb->prefix}mp_reviews WHERE review_id = %d",
                $reviewId
            ),
            ARRAY_A
        );

        if (!$review) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => __('Review not found.', 'myprotector-platform'),
            ], 404);
        }

        $model = new \MyProtector\Modules\Reviews\Models\ReviewResponseModel();
        $result = $model->create([
            'review_id' => $reviewId,
            'company_id' => $review['company_id'],
            'user_id' => get_current_user_id(),
            'content' => $request->get_param('content'),
        ]);

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'response_id' => $result,
        ], 201);
    }

    /**
     * REST API - Approve response
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function approveResponseApi(\WP_REST_Request $request): \WP_REST_Response {
        $moderation = new \MyProtector\Modules\Reviews\Services\ReviewModerationService();
        $result = $moderation->approveResponse($request->get_param('id'));

        if (is_wp_error($result)) {
            return new \WP_REST_Response([
                'success' => false,
                'message' => $result->get_error_message(),
            ], 400);
        }

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Response approved.', 'myprotector-platform'),
        ], 200);
    }

    /**
     * REST API - Delete response
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function deleteResponseApi(\WP_REST_Request $request): \WP_REST_Response {
        $model = new \MyProtector\Modules\Reviews\Models\ReviewResponseModel();
        $result = $model->delete($request->get_param('id'));

        return new \WP_REST_Response([
            'success' => true,
            'message' => __('Response deleted.', 'myprotector-platform'),
        ], 200);
    }

    /**
     * Handle admin reject (AJAX)
     * 
     * @return void
     */
    public function handleAdminReject(): void {
        check_ajax_referer('mp_reviews_admin', 'nonce');

        $reviewId = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        if (!$reviewId) {
            wp_send_json_error(['message' => __('Invalid review ID.', 'myprotector-platform')]);
        }

        if (empty($reason)) {
            wp_send_json_error(['message' => __('Rejection reason is required.', 'myprotector-platform')]);
        }

        $moderation = new \MyProtector\Modules\Reviews\Services\ReviewModerationService();
        $result = $moderation->reject($reviewId, $reason);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => __('Review rejected.', 'myprotector-platform')]);
    }

    /**
     * Handle admin update (AJAX)
     * 
     * @return void
     */
    public function handleAdminUpdate(): void {
        check_ajax_referer('mp_reviews_admin', 'nonce');

        $reviewId = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;

        if (!$reviewId) {
            wp_send_json_error(['message' => __('Invalid review ID.', 'myprotector-platform')]);
        }

        $moderation = new \MyProtector\Modules\Reviews\Services\ReviewModerationService();
        
        $data = [
            'rating' => isset($_POST['rating']) ? (int) $_POST['rating'] : null,
            'title' => isset($_POST['title']) ? sanitize_text_field($_POST['title']) : null,
            'content' => isset($_POST['content']) ? wp_kses_post($_POST['content']) : null,
            'status' => isset($_POST['status']) ? sanitize_text_field($_POST['status']) : null,
            'trust_level' => isset($_POST['trust_level']) ? sanitize_text_field($_POST['trust_level']) : null,
        ];

        $data = array_filter($data, fn($v) => $v !== null);

        $result = $moderation->edit($reviewId, $data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => __('Review updated.', 'myprotector-platform')]);
    }

    /**
     * Handle admin action (AJAX)
     * 
     * @return void
     */
    public function handleAdminAction(): void {
        check_ajax_referer('mp_reviews_admin', 'nonce');

        $reviewId = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;
        $action = isset($_POST['review_action']) ? sanitize_text_field($_POST['review_action']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        if (!$reviewId || !$action) {
            wp_send_json_error(['message' => __('Invalid request.', 'myprotector-platform')]);
        }

        $service = $this->getService('reviews.service');
        $moderation = new \MyProtector\Modules\Reviews\Services\ReviewModerationService();

        switch ($action) {
            case 'approve':
                $result = $moderation->approve($reviewId);
                break;
            case 'reject':
                $result = $moderation->reject($reviewId, $reason);
                break;
            case 'delete':
                $result = $service->delete($reviewId);
                break;
            case 'flag':
                $model = new \MyProtector\Modules\Reviews\Models\ReviewModel();
                $result = $model->updateStatus($reviewId, 'flagged');
                break;
            case 'unapprove':
                $model = new \MyProtector\Modules\Reviews\Models\ReviewModel();
                $result = $model->updateStatus($reviewId, 'pending');
                break;
            default:
                wp_send_json_error(['message' => __('Unknown action.', 'myprotector-platform')]);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => __('Action completed.', 'myprotector-platform')]);
    }
}