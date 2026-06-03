<?php
/**
 * MyProtector Platform - Reviews Public Controller
 * 
 * Public-facing review functionality
 * 
 * @package MyProtector\Modules\Reviews\Public
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews\Public_;

use MyProtector\Modules\Reviews\Reviews;
use MyProtector\Modules\Reviews\Services\ReviewService;
use MyProtector\Modules\Reviews\Services\ReviewVerificationService;
use MyProtector\Modules\Reviews\Validators\ReviewValidator;

class ReviewsPublicController {
    /**
     * Module reference
     * 
     * @var Reviews
     */
    protected Reviews $module;

    /**
     * Review service
     * 
     * @var ReviewService
     */
    protected ReviewService $reviewService;

    /**
     * Verification service
     * 
     * @var ReviewVerificationService
     */
    protected ReviewVerificationService $verificationService;

    /**
     * Validator
     * 
     * @var ReviewValidator
     */
    protected ReviewValidator $validator;

    /**
     * Constructor
     * 
     * @param Reviews $module
     */
    public function __construct(Reviews $module) {
        $this->module = $module;
        $this->reviewService = new ReviewService();
        $this->verificationService = new ReviewVerificationService();
        $this->validator = new ReviewValidator();

        $this->registerShortcodes();
        $this->registerAjaxHandlers();
    }

    /**
     * Register shortcodes
     * 
     * @return void
     */
    protected function registerShortcodes(): void {
        add_shortcode('mp_review_form', [$this, 'renderReviewForm']);
        add_shortcode('mp_reviews_list', [$this, 'renderReviewsList']);
        add_shortcode('mp_review_summary', [$this, 'renderReviewSummary']);
    }

    /**
     * Register AJAX handlers
     * 
     * @return void
     */
    protected function registerAjaxHandlers(): void {
        add_action('wp_ajax_mp_submit_review', [$this, 'handleSubmitReview']);
        add_action('wp_ajax_nopriv_mp_submit_review', [$this, 'handleSubmitReview']);
        add_action('wp_ajax_mp_mark_helpful', [$this, 'handleMarkHelpful']);
        add_action('wp_ajax_nopriv_mp_mark_helpful', [$this, 'handleMarkHelpful']);
        add_action('wp_ajax_mp_report_review', [$this, 'handleReportReview']);
        add_action('wp_ajax_nopriv_mp_report_review', [$this, 'handleReportReview']);
    }

    /**
     * Render review submission form
     * 
     * @param array $atts
     * @return string
     */
    public function renderReviewForm(array $atts = []): string {
        $atts = shortcode_atts([
            'company_id' => 0,
            'show_rating' => 'true',
            'show_images' => 'true',
        ], $atts, 'mp_review_form');

        $companyId = (int) $atts['company_id'];

        if (!$companyId) {
            global $post;
            if ($post && get_post_type($post) === 'mp_company') {
                $companyId = $post->ID;
            }
        }

        if (!$companyId) {
            return '<p class="mp-error">' . __('Company ID required.', 'myprotector-platform') . '</p>';
        }

        // Check if user can review
        if (!is_user_logged_in()) {
            return '<div class="mp-login-required">' . 
                   '<p>' . __('Please log in to leave a review.', 'myprotector-platform') . '</p>' .
                   '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="mp-login-btn">' . 
                   __('Log In', 'myprotector-platform') . '</a></div>';
        }

        // Check if already reviewed
        $userId = get_current_user_id();
        $hasReviewed = $this->reviewService->getReviews([
            'company_id' => $companyId,
            'user_id' => $userId,
            'status' => 'any',
        ]);

        if (!empty($hasReviewed)) {
            return '<p class="mp-already-reviewed">' . 
                   __('You have already reviewed this business.', 'myprotector-platform') . '</p>';
        }

        // Nonce for form
        $nonce = wp_create_nonce('mp_review_submission_' . $companyId);

        ob_start();
        include $this->module->getPath('templates/public/review-form.php');
        return ob_get_clean();
    }

    /**
     * Render reviews list
     * 
     * @param array $atts
     * @return string
     */
    public function renderReviewsList(array $atts = []): string {
        $atts = shortcode_atts([
            'company_id' => 0,
            'limit' => 10,
            'show_images' => 'true',
            'show_responses' => 'true',
            'sort' => 'newest',
        ], $atts, 'mp_reviews_list');

        $companyId = (int) $atts['company_id'];

        if (!$companyId) {
            global $post;
            if ($post && get_post_type($post) === 'mp_company') {
                $companyId = $post->ID;
            }
        }

        if (!$companyId) {
            return '<p class="mp-error">' . __('Company ID required.', 'myprotector-platform') . '</p>';
        }

        $args = [
            'company_id' => $companyId,
            'status' => 'approved',
            'limit' => (int) $atts['limit'],
        ];

        switch ($atts['sort']) {
            case 'oldest':
                $args['orderby'] = 'created_at';
                $args['order'] = 'ASC';
                break;
            case 'highest':
                $args['orderby'] = 'review_rating';
                $args['order'] = 'DESC';
                break;
            case 'lowest':
                $args['orderby'] = 'review_rating';
                $args['order'] = 'ASC';
                break;
            case 'helpful':
                $args['orderby'] = 'helpful_count';
                $args['order'] = 'DESC';
                break;
            default:
                $args['orderby'] = 'created_at';
                $args['order'] = 'DESC';
        }

        $reviews = $this->reviewService->getReviews($args);
        $nonce = wp_create_nonce('mp_reviews_public');

        ob_start();
        include $this->module->getPath('templates/public/reviews-list.php');
        return ob_get_clean();
    }

    /**
     * Render review summary
     * 
     * @param array $atts
     * @return string
     */
    public function renderReviewSummary(array $atts = []): string {
        $atts = shortcode_atts([
            'company_id' => 0,
        ], $atts, 'mp_review_summary');

        $companyId = (int) $atts['company_id'];

        if (!$companyId) {
            global $post;
            if ($post && get_post_type($post) === 'mp_company') {
                $companyId = $post->ID;
            }
        }

        if (!$companyId) {
            return '';
        }

        $stats = $this->reviewService->getCompanyStats($companyId);

        ob_start();
        include $this->module->getPath('templates/public/review-summary.php');
        return ob_get_clean();
    }

    /**
     * Handle review submission (AJAX)
     * 
     * @return void
     */
    public function handleSubmitReview(): void {
        // Verify nonce
        $companyId = isset($_POST['company_id']) ? (int) $_POST['company_id'] : 0;
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

        if (!wp_verify_nonce($nonce, 'mp_review_submission_' . $companyId)) {
            wp_send_json_error(['message' => __('Security check failed.', 'myprotector-platform')]);
        }

        // Check login
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Please log in to submit a review.', 'myprotector-platform')]);
        }

        // Validate
        $validation = $this->validator->validateSubmission($_POST);
        if (is_wp_error($validation)) {
            wp_send_json_error(['message' => $validation->get_error_message()]);
        }

        // Create review
        $data = [
            'company_id' => $companyId,
            'user_id' => get_current_user_id(),
            'rating' => isset($_POST['rating']) ? (int) $_POST['rating'] : 0,
            'title' => isset($_POST['review_title']) ? sanitize_text_field($_POST['review_title']) : '',
            'content' => isset($_POST['review_content']) ? wp_kses_post($_POST['review_content']) : '',
        ];

        // Handle images
        if (!empty($_POST['images']) && is_array($_POST['images'])) {
            $data['images'] = $_POST['images'];
        }

        $result = $this->reviewService->create($data);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        // Increment rate limit
        $this->validator->incrementRateLimit(get_current_user_id());

        wp_send_json_success([
            'message' => __('Thank you! Your review has been submitted and is pending approval.', 'myprotector-platform'),
            'review_id' => $result,
        ]);
    }

    /**
     * Handle mark helpful (AJAX)
     * 
     * @return void
     */
    public function handleMarkHelpful(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

        if (!wp_verify_nonce($nonce, 'mp_reviews_public')) {
            wp_send_json_error(['message' => __('Security check failed.', 'myprotector-platform')]);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Please log in to mark reviews as helpful.', 'myprotector-platform')]);
        }

        $reviewId = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;

        if (!$reviewId) {
            wp_send_json_error(['message' => __('Invalid review.', 'myprotector-platform')]);
        }

        $result = $this->reviewService->markHelpful($reviewId);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Marked as helpful!', 'myprotector-platform'),
            'count' => $result,
        ]);
    }

    /**
     * Handle report review (AJAX)
     * 
     * @return void
     */
    public function handleReportReview(): void {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

        if (!wp_verify_nonce($nonce, 'mp_reviews_public')) {
            wp_send_json_error(['message' => __('Security check failed.', 'myprotector-platform')]);
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Please log in to report reviews.', 'myprotector-platform')]);
        }

        $reviewId = isset($_POST['review_id']) ? (int) $_POST['review_id'] : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        if (!$reviewId) {
            wp_send_json_error(['message' => __('Invalid review.', 'myprotector-platform')]);
        }

        if (empty($reason)) {
            wp_send_json_error(['message' => __('Please provide a reason.', 'myprotector-platform')]);
        }

        $result = $this->reviewService->reportReview($reviewId, get_current_user_id(), $reason);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Thank you for your report.', 'myprotector-platform'),
        ]);
    }

    /**
     * Render star rating input
     * 
     * @param string $name
     * @param int $selected
     * @return string
     */
    public function renderStarInput(string $name, int $selected = 0): string {
        $html = '<div class="mp-star-input" data-name="' . esc_attr($name) . '">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= '<span class="mp-star' . ($i <= $selected ? ' selected' : '') . '" data-value="' . $i . '">★</span>';
        }
        $html .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($selected) . '">';
        $html .= '</div>';
        return $html;
    }
}