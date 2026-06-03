<?php
/**
 * Reviews List Template (Public)
 * 
 * @package MyProtector\Modules\Reviews\templates\public
 */

if (!defined('ABSPATH')) {
    exit;
}

$reviews = $reviews ?? [];
$showImages = ($show_images ?? 'true') === 'true';
$showResponses = ($show_responses ?? 'true') === 'true';
$nonce = $nonce ?? '';
?>

<div class="mp-reviews-list-container">
    <?php if (empty($reviews)): ?>
        <p class="mp-no-reviews"><?php _e('No reviews yet. Be the first to leave a review!', 'myprotector-platform'); ?></p>
    <?php else: ?>
        <div class="mp-reviews-list">
            <?php foreach ($reviews as $review): ?>
                <article class="mp-review-item" data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                    <!-- Review Header -->
                    <div class="mp-review-header">
                        <div class="mp-reviewer-info">
                            <div class="mp-reviewer-avatar">
                                <?php echo esc_html(substr($review['user_name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <div class="mp-reviewer-details">
                                <span class="mp-reviewer-name"><?php echo esc_html($review['user_name'] ?? 'Anonymous'); ?></span>
                                <?php if ($review['trust_level'] !== 'unverified'): ?>
                                    <span class="mp-trust-badge mp-trust-<?php echo esc_attr($review['trust_level']); ?>">
                                        <?php echo esc_html(ucfirst($review['trust_level'])); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($review['verified_purchase']): ?>
                                    <span class="mp-verified-badge">✓ <?php _e('Verified', 'myprotector-platform'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mp-review-meta">
                            <span class="mp-review-date"><?php echo esc_html(wp_date('M d, Y', strtotime($review['created_at']))); ?></span>
                        </div>
                    </div>

                    <!-- Star Rating -->
                    <div class="mp-review-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="mp-star <?php echo $i <= $review['review_rating'] ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>

                    <!-- Review Title -->
                    <h3 class="mp-review-title"><?php echo esc_html($review['review_title']); ?></h3>

                    <!-- Review Content -->
                    <div class="mp-review-content">
                        <?php echo wp_kses_post(wp_trim_words($review['review_content'], 60)); ?>
                        <?php if (strlen(strip_tags($review['review_content'])) > 300): ?>
                            <button class="mp-read-more-btn" data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                                <?php _e('Read more', 'myprotector-platform'); ?>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Images -->
                    <?php if ($showImages && !empty($review['images'])): ?>
                        <div class="mp-review-images">
                            <?php foreach ($review['images'] as $image): ?>
                                <div class="mp-review-image">
                                    <img src="<?php echo esc_url($image['image_url']); ?>" alt="<?php esc_attr_e('Review image', 'myprotector-platform'); ?>" loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Business Response -->
                    <?php if ($showResponses && !empty($review['response'])): ?>
                        <div class="mp-business-response">
                            <div class="mp-response-header">
                                <strong><?php echo esc_html($review['response']['responder_name'] ?? 'Business'); ?></strong>
                                <span class="mp-response-date"><?php echo esc_html(wp_date('M d, Y', strtotime($review['response']['created_at']))); ?></span>
                            </div>
                            <div class="mp-response-content">
                                <?php echo wp_kses_post($review['response']['response_content']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="mp-review-actions">
                        <button class="mp-helpful-btn" data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                            <span class="mp-helpful-icon">👍</span>
                            <span class="mp-helpful-text"><?php _e('Helpful', 'myprotector-platform'); ?></span>
                            <span class="mp-helpful-count">(<?php echo esc_html($review['helpful_count']); ?>)</span>
                        </button>
                        <button class="mp-report-btn" data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                            <?php _e('Report', 'myprotector-platform'); ?>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.mp-reviews-list-container {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.mp-reviews-list-container .mp-no-reviews {
    text-align: center;
    color: #666;
    padding: 40px;
}

.mp-reviews-list {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.mp-review-item {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
}

.mp-review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.mp-reviewer-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mp-reviewer-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #0073aa;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

.mp-reviewer-details {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.mp-reviewer-name {
    font-weight: 600;
    color: #333;
}

.mp-trust-badge {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 3px;
    background: #28a745;
    color: #fff;
}

.mp-trust-premium { background: #f0ad4e; }
.mp-trust-trusted { background: #17a2b8; }

.mp-verified-badge {
    font-size: 11px;
    color: #28a745;
    font-weight: 500;
}

.mp-review-date {
    font-size: 12px;
    color: #999;
}

.mp-review-rating {
    margin-bottom: 8px;
}

.mp-review-rating .mp-star {
    font-size: 18px;
    color: #ddd;
}

.mp-review-rating .mp-star.filled {
    color: #f0ad4e;
}

.mp-review-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 10px 0;
    color: #333;
}

.mp-review-content {
    line-height: 1.6;
    color: #555;
}

.mp-review-content p {
    margin: 0 0 10px 0;
}

.mp-read-more-btn {
    background: none;
    border: none;
    color: #0073aa;
    cursor: pointer;
    padding: 0;
    font-size: 14px;
}

.mp-read-more-btn:hover {
    text-decoration: underline;
}

.mp-review-images {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.mp-review-image {
    width: 100px;
    height: 100px;
    border-radius: 6px;
    overflow: hidden;
}

.mp-review-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
}

.mp-business-response {
    margin-top: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-left: 3px solid #28a745;
    border-radius: 0 6px 6px 0;
}

.mp-response-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.mp-response-header strong {
    color: #333;
}

.mp-response-date {
    font-size: 12px;
    color: #999;
}

.mp-response-content {
    line-height: 1.5;
    color: #555;
}

.mp-review-actions {
    display: flex;
    gap: 15px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.mp-helpful-btn,
.mp-report-btn {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 13px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 4px;
    transition: all 0.2s;
}

.mp-helpful-btn:hover,
.mp-report-btn:hover {
    background: #f5f5f5;
}

.mp-report-btn:hover {
    color: #dc3545;
}
</style>

<script>
(function($) {
    'use strict';

    // Mark helpful
    $('.mp-helpful-btn').on('click', function() {
        const btn = $(this);
        const reviewId = btn.data('review-id');

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'mp_mark_helpful',
                nonce: '<?php echo esc_attr($nonce); ?>',
                review_id: reviewId
            },
            success: function(response) {
                if (response.success) {
                    btn.find('.mp-helpful-count').text('(' + response.data.count + ')');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Report
    $('.mp-report-btn').on('click', function() {
        const reviewId = $(this).data('review-id');
        const reason = prompt('<?php esc_attr_e('Why are you reporting this review?', 'myprotector-platform'); ?>');

        if (!reason) return;

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'mp_report_review',
                nonce: '<?php echo esc_attr($nonce); ?>',
                review_id: reviewId,
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php esc_attr_e('Thank you for your report.', 'myprotector-platform'); ?>');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

})(jQuery);
</script>