<?php
/**
 * MyProtector Platform - Review Validator
 * 
 * Security validation for review submissions
 * 
 * @package MyProtector\Modules\Reviews\Validators
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews\Validators;

class ReviewValidator {
    /**
     * Spam patterns to detect
     * 
     * @var array
     */
    protected array $spamPatterns = [
        '/\b(buy|cheap|discount|offer|save)\b/i',
        '/\b(free|money|cash|earn)\b/i',
        '/\b(viagra|cialis|casino|lottery)\b/i',
        '/https?:\/\/[^\s]+/i',
        '/[A-Z]{10,}/',
        '/(.)\1{5,}/', // Repeated characters
    ];

    /**
     * Validate review submission
     * 
     * @param array $data
     * @return true|\WP_Error
     */
    public function validateSubmission(array $data): true|\WP_Error {
        // Check required fields
        if (empty($data['company_id'])) {
            return new \WP_Error('missing_company', __('Company is required.', 'myprotector-platform'));
        }

        if (empty($data['user_id']) && !is_user_logged_in()) {
            return new \WP_Error('login_required', __('You must be logged in to submit a review.', 'myprotector-platform'));
        }

        // Validate rating
        $rating = isset($data['rating']) ? (int) $data['rating'] : 0;
        if ($rating < 1 || $rating > 5) {
            return new \WP_Error('invalid_rating', __('Rating must be between 1 and 5.', 'myprotector-platform'));
        }

        // Validate title
        if (empty(trim($data['title'] ?? ''))) {
            return new \WP_Error('missing_title', __('Review title is required.', 'myprotector-platform'));
        }

        $title = trim($data['title']);
        if (strlen($title) < 5) {
            return new \WP_Error('short_title', __('Title must be at least 5 characters.', 'myprotector-platform'));
        }
        if (strlen($title) > 255) {
            return new \WP_Error('long_title', __('Title must be less than 255 characters.', 'myprotector-platform'));
        }

        // Validate content
        if (empty(trim($data['content'] ?? ''))) {
            return new \WP_Error('missing_content', __('Review content is required.', 'myprotector-platform'));
        }

        $content = trim($data['content']);
        $strippedContent = strip_tags($content);

        if (strlen($strippedContent) < 20) {
            return new \WP_Error('short_content', __('Review must be at least 20 characters.', 'myprotector-platform'));
        }

        if (strlen($strippedContent) > 5000) {
            return new \WP_Error('long_content', __('Review must be less than 5000 characters.', 'myprotector-platform'));
        }

        // Check for spam
        $spamCheck = $this->checkSpam($title, $content);
        if (is_wp_error($spamCheck)) {
            return $spamCheck;
        }

        // Check for banned words
        $bannedCheck = $this->checkBannedWords($content);
        if (is_wp_error($bannedCheck)) {
            return $bannedCheck;
        }

        // Validate images if provided
        if (!empty($data['images']) && is_array($data['images'])) {
            $imageValidation = $this->validateImages($data['images']);
            if (is_wp_error($imageValidation)) {
                return $imageValidation;
            }
        }

        return true;
    }

    /**
     * Validate images
     * 
     * @param array $images
     * @return true|\WP_Error
     */
    public function validateImages(array $images): true|\WP_Error {
        if (count($images) > 5) {
            return new \WP_Error('too_many_images', __('Maximum 5 images allowed.', 'myprotector-platform'));
        }

        foreach ($images as $image) {
            // Check file size
            if (!empty($image['size']) && $image['size'] > 5 * 1024 * 1024) {
                return new \WP_Error('image_too_large', __('Image exceeds maximum size of 5MB.', 'myprotector-platform'));
            }

            // Check file type
            if (!empty($image['type'])) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($image['type'], $allowedTypes)) {
                    return new \WP_Error('invalid_image_type', __('Invalid image type. Allowed: JPG, PNG, GIF, WebP.', 'myprotector-platform'));
                }
            }

            // Check for valid image data
            if (!empty($image['file']) && strlen($image['file']) > 10 * 1024 * 1024) {
                return new \WP_Error('image_too_large', __('Image data too large.', 'myprotector-platform'));
            }
        }

        return true;
    }

    /**
     * Check for spam patterns
     * 
     * @param string $title
     * @param string $content
     * @return true|\WP_Error
     */
    protected function checkSpam(string $title, string $content): true|\WP_Error {
        $text = strip_tags($content) . ' ' . $title;

        foreach ($this->spamPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return new \WP_Error(
                    'spam_detected',
                    __('Your review appears to contain spam. Please remove promotional content.', 'myprotector-platform')
                );
            }
        }

        // Check for excessive links
        $linkCount = preg_match_all('/https?:\/\/[^\s]+/i', $text);
        if ($linkCount > 2) {
            return new \WP_Error(
                'too_many_links',
                __('Too many links in review.', 'myprotector-platform')
            );
        }

        // Check for excessive caps
        $capsRatio = strlen(preg_replace('/[^A-Z]/', '', $text)) / max(1, strlen(preg_replace('/[^a-zA-Z]/', '', $text)));
        if ($capsRatio > 0.5 && strlen($text) > 50) {
            return new \WP_Error(
                'excessive_caps',
                __('Please avoid excessive capital letters.', 'myprotector-platform')
            );
        }

        return true;
    }

    /**
     * Check for banned words
     * 
     * @param string $content
     * @return true|\WP_Error
     */
    protected function checkBannedWords(string $content): true|\WP_Error {
        $stripped = strip_tags($content);
        $bannedPatterns = [
            '/\b(f+[u]+[c+]+k)\b/i',
            '/\b(s+[h]+[i]+[t]+)\b/i',
            '/\b(a+[s]+[s]+[h]+[o]+[l]+[e]+)\b/i',
            '/\b(n+[i]+[g]+[g]+[e]+[r]+)\b/i',
        ];

        foreach ($bannedPatterns as $pattern) {
            if (preg_match($pattern, $stripped)) {
                return new \WP_Error(
                    'banned_content',
                    __('Review contains inappropriate language.', 'myprotector-platform')
                );
            }
        }

        return true;
    }

    /**
     * Sanitize review content
     * 
     * @param string $content
     * @return string
     */
    public function sanitizeContent(string $content): string {
        // Allow basic HTML
        $allowed = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'em' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
        ];

        return wp_kses($content, $allowed);
    }

    /**
     * Validate response submission
     * 
     * @param array $data
     * @return true|\WP_Error
     */
    public function validateResponse(array $data): true|\WP_Error {
        if (empty($data['review_id'])) {
            return new \WP_Error('missing_review', __('Review ID is required.', 'myprotector-platform'));
        }

        if (empty($data['content'])) {
            return new \WP_Error('missing_content', __('Response content is required.', 'myprotector-platform'));
        }

        $content = trim($data['content']);
        $stripped = strip_tags($content);

        if (strlen($stripped) < 10) {
            return new \WP_Error('short_content', __('Response must be at least 10 characters.', 'myprotector-platform'));
        }

        if (strlen($stripped) > 2000) {
            return new \WP_Error('long_content', __('Response must be less than 2000 characters.', 'myprotector-platform'));
        }

        // Check for spam in response
        $spamCheck = $this->checkSpam('', $content);
        if (is_wp_error($spamCheck)) {
            return $spamCheck;
        }

        return true;
    }

    /**
     * Validate nonce for form submission
     * 
     * @param string $nonce
     * @param string $action
     * @return bool
     */
    public function validateNonce(string $nonce, string $action): bool {
        return wp_verify_nonce($nonce, $action) !== false;
    }

    /**
     * Check rate limit for user
     * 
     * @param int $userId
     * @param int $limit
     * @param int $windowSeconds
     * @return bool
     */
    public function checkRateLimit(int $userId, int $limit = 3, int $windowSeconds = 3600): bool {
        $key = 'mp_review_rate_' . $userId;
        $count = get_transient($key) ?: 0;

        return $count < $limit;
    }

    /**
     * Increment rate limit counter
     * 
     * @param int $userId
     * @param int $windowSeconds
     * @return void
     */
    public function incrementRateLimit(int $userId, int $windowSeconds = 3600): void {
        $key = 'mp_review_rate_' . $userId;
        $count = get_transient($key) ?: 0;
        set_transient($key, $count + 1, $windowSeconds);
    }

    /**
     * Validate CSRF token
     * 
     * @param string $token
     * @return bool
     */
    public function validateCsrf(string $token): bool {
        return wp_verify_nonce($token, 'mp_nonce') !== false || 
               wp_verify_nonce($token, 'mp_review_nonce') !== false;
    }

    /**
     * Check if IP is banned
     * 
     * @param string|null $ip
     * @return bool
     */
    public function isIpBanned(?string $ip = null): bool {
        global $wpdb;

        $ip = $ip ?: $this->getClientIp();

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_ip_ban_list WHERE ip_address = %s AND is_active = 1",
                $ip
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