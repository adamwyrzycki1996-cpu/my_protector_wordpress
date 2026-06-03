<?php
/**
 * MyProtector Platform - Review Image Model
 * 
 * Database operations for review images
 * 
 * @package MyProtector\Modules\Reviews\Models
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews\Models;

class ReviewImageModel {
    /**
     * Database table name
     * 
     * @var string
     */
    protected $table;

    /**
     * Allowed image MIME types
     * 
     * @var array
     */
    protected $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    /**
     * Maximum file size (5MB)
     * 
     * @var int
     */
    protected $maxFileSize = 5 * 1024 * 1024;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'mp_review_images';
    }

    /**
     * Add image to review
     * 
     * @param int $reviewId
     * @param array $imageData
     * @return int|\WP_Error
     */
    public function add(int $reviewId, array $imageData): int|\WP_Error {
        global $wpdb;

        // Validate file data
        $validation = $this->validateImageData($imageData);
        if (is_wp_error($validation)) {
            return $validation;
        }

        // Process and save image
        $result = $this->processAndSaveImage($reviewId, $imageData);
        if (is_wp_error($result)) {
            return $result;
        }

        $result = $wpdb->insert(
            $this->table,
            [
                'review_id' => $reviewId,
                'image_url' => $result['url'],
                'image_path' => $result['path'],
                'image_type' => $imageData['image_type'] ?? 'review',
                'caption' => sanitize_text_field($imageData['caption'] ?? ''),
                'is_approved' => 0,
                'uploaded_by' => $imageData['uploaded_by'] ?? get_current_user_id(),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
        );

        if ($result === false) {
            // Clean up uploaded file on failure
            if (file_exists($result['path'])) {
                @unlink($result['path']);
            }
            return new \WP_Error('db_error', __('Failed to save image record.', 'myprotector-platform'));
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Get images for a review
     * 
     * @param int $reviewId
     * @param bool $approvedOnly
     * @return array
     */
    public function getByReview(int $reviewId, bool $approvedOnly = true): array {
        global $wpdb;
        
        $where = 'review_id = %d';
        $params = [$reviewId];

        if ($approvedOnly) {
            $where .= ' AND is_approved = 1';
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE {$where} ORDER BY created_at ASC",
                $params
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Get image by ID
     * 
     * @param int $imageId
     * @return array|null
     */
    public function getById(int $imageId): ?array {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE image_id = %d",
                $imageId
            ),
            ARRAY_A
        );
    }

    /**
     * Approve image
     * 
     * @param int $imageId
     * @return bool
     */
    public function approve(int $imageId): bool {
        global $wpdb;
        
        return $wpdb->update(
            $this->table,
            ['is_approved' => 1],
            ['image_id' => $imageId],
            ['%d'],
            ['%d']
        ) !== false;
    }

    /**
     * Reject/delete image
     * 
     * @param int $imageId
     * @return bool
     */
    public function reject(int $imageId): bool {
        global $wpdb;
        
        // Get image path for file deletion
        $image = $this->getById($imageId);
        
        if ($image && !empty($image['image_path']) && file_exists($image['image_path'])) {
            @unlink($image['image_path']);
        }

        return $wpdb->delete($this->table, ['image_id' => $imageId], ['%d']) !== false;
    }

    /**
     * Delete all images for a review
     * 
     * @param int $reviewId
     * @return bool
     */
    public function deleteByReview(int $reviewId): bool {
        global $wpdb;
        
        // Get all images to delete files
        $images = $this->getByReview($reviewId, false);
        
        foreach ($images as $image) {
            if (!empty($image['image_path']) && file_exists($image['image_path'])) {
                @unlink($image['image_path']);
            }
        }

        return $wpdb->delete($this->table, ['review_id' => $reviewId], ['%d']) !== false;
    }

    /**
     * Count images for a review
     * 
     * @param int $reviewId
     * @param bool $approvedOnly
     * @return int
     */
    public function countByReview(int $reviewId, bool $approvedOnly = true): int {
        global $wpdb;
        
        $where = 'review_id = %d';
        $params = [$reviewId];

        if ($approvedOnly) {
            $where .= ' AND is_approved = 1';
        }

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$where}", $params)
        );
    }

    /**
     * Validate image data
     * 
     * @param array $data
     * @return true|\WP_Error
     */
    protected function validateImageData(array $data): true|\WP_Error {
        // Check if file data is provided
        if (empty($data['file']) && empty($data['url'])) {
            return new \WP_Error('no_file', __('No image file provided.', 'myprotector-platform'));
        }

        // Validate file size
        if (!empty($data['size']) && $data['size'] > $this->maxFileSize) {
            return new \WP_Error('file_too_large', __('Image exceeds maximum size of 5MB.', 'myprotector-platform'));
        }

        // Validate image type
        if (!empty($data['type']) && !in_array($data['type'], $this->allowedMimes)) {
            return new \WP_Error('invalid_type', __('Invalid image type. Allowed: JPG, PNG, GIF, WebP.', 'myprotector-platform'));
        }

        // Validate review ID
        if (empty($data['review_id']) || (int) $data['review_id'] <= 0) {
            return new \WP_Error('invalid_review', __('Invalid review ID.', 'myprotector-platform'));
        }

        return true;
    }

    /**
     * Process and save image
     * 
     * @param int $reviewId
     * @param array $imageData
     * @return array|\WP_Error
     */
    protected function processAndSaveImage(int $reviewId, array $imageData): array|\WP_Error {
        $uploadDir = wp_upload_dir();
        $reviewsDir = $uploadDir['basedir'] . '/mp-reviews';

        // Create directory if not exists
        if (!file_exists($reviewsDir)) {
            wp_mkdir_p($reviewsDir);
        }

        // Generate unique filename
        $extension = !empty($imageData['type']) ? $this->getExtensionFromMime($imageData['type']) : 'jpg';
        $filename = 'review_' . $reviewId . '_' . time() . '_' . wp_generate_uuid4() . '.' . $extension;
        $filepath = $reviewsDir . '/' . $filename;

        // Handle file data
        if (!empty($imageData['file']) && is_string($imageData['file'])) {
            // Base64 encoded image data
            $decoded = base64_decode($imageData['file']);
            if ($decoded === false) {
                return new \WP_Error('decode_error', __('Failed to decode image.', 'myprotector-platform'));
            }

            // Check directory writable
            if (!is_writable($reviewsDir)) {
                return new \WP_Error('dir_not_writable', __('Upload directory not writable.', 'myprotector-platform'));
            }

            $saved = file_put_contents($filepath, $decoded);
            if ($saved === false) {
                return new \WP_Error('save_error', __('Failed to save image.', 'myprotector-platform'));
            }
        } elseif (!empty($imageData['tmp_name'])) {
            // Uploaded file
            if (!move_uploaded_file($imageData['tmp_name'], $filepath)) {
                return new \WP_Error('upload_error', __('Failed to move uploaded file.', 'myprotector-platform'));
            }
        } elseif (!empty($imageData['url'])) {
            // URL - download image
            $response = wp_remote_get($imageData['url'], [
                'timeout' => 30,
                'stream' => true,
                'filename' => $filepath,
            ]);

            if (is_wp_error($response)) {
                return $response;
            }
        }

        // Verify the saved file is a valid image
        $imageSize = @getimagesize($filepath);
        if ($imageSize === false) {
            @unlink($filepath);
            return new \WP_Error('invalid_image', __('Uploaded file is not a valid image.', 'myprotector-platform'));
        }

        // Generate thumbnail
        $this->generateThumbnail($filepath);

        return [
            'path' => $filepath,
            'url' => str_replace($uploadDir['basedir'], $uploadDir['baseurl'], $filepath),
        ];
    }

    /**
     * Generate thumbnail
     * 
     * @param string $filepath
     * @return bool
     */
    protected function generateThumbnail(string $filepath): bool {
        $uploadDir = wp_upload_dir();
        $thumbDir = $uploadDir['basedir'] . '/mp-reviews/thumbs';
        
        if (!file_exists($thumbDir)) {
            wp_mkdir_p($thumbDir);
        }

        $thumbFilename = basename($filepath, '.' . pathinfo($filepath, PATHINFO_EXTENSION)) . '_thumb.jpg';
        $thumbPath = $thumbDir . '/' . $thumbFilename;

        $image = wp_get_image_editor($filepath);
        if (is_wp_error($image)) {
            return false;
        }

        $image->resize(300, 300, false);
        $result = $image->save($thumbPath, 'image/jpeg');

        return !is_wp_error($result);
    }

    /**
     * Get file extension from MIME type
     * 
     * @param string $mime
     * @return string
     */
    protected function getExtensionFromMime(string $mime): string {
        $map = array_flip($this->allowedMimes);
        return $map[$mime] ?? 'jpg';
    }
}