<?php
/**
 * Review Summary Template (Public)
 * 
 * @package MyProtector\Modules\Reviews\templates\public
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = $stats ?? ['total_reviews' => 0, 'average_rating' => 0, 'rating_distribution' => []];
?>

<div class="mp-review-summary">
    <div class="mp-summary-header">
        <div class="mp-average-rating">
            <span class="mp-rating-number"><?php echo number_format($stats['average_rating'], 1); ?></span>
            <div class="mp-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="mp-star <?php echo $i <= round($stats['average_rating']) ? 'filled' : ''; ?>">★</span>
                <?php endfor; ?>
            </div>
            <span class="mp-total-count"><?php printf(_n('%d review', '%d reviews', $stats['total_reviews'], 'myprotector-platform'), $stats['total_reviews']); ?></span>
        </div>
    </div>

    <?php if (!empty($stats['rating_distribution'])): ?>
        <div class="mp-rating-bars">
            <?php foreach (array_reverse($stats['rating_distribution'], true) as $rating => $count): ?>
                <div class="mp-rating-row">
                    <span class="mp-rating-label"><?php echo $rating; ?> ★</span>
                    <div class="mp-rating-bar">
                        <?php 
                        $percentage = $stats['total_reviews'] > 0 ? ($count / $stats['total_reviews']) * 100 : 0;
                        ?>
                        <div class="mp-rating-fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                    </div>
                    <span class="mp-rating-count"><?php echo esc_html($count); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.mp-review-summary {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
    max-width: 400px;
}

.mp-review-summary .mp-summary-header {
    margin-bottom: 20px;
}

.mp-review-summary .mp-average-rating {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.mp-review-summary .mp-rating-number {
    font-size: 48px;
    font-weight: bold;
    color: #333;
}

.mp-review-summary .mp-stars {
    display: flex;
}

.mp-review-summary .mp-star {
    font-size: 20px;
    color: #ddd;
}

.mp-review-summary .mp-star.filled {
    color: #f0ad4e;
}

.mp-review-summary .mp-total-count {
    color: #666;
    font-size: 14px;
}

.mp-review-summary .mp-rating-bars {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.mp-review-summary .mp-rating-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mp-review-summary .mp-rating-label {
    width: 40px;
    font-size: 13px;
    color: #666;
}

.mp-review-summary .mp-rating-bar {
    flex: 1;
    height: 8px;
    background: #eee;
    border-radius: 4px;
    overflow: hidden;
}

.mp-review-summary .mp-rating-fill {
    height: 100%;
    background: #f0ad4e;
    transition: width 0.3s ease;
}

.mp-review-summary .mp-rating-count {
    width: 30px;
    text-align: right;
    font-size: 13px;
    color: #666;
}
</style>