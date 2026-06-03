<?php
/**
 * Reviews Admin - List Page Template
 * 
 * @package MyProtector\Modules\Reviews\templates\admin
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap mp-reviews-admin">
    <h1 class="wp-heading-inline"><?php _e('Reviews', 'myprotector-platform'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=mp-reviews-analytics')); ?>" class="page-title-action"><?php _e('Analytics', 'myprotector-platform'); ?></a>
    <hr class="wp-header-end">

    <!-- Stats Cards -->
    <div class="mp-reviews-stats">
        <div class="mp-stat-card mp-stat-pending">
            <div class="mp-stat-icon">⏳</div>
            <div class="mp-stat-content">
                <div class="mp-stat-number"><?php echo esc_html($stats['pending']); ?></div>
                <div class="mp-stat-label"><?php _e('Pending', 'myprotector-platform'); ?></div>
            </div>
        </div>
        <div class="mp-stat-card mp-stat-flagged">
            <div class="mp-stat-icon">🚩</div>
            <div class="mp-stat-content">
                <div class="mp-stat-number"><?php echo esc_html($stats['flagged']); ?></div>
                <div class="mp-stat-label"><?php _e('Flagged', 'myprotector-platform'); ?></div>
            </div>
        </div>
        <div class="mp-stat-card mp-stat-approved">
            <div class="mp-stat-icon">✓</div>
            <div class="mp-stat-content">
                <div class="mp-stat-number"><?php echo esc_html($stats['approved_today']); ?></div>
                <div class="mp-stat-label"><?php _e('Approved Today', 'myprotector-platform'); ?></div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <form method="get" class="mp-reviews-filters">
        <input type="hidden" name="page" value="mp-reviews">
        
        <select name="status" id="status-filter">
            <option value=""><?php _e('All Statuses', 'myprotector-platform'); ?></option>
            <option value="pending" <?php selected($statusFilter, 'pending'); ?>><?php _e('Pending', 'myprotector-platform'); ?></option>
            <option value="approved" <?php selected($statusFilter, 'approved'); ?>><?php _e('Approved', 'myprotector-platform'); ?></option>
            <option value="rejected" <?php selected($statusFilter, 'rejected'); ?>><?php _e('Rejected', 'myprotector-platform'); ?></option>
            <option value="flagged" <?php selected($statusFilter, 'flagged'); ?>><?php _e('Flagged', 'myprotector-platform'); ?></option>
        </select>

        <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search reviews...', 'myprotector-platform'); ?>">
        
        <?php submit_button(__('Filter', 'myprotector-platform'), 'secondary', 'filter_action', false); ?>
    </form>

    <!-- Bulk Actions -->
    <form id="mp-reviews-bulk-form" method="post">
        <div class="mp-bulk-actions">
            <select id="bulk-action-selector" name="bulk_action">
                <option value=""><?php _e('Bulk Actions', 'myprotector-platform'); ?></option>
                <option value="approve"><?php _e('Approve', 'myprotector-platform'); ?></option>
                <option value="reject"><?php _e('Reject', 'myprotector-platform'); ?></option>
            </select>
            <button type="button" class="button mp-bulk-apply-btn"><?php _e('Apply', 'myprotector-platform'); ?></button>
        </div>

        <!-- Reviews Table -->
        <table class="wp-list-table widefat fixed striped mp-reviews-table">
            <thead>
                <tr>
                    <th class="mp-check-column"><input type="checkbox" id="mp-select-all"></th>
                    <th class="mp-column-company"><?php _e('Company', 'myprotector-platform'); ?></th>
                    <th class="mp-column-reviewer"><?php _e('Reviewer', 'myprotector-platform'); ?></th>
                    <th class="mp-column-rating"><?php _e('Rating', 'myprotector-platform'); ?></th>
                    <th class="mp-column-title"><?php _e('Title', 'myprotector-platform'); ?></th>
                    <th class="mp-column-status"><?php _e('Status', 'myprotector-platform'); ?></th>
                    <th class="mp-column-date"><?php _e('Date', 'myprotector-platform'); ?></th>
                    <th class="mp-column-actions"><?php _e('Actions', 'myprotector-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr>
                        <td colspan="8" class="mp-no-items">
                            <?php _e('No reviews found.', 'myprotector-platform'); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                            <td class="mp-check-column">
                                <input type="checkbox" name="review_ids[]" value="<?php echo esc_attr($review['review_id']); ?>">
                            </td>
                            <td class="mp-column-company">
                                <strong>
                                    <a href="<?php echo esc_url(home_url('/company/' . $review['company_slug'])); ?>" target="_blank">
                                        <?php echo esc_html($review['company_name'] ?? 'Unknown'); ?>
                                    </a>
                                </strong>
                            </td>
                            <td class="mp-column-reviewer">
                                <strong><?php echo esc_html($review['user_name'] ?? 'Unknown'); ?></strong>
                                <?php if ($review['verified_purchase']): ?>
                                    <span class="mp-verified-badge" title="<?php esc_attr_e('Verified Purchase', 'myprotector-platform'); ?>">✓</span>
                                <?php endif; ?>
                            </td>
                            <td class="mp-column-rating">
                                <div class="mp-star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="mp-star <?php echo $i <= $review['review_rating'] ? 'filled' : ''; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td class="mp-column-title">
                                <span class="mp-review-title"><?php echo esc_html(wp_trim_words($review['review_title'], 6)); ?></span>
                                <?php if ($review['image_count'] > 0): ?>
                                    <span class="mp-images-indicator" title="<?php printf(esc_attr('%d images', 'myprotector-platform'), $review['image_count']); ?>">📷</span>
                                <?php endif; ?>
                                <?php if (!empty($review['has_response'])): ?>
                                    <span class="mp-responded-indicator" title="<?php esc_attr_e('Business responded', 'myprotector-platform'); ?>">💬</span>
                                <?php endif; ?>
                            </td>
                            <td class="mp-column-status">
                                <span class="mp-status-badge mp-status-<?php echo esc_attr($review['review_status']); ?>">
                                    <?php echo esc_html(ucfirst($review['review_status'])); ?>
                                </span>
                            </td>
                            <td class="mp-column-date">
                                <?php echo esc_html(wp_date(get_option('date_format'), strtotime($review['created_at']))); ?>
                            </td>
                            <td class="mp-column-actions">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=mp-reviews-edit&review_id=' . $review['review_id'])); ?>" class="button button-small">
                                    <?php _e('View', 'myprotector-platform'); ?>
                                </a>
                                <?php if ($review['review_status'] === 'pending'): ?>
                                    <button type="button" class="button button-small button-primary mp-approve-btn" data-review-id="<?php echo esc_attr($review['review_id']); ?>">
                                        <?php _e('Approve', 'myprotector-platform'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<style>
.mp-reviews-admin .mp-reviews-stats { display: flex; gap: 20px; margin: 20px 0; }
.mp-reviews-admin .mp-stat-card { 
    background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; 
    padding: 15px 20px; display: flex; align-items: center; gap: 15px;
}
.mp-reviews-admin .mp-stat-icon { font-size: 24px; }
.mp-reviews-admin .mp-stat-number { font-size: 24px; font-weight: bold; }
.mp-reviews-admin .mp-stat-label { color: #666; font-size: 12px; }

.mp-reviews-admin .mp-reviews-filters { margin: 20px 0; display: flex; gap: 10px; }
.mp-reviews-admin .mp-reviews-filters select, .mp-reviews-filters input { padding: 8px; }

.mp-reviews-admin .mp-bulk-actions { margin: 15px 0; display: flex; gap: 10px; }

.mp-reviews-admin .mp-check-column { width: 40px; text-align: center; }
.mp-reviews-admin .mp-column-company { width: 150px; }
.mp-reviews-admin .mp-column-rating { width: 120px; }

.mp-reviews-admin .mp-star-rating { color: #ddd; font-size: 16px; }
.mp-reviews-admin .mp-star.filled { color: #f0ad4e; }

.mp-reviews-admin .mp-verified-badge { color: #28a745; font-weight: bold; margin-left: 5px; }
.mp-reviews-admin .mp-images-indicator, .mp-reviews-admin .mp-responded-indicator { margin-left: 5px; }

.mp-reviews-admin .mp-status-badge {
    display: inline-block; padding: 3px 8px; border-radius: 4px; 
    font-size: 11px; font-weight: bold;
}
.mp-reviews-admin .mp-status-pending { background: #ffc107; color: #000; }
.mp-reviews-admin .mp-status-approved { background: #28a745; color: #fff; }
.mp-reviews-admin .mp-status-rejected { background: #dc3545; color: #fff; }
.mp-reviews-admin .mp-status-flagged { background: #17a2b8; color: #fff; }

.mp-reviews-admin .mp-no-items { text-align: center; padding: 40px; color: #666; }
</style>

<script>
jQuery(document).ready(function($) {
    // Select all checkbox
    $('#mp-select-all').on('change', function() {
        $('input[name="review_ids[]"]').prop('checked', $(this).prop('checked'));
    });

    // Bulk apply
    $('.mp-bulk-apply-btn').on('click', function() {
        const action = $('#bulk-action-selector').val();
        const selected = $('input[name="review_ids[]"]:checked').map(function() { return $(this).val(); }).get();

        if (!action) {
            alert('<?php esc_attr_e('Please select an action.', 'myprotector-platform'); ?>');
            return;
        }

        if (selected.length === 0) {
            alert('<?php esc_attr_e('Please select reviews.', 'myprotector-platform'); ?>');
            return;
        }

        if (action === 'reject') {
            const reason = prompt('<?php esc_attr_e('Enter rejection reason:', 'myprotector-platform'); ?>');
            if (!reason) return;
            $.ajax({
                url: mpReviewsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mp_reviews_batch_reject',
                    nonce: mpReviewsAdmin.nonce,
                    review_ids: selected,
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
        } else if (action === 'approve') {
            $.ajax({
                url: mpReviewsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mp_reviews_batch_approve',
                    nonce: mpReviewsAdmin.nonce,
                    review_ids: selected
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                }
            });
        }
    });

    // Single approve
    $('.mp-approve-btn').on('click', function() {
        const reviewId = $(this).data('review-id');
        $.ajax({
            url: mpReviewsAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mp_reviews_approve',
                nonce: mpReviewsAdmin.nonce,
                review_id: reviewId
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