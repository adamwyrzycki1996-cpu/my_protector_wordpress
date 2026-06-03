<?php
/**
 * MyProtector Platform - Review Analytics Service
 * 
 * Handles review analytics and statistics
 * 
 * @package MyProtector\Modules\Reviews\Services
 * @version 1.0.0
 */

namespace MyProtector\Modules\Reviews\Services;

class ReviewAnalyticsService {
    /**
     * Get company review analytics
     * 
     * @param int $companyId
     * @return array
     */
    public function getCompanyAnalytics(int $companyId): array {
        global $wpdb;

        // Basic stats
        $totalReviews = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews 
                WHERE company_id = %d AND review_status = 'approved'",
                $companyId
            )
        );

        $avgRating = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(review_rating) FROM {$wpdb->prefix}mp_reviews 
                WHERE company_id = %d AND review_status = 'approved'",
                $companyId
            )
        );

        // Rating distribution
        $distribution = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT review_rating, COUNT(*) as count 
                FROM {$wpdb->prefix}mp_reviews 
                WHERE company_id = %d AND review_status = 'approved'
                GROUP BY review_rating
                ORDER BY review_rating DESC",
                $companyId
            ),
            ARRAY_A
        );

        $ratingDistribution = array_fill(1, 5, 0);
        foreach ($distribution as $row) {
            $ratingDistribution[(int) $row['review_rating']] = (int) $row['count'];
        }

        // Time-based stats
        $thisMonth = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews 
                WHERE company_id = %d AND review_status = 'approved' 
                AND YEAR(created_at) = YEAR(CURRENT_DATE) 
                AND MONTH(created_at) = MONTH(CURRENT_DATE)",
                $companyId
            )
        );

        $lastMonth = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews 
                WHERE company_id = %d AND review_status = 'approved' 
                AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) 
                AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH))",
                $companyId
            )
        );

        // Response rate
        $totalWithResponses = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews r
                INNER JOIN {$wpdb->prefix}mp_review_responses rr ON r.review_id = rr.review_id
                WHERE r.company_id = %d AND r.review_status = 'approved' AND rr.status = 'published'",
                $companyId
            )
        );

        // Helpful stats
        $totalHelpful = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(helpful_count) FROM {$wpdb->prefix}mp_reviews 
                WHERE company_id = %d AND review_status = 'approved'",
                $companyId
            )
        );

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => round($avgRating, 1),
            'rating_distribution' => $ratingDistribution,
            'reviews_this_month' => $thisMonth,
            'reviews_last_month' => $lastMonth,
            'monthly_growth' => $thisMonth - $lastMonth,
            'response_rate' => $totalReviews > 0 ? round(($totalWithResponses / $totalReviews) * 100, 1) : 0,
            'total_helpful_marks' => (int) $totalHelpful,
            'verified_purchases' => (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews 
                    WHERE company_id = %d AND verified_purchase = 1",
                    $companyId
                )
            ),
        ];
    }

    /**
     * Get overall platform analytics
     * 
     * @return array
     */
    public function getPlatformAnalytics(): array {
        global $wpdb;

        $totalReviews = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews WHERE review_status = 'approved'"
        );

        $avgRating = (float) $wpdb->get_var(
            "SELECT AVG(review_rating) FROM {$wpdb->prefix}mp_reviews WHERE review_status = 'approved'"
        );

        $pendingCount = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews WHERE review_status = 'pending'"
        );

        $flaggedCount = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mp_reviews WHERE review_status = 'flagged'"
        );

        // Top rated companies
        $topRated = $wpdb->get_results(
            "SELECT c.company_id, c.company_name, c.avg_rating, c.total_reviews,
                    (SELECT COUNT(*) FROM {$wpdb->prefix}mp_review_responses rr 
                     INNER JOIN {$wpdb->prefix}mp_reviews r ON rr.review_id = r.review_id 
                     WHERE r.company_id = c.company_id AND rr.status = 'published') as response_count
             FROM {$wpdb->prefix}mp_companies c
             WHERE c.total_reviews > 0 AND c.status = 'approved'
             ORDER BY c.avg_rating DESC, c.total_reviews DESC
             LIMIT 10",
            ARRAY_A
        );

        // Rating distribution
        $distribution = $wpdb->get_results(
            "SELECT review_rating, COUNT(*) as count 
             FROM {$wpdb->prefix}mp_reviews 
             WHERE review_status = 'approved'
             GROUP BY review_rating
             ORDER BY review_rating DESC",
            ARRAY_A
        );

        $ratingDistribution = array_fill(1, 5, 0);
        foreach ($distribution as $row) {
            $ratingDistribution[(int) $row['review_rating']] = (int) $row['count'];
        }

        // Recent reviews
        $recentReviews = $wpdb->get_results(
            "SELECT r.*, u.display_name as user_name, c.company_name
             FROM {$wpdb->prefix}mp_reviews r
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
             LEFT JOIN {$wpdb->prefix}mp_companies c ON r.company_id = c.company_id
             WHERE r.review_status = 'approved'
             ORDER BY r.created_at DESC
             LIMIT 5",
            ARRAY_A
        );

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => round($avgRating, 1),
            'pending_count' => $pendingCount,
            'flagged_count' => $flaggedCount,
            'rating_distribution' => $ratingDistribution,
            'top_rated_companies' => $topRated,
            'recent_reviews' => $recentReviews,
        ];
    }

    /**
     * Get review trends
     * 
     * @param int $companyId
     * @param int $days
     * @return array
     */
    public function getReviewTrends(int $companyId, int $days = 30): array {
        global $wpdb;

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(created_at) as date, 
                        COUNT(*) as reviews, 
                        AVG(review_rating) as avg_rating
                 FROM {$wpdb->prefix}mp_reviews 
                 WHERE company_id = %d 
                 AND review_status = 'approved'
                 AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL %d DAY)
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC",
                $companyId,
                $days
            ),
            ARRAY_A
        );

        return $results ?: [];
    }
}