<?php
/**
 * Reviews Admin - Edit Review Page Template
 * 
 * @package MyProtector\Modules\Reviews\templates\admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$review = $review ?? [];
$images = $images ?? [];
$response = $response ?? null;
$history = $history ?? [];
?>

<div class="wrap mp-review-edit">
    <h1>
        <?php _e('Edit Review', 'myprotector-platform'); ?>
        <a href="<?php echo esc_url(admin_url('admin.php?page=mp-reviews')); ?>" class="page-title-action"><?php _e('← Back to List', 'myprotector-platform'); ?></a>
    </h1>

    <div class="mp-edit-layout">
        <!-- Main Content -->
        <div class="mp-edit-main">
            <!-- Review Content -->
            <div class="mp-review-box">
                <div class="mp-review-header">
                    <div class="mp-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="mp-star <?php echo $i <= $review['review_rating'] ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="mp-status-badge mp-status-<?php echo esc_attr($review['review_status']); ?>">
                        <?php echo esc_html(ucfirst($review['review_status'])); ?>
                    </span>
                    <?php if ($review['verified_purchase']): ?>
                        <span class="mp-verified-badge">✓ <?php _e('Verified Purchase', 'myprotector-platform'); ?></span>
                    <?php endif; ?>
                </div>

                <h2 class="mp-review-title"><?php echo esc_html($review['review_title']); ?></h2>
                
                <div class="mp-review-meta">
                    <span><?php echo esc_html($review['user_name'] ?? 'Unknown'); ?></span>
                    <span>•</span>
                    <span><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($review['created_at']))); ?></span>
                </div>

                <div class="mp-review-content">
                    <?php echo wp_kses_post($review['review_content']); ?>
                </div>

                <!-- Images -->
                <?php if (!empty($images)): ?>
                    <div class="mp-review-images">
                        <h4><?php _e('Review Images', 'myprotector-platform'); ?></h4>
                        <div class="mp-images-grid">
                            <?php foreach ($images as $image): ?>
                                <div class="mp-image-item <?php echo $image['is_approved'] ? 'approved' : 'pending'; ?>">
                                    <img src="<?php echo esc_url($image['image_url']); ?>" alt="<?php esc_attr_e('Review image', 'myprotector-platform'); ?>">
                                    <?php if (!$image['is_approved']): ?>
                                        <span class="mp-image-status"><?php _e('Pending', 'myprotector-platform'); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Business Response -->
                <?php if ($response): ?>
                    <div class="mp-review-response">
                        <h4><?php _e('Business Response', 'myprotector-platform'); ?></h4>
                        <div class="mp-response-content">
                            <p><?php echo wp_kses_post($response['response_content']); ?></p>
                            <small><?php echo esc_html($response['responder_name'] ?? 'Business'); ?> - <?php echo esc_html(wp_date(get_option('date_format'), strtotime($response['created_at']))); ?></small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Edit Form -->
            <?php if ($review['review_status'] !== 'deleted'): ?>
                <div class="mp-edit-form">
                    <h3><?php _e('Edit Review', 'myprotector-platform'); ?></h3>
                    <form id="mp-edit-review-form">
                        <input type="hidden" name="action" value="mp_reviews_update">
                        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('mp_reviews_admin')); ?>">
                        <input type="hidden" name="review_id" value="<?php echo esc_attr($review['review_id']); ?>">

                        <p>
                            <label for="edit_rating"><?php _e('Rating', 'myprotector-platform'); ?></label>
                            <select name="rating" id="edit_rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php selected($review['review_rating'], $i); ?>><?php echo str_repeat('★', $i); ?></option>
                                <?php endfor; ?>
                            </select>
                        </p>

                        <p>
                            <label for="edit_title"><?php _e('Title', 'myprotector-platform'); ?></label>
                            <input type="text" name="title" id="edit_title" value="<?php echo esc_attr($review['review_title']); ?>" class="widefat">
                        </p>

                        <p>
                            <label for="edit_content"><?php _e('Content', 'myprotector-platform'); ?></label>
                            <textarea name="content" id="edit_content" rows="8" class="widefat"><?php echo esc_textarea(strip_tags($review['review_content'])); ?></textarea>
                        </p>

                        <p>
                            <label for="edit_status"><?php _e('Status', 'myprotector-platform'); ?></label>
                            <select name="status" id="edit_status">
                                <option value="pending" <?php selected($review['review_status'], 'pending'); ?>><?php _e('Pending', 'myprotector-platform'); ?></option>
                                <option value="approved" <?php selected($review['review_status'], 'approved'); ?>><?php _e('Approved', 'myprotector-platform'); ?></option>
                                <option value="rejected" <?php selected($review['review_status'], 'rejected'); ?>><?php _e('Rejected', 'myprotector-platform'); ?></option>
                                <option value="flagged" <?php selected($review['review_status'], 'flagged'); ?>><?php _e('Flagged', 'myprotector-platform'); ?></option>
                            </select>
                        </p>

                        <p>
                            <label for="edit_trust_level"><?php _e('Trust Level', 'myprotector-platform'); ?></label>
                            <select name="trust_level" id="edit_trust_level">
                                <option value="unverified" <?php selected($review['trust_level'], 'unverified'); ?>><?php _e('Unverified', 'myprotector-platform'); ?></option>
                                <option value="verified" <?php selected($review['trust_level'], 'verified'); ?>><?php _e('Verified', 'myprotector-platform'); ?></option>
                                <option value="premium" <?php selected($review['trust_level'], 'premium'); ?>><?php _e('Premium', 'myprotector-platform'); ?></option>
                                <option value="trusted" <?php selected($review['trust_level'], 'trusted'); ?>><?php _e('Trusted', 'myprotector-platform'); ?></option>
                            </select>
                        </p>

                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Save Changes', 'myprotector-platform'); ?></button>
                        </p>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="mp-edit-sidebar">
            <!-- Quick Actions -->
            <div class="mp-sidebar-box">
                <h3><?php _e('Quick Actions', 'myprotector-platform'); ?></h3>
                
                <?php if ($review['review_status'] === 'pending'): ?>
                    <button type="button" class="button button-primary button-block mp-action-btn" data-action="approve" data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                        <?php _e('Approve Review', 'myprotector-platform'); ?>
                    </button>
                    <button type="button" class="button button-secondary button-block mp-action-btn" data-action="reject" data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                        <?php _e('Reject Review', 'myprotector-platform'); ?>
                    </button>
                <?php elseif ($review['review_status'] === 'approved'): ?>
                    <button type="button" class="button button-secondary button-block mp-action-btn" data-action="unapprove" data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                        <?php _e('Move to Pending', 'myprotector-platform'); ?>
                    </button>
                <?php endif; ?>

                <button type="button" class="button button-secondary button-block mp-action-btn" data-action="flag" data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                    <?php _e('Flag Review', 'myprotector-platform'); ?>
                </button>

                <button type="button" class="button button-secondary button-block mp-action-btn mp-delete-btn" data-action="delete" data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                    <?php _e('Delete Review', 'myprotector-platform'); ?>
                </button>
            </div>

            <!-- Review Info -->
            <div class="mp-sidebar-box">
                <h3><?php _e('Review Info', 'myprotector-platform'); ?></h3>
                <table class="mp-info-table">
                    <tr>
                        <td><?php _e('Company', 'myprotector-platform'); ?></td>
                        <td><a href="<?php echo esc_url(home_url('/company/' . $review['company_slug'])); ?>" target="_blank"><?php echo esc_html($review['company_name']); ?></a></td>
                    </tr>
                    <tr>
                        <td><?php _e('Reviewer', 'myprotector-platform'); ?></td>
                        <td><?php echo esc_html($review['user_name']); ?> (<?php echo esc_html($review['user_email']); ?>)</td>
                    </tr>
                    <tr>
                        <td><?php _e('Submitted', 'myprotector-platform'); ?></td>
                        <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($review['created_at']))); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('IP Address', 'myprotector-platform'); ?></td>
                        <td><?php echo esc_html($review['ip_address']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Helpful', 'myprotector-platform'); ?></td>
                        <td><?php echo esc_html($review['helpful_count']); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Reports', 'myprotector-platform'); ?></td>
                        <td><?php echo esc_html($review['report_count']); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Moderation History -->
            <?php if (!empty($history)): ?>
                <div class="mp-sidebar-box">
                    <h3><?php _e('Moderation History', 'myprotector-platform'); ?></h3>
                    <ul class="mp-history-list">
                        <?php foreach ($history as $entry): ?>
                            <li>
                                <strong><?php echo esc_html(ucfirst($entry['action'])); ?></strong>
                                <?php if ($entry['notes']): ?>
                                    - <?php echo esc_html($entry['notes']); ?>
                                <?php endif; ?>
                                <br>
                                <small>
                                    <?php echo esc_html($entry['moderator_name'] ?? 'System'); ?> • 
                                    <?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry['created_at']))); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.mp-review-edit .mp-edit-layout { display: grid; grid-template-columns: 1fr 300px; gap: 20px; }
.mp-review-edit .mp-edit-main { display: flex; flex-direction: column; gap: 20px; }
.mp-review-edit .mp-edit-sidebar { display: flex; flex-direction: column; gap: 20px; }

.mp-review-edit .mp-review-box { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
.mp-review-edit .mp-review-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
.mp-review-edit .mp-rating { font-size: 20px; color: #ddd; }
.mp-review-edit .mp-rating .filled { color: #f0ad4e; }
.mp-review-edit .mp-review-title { font-size: 20px; margin: 10px 0; }
.mp-review-edit .mp-review-meta { color: #666; font-size: 13px; margin-bottom: 15px; }
.mp-review-edit .mp-review-meta span { margin: 0 5px; }
.mp-review-edit .mp-review-content { line-height: 1.7; }

.mp-review-edit .mp-verified-badge { background: #28a745; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 11px; }

.mp-review-edit .mp-review-images { margin-top: 20px; }
.mp-review-edit .mp-review-images h4 { margin-bottom: 10px; }
.mp-review-edit .mp-images-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px; }
.mp-review-edit .mp-image-item { position: relative; border-radius: 4px; overflow: hidden; }
.mp-review-edit .mp-image-item img { width: 100%; height: 100px; object-fit: cover; }
.mp-review-edit .mp-image-item.pending { border: 2px solid #ffc107; }
.mp-review-edit .mp-image-status { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: #fff; font-size: 11px; text-align: center; padding: 3px; }

.mp-review-edit .mp-review-response { margin-top: 20px; padding: 15px; background: #f0f7ff; border-radius: 8px; }
.mp-review-edit .mp-review-response h4 { margin-bottom: 10px; }
.mp-review-edit .mp-response-content small { color: #666; }

.mp-review-edit .mp-edit-form { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
.mp-review-edit .mp-edit-form h3 { margin-top: 0; }
.mp-review-edit .mp-edit-form label { font-weight: bold; display: block; margin-bottom: 5px; }
.mp-review-edit .mp-edit-form input, .mp-review-edit .mp-edit-form select, .mp-review-edit .mp-edit-form textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }

.mp-review-edit .mp-sidebar-box { background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
.mp-review-edit .mp-sidebar-box h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.mp-review-edit .mp-sidebar-box .button-block { width: 100%; margin-bottom: 10px; }
.mp-review-edit .mp-delete-btn { border-color: #dc3545; color: #dc3545; }
.mp-review-edit .mp-delete-btn:hover { background: #dc3545; color: #fff; }

.mp-review-edit .mp-info-table { width: 100%; }
.mp-review-edit .mp-info-table td { padding: 8px 0; border-bottom: 1px solid #eee; }
.mp-review-edit .mp-info-table td:first-child { color: #666; width: 40%; }

.mp-review-edit .mp-history-list { list-style: none; padding: 0; margin: 0; }
.mp-review-edit .mp-history-list li { padding: 10px 0; border-bottom: 1px solid #eee; font-size: 13px; }
.mp-review-edit .mp-history-list li:last-child { border-bottom: none; }
</style>

<script>
jQuery(document).ready(function($) {
    // Edit form submit
    $('#mp-edit-review-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: mpReviewsAdmin.ajaxUrl,
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    alert('<?php esc_attr_e('Review updated successfully.', 'myprotector-platform'); ?>');
                } else {
                    alert(response.data.message);
                }
            }
        });
    });

    // Quick actions
    $('.mp-action-btn').on('click', function() {
        const action = $(this).data('action');
        const reviewId = $(this).data('review-id');
        let reason = '';

        if (action === 'reject') {
            reason = prompt('<?php esc_attr_e('Enter rejection reason:', 'myprotector-platform'); ?>');
            if (!reason) return;
        }

        if (action === 'delete') {
            if (!confirm('<?php esc_attr_e('Are you sure you want to delete this review?', 'myprotector-platform'); ?>')) {
                return;
            }
        }

        $.ajax({
            url: mpReviewsAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mp_reviews_action',
                nonce: mpReviewsAdmin.nonce,
                review_id: reviewId,
                review_action: action,
                reason: reason
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
});
</script>