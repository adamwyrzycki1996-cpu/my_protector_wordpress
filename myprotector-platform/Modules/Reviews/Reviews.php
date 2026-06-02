<?php
/**
 * MyProtector - Reviews Module
 * 
 * @package MyProtector\Modules\Reviews
 */

namespace MyProtector\Modules\Reviews;

use MyProtector\Core\Module;

class Reviews extends Module {
    protected $name = 'reviews';

    protected function getModuleDirectory(): string {
        return 'Reviews';
    }

    public function boot(): void {
        // Initialize review functionality
    }

    public function registerHooks(): void {
        // Review submission
        add_action('wp_ajax_submit_review', [$this, 'handleReviewSubmission']);
        add_action('wp_ajax_nopriv_submit_review', [$this, 'handleReviewSubmission']);
        
        // Helpful marking
        add_action('wp_ajax_mark_helpful', [$this, 'handleMarkHelpful']);
        
        // Report review
        add_action('wp_ajax_report_review', [$this, 'handleReportReview']);
    }

    /**
     * Handle review submission
     */
    public function handleReviewSubmission(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mp_submit_review')) {
            wp_send_json_error(['message' => __('Security check failed.', 'myprotector-platform')]);
        }

        wp_send_json_success([
            'message' => __('Review submitted successfully!', 'myprotector-platform'),
        ]);
    }

    /**
     * Handle mark helpful
     */
    public function handleMarkHelpful(): void {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mp_mark_helpful')) {
            wp_send_json_error(['message' => __('Security check failed.', 'myprotector-platform')]);
        }

        wp_send_json_success([
            'message' => __('Marked as helpful!', 'myprotector-platform'),
        ]);
    }

    /**
     * Handle report review
     */
    public function handleReportReview(): void {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mp_report_review')) {
            wp_send_json_error(['message' => __('Security check failed.', 'myprotector-platform')]);
        }

        wp_send_json_success([
            'message' => __('Report submitted. Thank you!', 'myprotector-platform'),
        ]);
    }
}