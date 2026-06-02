# MyProtector Platform - MySQL Database Schema
## Stage 1 - Core Tables

**Version:** 1.0.0  
**Database:** WordPress (MySQL 8.0+)  
**Purpose:** Trustpilot-style review platform  

---

## 1. Schema Overview

### 1.1 Table List

| Table | Purpose | Est. Rows |
|-------|---------|-----------|
| `wp_mp_reviews` | Review submissions | 1M+ |
| `wp_mp_review_images` | Review image attachments | 5M+ |
| `wp_mp_businesses` | Business/company profiles | 100K |
| `wp_mp_traffic_signals` | Trust status (traffic light) | 100K |
| `wp_mp_resellers` | Partner/reseller accounts | 10K |
| `wp_mp_commissions` | Reseller commission tracking | 500K |
| `wp_mp_notifications` | User notifications | 10M+ |
| `wp_mp_email_logs` | Email sending history | 50M+ |

### 1.2 ERD Diagram (Text)

```
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│   wp_mp_reviews  │──────│ wp_mp_businesses │      │  wp_mp_resellers │
│                 │      │                 │      │                 │
│ company_id ──────┼──────│ reseller_id     │      │                 │
│ user_id ────────┼──────│                 │      │                 │
│                 │      └─────────────────┘      └────────┬────────┘
└────────┬────────┘                                       │
         │                                              │
    ┌────┴────┐                                          │
    │         │                                       ┌──┴──────┐
┌───▼────┐  ┌──▼────┐                               │ wp_mp_  │
│ wp_mp_ │  │ wp_mp_ │                               │commissions│
│review_ │  │notifi- │                               │          │
│images  │  │cations │                               └──────────┘
│        │  │        │
└────────┘  └────────┘

┌─────────────────────┐
│ wp_mp_traffic_      │
│ signals             │
│                     │
│ business_id ────────┼───► wp_mp_businesses.id
│                     │
└─────────────────────┘

┌─────────────────────┐
│ wp_mp_email_logs    │
│                     │
│ (Standalone audit)  │
└─────────────────────┘
```

---

## 2. Complete SQL Schema

### 2.1 Reviews Table

```sql
-- =====================================================
-- Table: wp_mp_reviews
-- Purpose: Store all review submissions
-- =====================================================

CREATE TABLE wp_mp_reviews (
    -- Primary Key
    review_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Foreign Keys
    business_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    
    -- Review Content
    review_title VARCHAR(255) NOT NULL,
    review_content LONGTEXT NOT NULL,
    review_rating TINYINT UNSIGNED NOT NULL CHECK (review_rating BETWEEN 1 AND 5),
    
    -- Status & Moderation
    review_status ENUM('pending', 'approved', 'rejected', 'flagged', 'spam') 
        DEFAULT 'pending',
    review_verified ENUM('unverified', 'verified', 'premium') 
        DEFAULT 'unverified',
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at DATETIME NULL,
    
    -- Review Metrics
    helpful_count INT UNSIGNED NOT NULL DEFAULT 0,
    report_count INT UNSIGNED NOT NULL DEFAULT 0,
    view_count INT UNSIGNED NOT NULL DEFAULT 0,
    
    -- AI Analysis
    ai_analyzed TINYINT(1) NOT NULL DEFAULT 0,
    ai_sentiment VARCHAR(20) NULL,
    ai_spam_score DECIMAL(5,4) NULL,
    
    -- User Agent & IP (for fraud prevention)
    user_agent VARCHAR(500) NULL,
    ip_address VARCHAR(45) NULL,
    
    -- WooCommerce Integration
    order_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NULL,
    
    -- Featured & Priority
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    priority INT UNSIGNED NOT NULL DEFAULT 0,
    
    -- Soft Delete
    deleted_at DATETIME NULL,
    
    -- Composite Indexes
    PRIMARY KEY (review_id),
    
    -- Performance Indexes
    INDEX idx_business_id (business_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (review_status),
    INDEX idx_rating (review_rating),
    INDEX idx_published_at (published_at),
    INDEX idx_created_at (created_at),
    
    -- Composite Indexes for Common Queries
    INDEX idx_business_status (business_id, review_status),
    INDEX idx_business_rating (business_id, review_rating),
    INDEX idx_status_published (review_status, published_at),
    
    -- Full-text Index for Search
    FULLTEXT INDEX idx_fulltext_content (review_title, review_content),
    
    -- Foreign Keys
    CONSTRAINT fk_reviews_business 
        FOREIGN KEY (business_id) 
        REFERENCES wp_mp_businesses(business_id) 
        ON DELETE CASCADE,
    
    CONSTRAINT fk_reviews_user 
        FOREIGN KEY (user_id) 
        REFERENCES wp_users(ID) 
        ON DELETE CASCADE

) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  AUTO_INCREMENT=1;
```

### 2.2 Review Images Table

```sql
-- =====================================================
-- Table: wp_mp_review_images
-- Purpose: Store images attached to reviews
-- =====================================================

CREATE TABLE wp_mp_review_images (
    -- Primary Key
    image_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Foreign Key
    review_id BIGINT UNSIGNED NOT NULL,
    
    -- Image Data
    image_url VARCHAR(500) NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    image_filename VARCHAR(255) NOT NULL,
    
    -- Image Metadata
    image_type ENUM('review', 'blacklist_evidence', 'business_logo', 'business_gallery') 
        DEFAULT 'review',
    mime_type VARCHAR(50) NOT NULL DEFAULT 'image/jpeg',
    file_size INT UNSIGNED NOT NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    
    -- Caption & Alt Text
    caption VARCHAR(255) NULL,
    alt_text VARCHAR(255) NULL,
    
    -- Status
    is_approved TINYINT(1) NOT NULL DEFAULT 0,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Upload Info
    uploaded_by BIGINT UNSIGNED NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- CDN & Processing
    cdn_url VARCHAR(500) NULL,
    thumbnail_url VARCHAR(500) NULL,
    processing_status ENUM('pending', 'processing', 'completed', 'failed') 
        DEFAULT 'pending',
    
    -- Soft Delete
    deleted_at DATETIME NULL,
    
    -- Indexes
    PRIMARY KEY (image_id),
    
    INDEX idx_review_id (review_id),
    INDEX idx_image_type (image_type),
    INDEX idx_uploaded_at (uploaded_at),
    INDEX idx_is_approved (is_approved),
    
    -- Foreign Key
    CONSTRAINT fk_review_images_review 
        FOREIGN KEY (review_id) 
        REFERENCES wp_mp_reviews(review_id) 
        ON DELETE CASCADE

) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC;
```

### 2.3 Businesses Table

```sql
-- =====================================================
-- Table: wp_mp_businesses
-- Purpose: Store business/company profiles
-- =====================================================

CREATE TABLE wp_mp_businesses (
    -- Primary Key
    business_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Foreign Keys
    user_id BIGINT UNSIGNED NULL,
    reseller_id BIGINT UNSIGNED NULL,
    category_id BIGINT UNSIGNED NULL,
    
    -- Basic Info
    business_name VARCHAR(255) NOT NULL,
    business_slug VARCHAR(255) NOT NULL,
    business_description LONGTEXT NULL,
    business_tagline VARCHAR(255) NULL,
    
    -- Contact Info
    business_email VARCHAR(255) NULL,
    business_phone VARCHAR(50) NULL,
    business_website VARCHAR(500) NULL,
    
    -- Address
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    country VARCHAR(2) NOT NULL DEFAULT 'US',
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    
    -- Verification
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    verified_at DATETIME NULL,
    verified_by BIGINT UNSIGNED NULL,
    
    -- Status
    business_status ENUM('pending', 'active', 'suspended', 'closed', 'archived') 
        DEFAULT 'pending',
    claim_status ENUM('unclaimed', 'claimed', 'verified') 
        DEFAULT 'unclaimed',
    
    -- Trust & Metrics
    total_reviews INT UNSIGNED NOT NULL DEFAULT 0,
    approved_reviews INT UNSIGNED NOT NULL DEFAULT 0,
    avg_rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    total_rating_sum INT UNSIGNED NOT NULL DEFAULT 0,
    response_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    avg_response_time INT UNSIGNED NULL COMMENT 'in hours',
    
    -- Logo & Branding
    logo_url VARCHAR(500) NULL,
    cover_image_url VARCHAR(500) NULL,
    brand_color VARCHAR(7) NULL,
    
    -- Business Documents (for Trust)
    insurance_name VARCHAR(255) NULL,
    insurance_url VARCHAR(500) NULL,
    terms_url VARCHAR(500) NULL,
    promise_page_url VARCHAR(500) NULL,
    promise_page_title VARCHAR(255) NULL,
    
    -- Social Media
    facebook_url VARCHAR(500) NULL,
    twitter_url VARCHAR(500) NULL,
    instagram_url VARCHAR(500) NULL,
    linkedin_url VARCHAR(500) NULL,
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    first_review_at DATETIME NULL,
    last_review_at DATETIME NULL,
    
    -- WooCommerce
    woocommerce_id BIGINT UNSIGNED NULL,
    woocommerce_shop_name VARCHAR(255) NULL,
    
    -- Featured
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    featured_until DATETIME NULL,
    
    -- Soft Delete
    deleted_at DATETIME NULL,
    
    -- Indexes
    PRIMARY KEY (business_id),
    UNIQUE KEY uk_business_slug (business_slug),
    UNIQUE KEY uk_business_email (business_email),
    
    INDEX idx_user_id (user_id),
    INDEX idx_reseller_id (reseller_id),
    INDEX idx_category_id (category_id),
    INDEX idx_business_status (business_status),
    INDEX idx_claim_status (claim_status),
    INDEX idx_is_verified (is_verified),
    INDEX idx_is_featured (is_featured),
    INDEX idx_avg_rating (avg_rating),
    INDEX idx_total_reviews (total_reviews),
    INDEX idx_created_at (created_at),
    INDEX idx_country (country),
    INDEX idx_city (city),
    
    -- Full-text for Search
    FULLTEXT INDEX idx_fulltext_search (business_name, business_description, business_tagline),
    
    -- Foreign Keys
    CONSTRAINT fk_businesses_user 
        FOREIGN KEY (user_id) 
        REFERENCES wp_users(ID) 
        ON DELETE SET NULL,
    
    CONSTRAINT fk_businesses_reseller 
        FOREIGN KEY (reseller_id) 
        REFERENCES wp_mp_resellers(reseller_id) 
        ON DELETE SET NULL,
    
    CONSTRAINT fk_businesses_category 
        FOREIGN KEY (category_id) 
        REFERENCES wp_terms(term_id) 
        ON DELETE SET NULL

) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC;
```

### 2.4 Traffic Signals Table

```sql
-- =====================================================
-- Table: wp_mp_traffic_signals
-- Purpose: Store trust status and traffic light data
-- =====================================================

CREATE TABLE wp_mp_traffic_signals (
    -- Primary Key
    signal_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Foreign Key (One-to-One with businesses)
    business_id BIGINT UNSIGNED NOT NULL,
    
    -- Trust Status
    trust_status ENUM('walking', 'shopping', 'bad') NOT NULL DEFAULT 'bad',
    traffic_light_color ENUM('green', 'yellow', 'red') NOT NULL DEFAULT 'red',
    
    -- Trust Score (0-100)
    trust_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    trust_score_breakdown JSON NULL COMMENT 'Score breakdown by factor',
    
    -- Requirements Met
    requirements_met JSON NULL COMMENT 'List of requirements fulfilled',
    requirements_total INT UNSIGNED NOT NULL DEFAULT 5,
    requirements_fulfilled INT UNSIGNED NOT NULL DEFAULT 0,
    
    -- Individual Requirements
    has_min_reviews TINYINT(1) NOT NULL DEFAULT 0,
    has_min_rating TINYINT(1) NOT NULL DEFAULT 0,
    has_verified_domain TINYINT(1) NOT NULL DEFAULT 0,
    has_insurance TINYINT(1) NOT NULL DEFAULT 0,
    has_terms TINYINT(1) NOT NULL DEFAULT 0,
    has_promise_page TINYINT(1) NOT NULL DEFAULT 0,
    has_active_subscription TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Auto vs Manual
    is_auto_calculated TINYINT(1) NOT NULL DEFAULT 1,
    
    -- Manual Override
    manual_override TINYINT(1) NOT NULL DEFAULT 0,
    override_reason TEXT NULL,
    override_by BIGINT UNSIGNED NULL,
    override_at DATETIME NULL,
    
    -- Calculations
    last_calculated_at DATETIME NULL,
    calculation_data JSON NULL COMMENT 'Full calculation data',
    
    -- Status Reasons
    status_reasons JSON NULL COMMENT 'Why current status',
    improvement_tips JSON NULL COMMENT 'How to improve',
    
    -- Public Badge
    show_traffic_light TINYINT(1) NOT NULL DEFAULT 1,
    badge_style ENUM('standard', 'compact', 'badge_only') NOT NULL DEFAULT 'standard',
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    PRIMARY KEY (signal_id),
    UNIQUE KEY uk_business_id (business_id),
    
    INDEX idx_trust_status (trust_status),
    INDEX idx_traffic_light_color (traffic_light_color),
    INDEX idx_trust_score (trust_score),
    INDEX idx_is_auto (is_auto_calculated),
    INDEX idx_manual_override (manual_override),
    
    -- Foreign Key
    CONSTRAINT fk_traffic_signals_business 
        FOREIGN KEY (business_id) 
        REFERENCES wp_mp_businesses(business_id) 
        ON DELETE CASCADE

) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC;

-- Create trigger for auto-update on business insert
DELIMITER //

CREATE TRIGGER tr_business_after_insert
AFTER INSERT ON wp_mp_businesses
FOR EACH ROW
BEGIN
    INSERT INTO wp_mp_traffic_signals (business_id, trust_status, traffic_light_color)
    VALUES (NEW.business_id, 'bad', 'red');
END//

DELIMITER ;
```

### 2.5 Resellers Table

```sql
-- =====================================================
-- Table: wp_mp_resellers
-- Purpose: Store reseller/partner accounts
-- =====================================================

CREATE TABLE wp_mp_resellers (
    -- Primary Key
    reseller_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Foreign Key
    user_id BIGINT UNSIGNED NOT NULL,
    
    -- Business Info
    company_name VARCHAR(255) NULL,
    company_url VARCHAR(500) NULL,
    
    -- Referral Code (Unique)
    referral_code VARCHAR(50) NOT NULL,
    
    -- Commission Settings
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    commission_tier ENUM('standard', 'silver', 'gold', 'platinum') NOT NULL DEFAULT 'standard',
    custom_commission_rates JSON NULL COMMENT 'Custom rates per product',
    
    -- Stats
    total_referrals INT UNSIGNED NOT NULL DEFAULT 0,
    total_conversions INT UNSIGNED NOT NULL DEFAULT 0,
    conversion_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    
    -- Earnings
    total_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    pending_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    paid_earnings DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    
    -- Payout Settings
    payout_method ENUM('bank_transfer', 'paypal', 'stripe', 'wire') DEFAULT 'bank_transfer',
    payout_details JSON NULL COMMENT 'Bank account, PayPal email, etc.',
    payout_threshold DECIMAL(10,2) NOT NULL DEFAULT 100.00,
    payout_schedule ENUM('weekly', 'biweekly', 'monthly') NOT NULL DEFAULT 'monthly',
    
    -- Minimum Payout
    minimum_payout DECIMAL(10,2) NOT NULL DEFAULT 50.00,
    
    -- Status
    reseller_status ENUM('pending', 'active', 'suspended', 'terminated') NOT NULL DEFAULT 'pending',
    approved_at DATETIME NULL,
    approved_by BIGINT UNSIGNED NULL,
    
    -- Marketing
    marketing_materials_access TINYINT(1) NOT NULL DEFAULT 1,
    api_access TINYINT(1) NOT NULL DEFAULT 0,
    api_key VARCHAR(255) NULL,
    
    -- Tracking
    tracking_domain VARCHAR(255) NULL,
    utm_parameters JSON NULL,
    
    -- Stats
    total_clicks INT UNSIGNED NOT NULL DEFAULT 0,
    total_conversions INT UNSIGNED NOT NULL DEFAULT 0,
    avg_order_value DECIMAL(10,2) NULL,
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_payout_at DATETIME NULL,
    last_activity_at DATETIME NULL,
    
    -- Soft Delete
    deleted_at DATETIME NULL,
    
    -- Indexes
    PRIMARY KEY (reseller_id),
    UNIQUE KEY uk_referral_code (referral_code),
    
    INDEX idx_user_id (user_id),
    INDEX idx_reseller_status (reseller_status),
    INDEX idx_commission_tier (commission_tier),
    INDEX idx_total_earnings (total_earnings),
    INDEX idx_created_at (created_at),
    
    -- Foreign Keys
    CONSTRAINT fk_resellers_user 
        FOREIGN KEY (user_id) 
        REFERENCES wp_users(ID) 
        ON DELETE CASCADE

) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC;
```

### 2.6 Commissions Table

```sql
-- =====================================================
-- Table: wp_mp_commissions
-- Purpose: Track reseller commissions and payouts
-- =====================================================

CREATE TABLE wp_mp_commissions (
    -- Primary Key
    commission_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Foreign Keys
    reseller_id BIGINT UNSIGNED NOT NULL,
    business_id BIGINT UNSIGNED NULL,
    referral_id BIGINT UNSIGNED NULL,
    
    -- Commission Details
    commission_type ENUM('signup', 'subscription', 'upgrade', 'review', 'custom') NOT NULL,
    commission_amount DECIMAL(12,2) NOT NULL,
    commission_rate DECIMAL(5,2) NOT NULL COMMENT 'Rate used at time of calculation',
    
    -- Reference Info
    reference_type VARCHAR(50) NULL COMMENT 'Order, subscription, etc.',
    reference_id VARCHAR(255) NULL COMMENT 'External reference ID',
    reference_amount DECIMAL(12,2) NULL COMMENT 'Amount that commission was calculated from',
    
    -- Status
    commission_status ENUM('pending', 'approved', 'paid', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    
    -- Approval
    approved_at DATETIME NULL,
    approved_by BIGINT UNSIGNED NULL,
    
    -- Payout
    payout_id BIGINT UNSIGNED NULL,
    paid_at DATETIME NULL,
    paid_amount DECIMAL(12,2) NULL,
    
    -- Validation
    is_validated TINYINT(1) NOT NULL DEFAULT 0,
    validated_at DATETIME NULL,
    validation_notes TEXT NULL,
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Soft Delete
    deleted_at DATETIME NULL,
    
    -- Indexes
    PRIMARY KEY (commission_id),
    
    INDEX idx_reseller_id (reseller_id),
    INDEX idx_business_id (business_id),
    INDEX idx_referral_id (referral_id),
    INDEX idx_commission_status (commission_status),
    INDEX idx_commission_type (commission_type),
    INDEX idx_created_at (created_at),
    INDEX idx_paid_at (paid_at),
    
    -- Composite Indexes
    INDEX idx_reseller_status (reseller_id, commission_status),
    INDEX idx_status_created (commission_status, created_at),
    
    -- Foreign Keys
    CONSTRAINT fk_commissions_reseller 
        FOREIGN KEY (reseller_id) 
        REFERENCES wp_mp_resellers(reseller_id) 
        ON DELETE CASCADE,
    
    CONSTRAINT fk_commissions_business 
        FOREIGN KEY (business_id) 
        REFERENCES wp_mp_businesses(business_id) 
        ON DELETE SET NULL

) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC;
```

### 2.7 Notifications Table

```sql
-- =====================================================
-- Table: wp_mp_notifications
-- Purpose: Store user notifications
-- =====================================================

CREATE TABLE wp_mp_notifications (
    -- Primary Key
    notification_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Foreign Key
    user_id BIGINT UNSIGNED NOT NULL,
    
    -- Notification Type
    notification_type ENUM(
        'review_received', 
        'review_approved', 
        'review_rejected',
        'review_response',
        'trust_update',
        'commission_earned',
        'commission_paid',
        'referral_signup',
        'system',
        'reminder',
        'alert'
    ) NOT NULL,
    
    -- Content
    notification_title VARCHAR(255) NOT NULL,
    notification_message TEXT NOT NULL,
    notification_data JSON NULL COMMENT 'Additional structured data',
    
    -- Related Entities
    related_type VARCHAR(50) NULL COMMENT 'review, business, commission, etc.',
    related_id BIGINT UNSIGNED NULL,
    
    -- User Reference (for mass notifications)
    reference_user_id BIGINT UNSIGNED NULL COMMENT 'User this notification references',
    
    -- Status
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    
    -- Delivery
    delivery_method ENUM('in_app', 'email', 'push', 'sms') NOT NULL DEFAULT 'in_app',
    delivery_status ENUM('pending', 'sent', 'delivered', 'failed') NOT NULL DEFAULT 'pending',
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    
    -- Priority
    priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
    
    -- Actions
    action_url VARCHAR(500) NULL,
    action_label VARCHAR(100) NULL,
    
    -- Expiration
    expires_at DATETIME NULL,
    
    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Soft Delete
    deleted_at DATETIME NULL,
    
    -- Indexes
    PRIMARY KEY (notification_id),
    
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read),
    INDEX idx_delivery_status (delivery_status),
    INDEX idx_priority (priority),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at),
    
    -- Composite Indexes
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_user_type (user_id, notification_type),
    INDEX idx_user_created (user_id, created_at),
    
    -- Foreign Keys
    CONSTRAINT fk_notifications_user 
        FOREIGN KEY (user_id) 
        REFERENCES wp_users(ID) 
        ON DELETE CASCADE

) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC;
```

### 2.8 Email Logs Table

```sql
-- =====================================================
-- Table: wp_mp_email_logs
-- Purpose: Track all email sending history
-- =====================================================

CREATE TABLE wp_mp_email_logs (
    -- Primary Key
    log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    
    -- Email Identity
    email_id VARCHAR(50) NOT NULL COMMENT 'Unique email identifier',
    
    -- Recipient
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NULL,
    recipient_id BIGINT UNSIGNED NULL,
    recipient_type ENUM('user', 'business', 'reseller', 'admin', 'guest') NOT NULL,
    
    -- Email Content
    email_subject VARCHAR(255) NOT NULL,
    email_template VARCHAR(100) NOT NULL,
    email_body_text LONGTEXT NULL,
    email_body_html LONGTEXT NULL,
    
    -- Email Type
    email_type ENUM('transactional', 'marketing', 'notification', 'system', 'alert') NOT NULL,
    email_category VARCHAR(50) NOT NULL COMMENT 'user, review, business, reseller, support',
    
    -- Related Entities
    related_type VARCHAR(50) NULL COMMENT 'review, business, commission, etc.',
    related_id BIGINT UNSIGNED NULL,
    
    -- Sending Status
    send_status ENUM('queued', 'sending', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed', 'unsubscribed') NOT NULL DEFAULT 'queued',
    
    -- Timestamps
    queued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sending_at DATETIME NULL,
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    opened_at DATETIME NULL,
    clicked_at DATETIME NULL,
    
    -- Open & Click Tracking
    opens_count INT UNSIGNED NOT NULL DEFAULT 0,
    clicks_count INT UNSIGNED NOT NULL DEFAULT 0,
    last_click_url VARCHAR(500) NULL,
    
    -- Bounce & Failure
    bounce_reason VARCHAR(255) NULL,
    bounce_type ENUM('hard', 'soft', 'block', 'spam') NULL,
    failure_reason TEXT NULL,
    
    -- SMTP Response
    smtp_response VARCHAR(255) NULL,
    message_id VARCHAR(255) NULL COMMENT 'ESP message ID',
    
    -- Provider
    email_provider VARCHAR(50) NOT NULL DEFAULT 'wordpress',
    provider_message_id VARCHAR(255) NULL,
    
    -- Tags & Segments
    tags JSON NULL COMMENT 'For filtering and segmentation',
    
    -- IP & User Agent
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    
    -- Unsubscribe
    unsubscribed_at DATETIME NULL,
    unsubscribe_reason VARCHAR(255) NULL,
    
    -- Cost
    cost_per_email DECIMAL(8,4) NULL,
    total_cost DECIMAL(10,4) NULL,
    
    -- Indexes
    PRIMARY KEY (log_id),
    
    INDEX idx_recipient_email (recipient_email),
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_recipient_type (recipient_type),
    INDEX idx_email_template (email_template),
    INDEX idx_email_type (email_type),
    INDEX idx_email_category (email_category),
    INDEX idx_send_status (send_status),
    INDEX idx_queued_at (queued_at),
    INDEX idx_sent_at (sent_at),
    INDEX idx_delivered_at (delivered_at),
    INDEX idx_opened_at (opened_at),
    
    -- Composite Indexes
    INDEX idx_status_queued (send_status, queued_at),
    INDEX idx_recipient_status (recipient_email, send_status),
    INDEX idx_template_status (email_template, send_status),
    INDEX idx_category_status (email_category, send_status)

) ENGINE=InnoDB 
  DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci
  ROW_FORMAT=DYNAMIC
  AUTO_INCREMENT=1;
```

---

## 3. Table Relationships

### 3.1 Relationship Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              RELATIONSHIP SUMMARY                           │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  wp_users ──────────────────────┬─────────────────────────────              │
│         │                       │                                            │
│         ├── 1:N ──► wp_mp_reviews.user_id                                  │
│         │                       │                                            │
│         ├── 1:N ──► wp_mp_businesses.user_id                               │
│         │                       │                                            │
│         ├── 1:N ──► wp_mp_resellers.user_id                                │
│         │                       │                                            │
│         ├── 1:N ──► wp_mp_notifications.user_id                           │
│         │                       │                                            │
│         └── 1:N ──► wp_mp_email_logs.recipient_id                         │
│                                                                             │
│  wp_mp_reviews ─────────────────┬──────────────────────────                │
│          │                      │                                            │
│          ├── 1:N ──► wp_mp_review_images.review_id                        │
│          │                      │                                            │
│          └── N:1 ──► wp_mp_businesses.business_id                          │
│                              │                                              │
│  wp_mp_businesses ──────────────┼──────────────────────────                │
│          │                      │                                            │
│          ├── 1:1 ──► wp_mp_traffic_signals.business_id                    │
│          │                      │                                            │
│          ├── 1:N ──► wp_mp_reviews.business_id                            │
│          │                      │                                            │
│          └── 1:N ──► wp_mp_commissions.business_id                         │
│                              │                                              │
│  wp_mp_resellers ──────────────┼──────────────────────────                │
│          │                      │                                            │
│          ├── 1:N ──► wp_mp_commissions.reseller_id                         │
│          │                      │                                            │
│          └── 1:N ──► wp_mp_businesses.reseller_id                          │
│                              │                                              │
│  wp_terms ─────────────────────┘                                            │
│          │                                                                  │
│          └── 1:N ──► wp_mp_businesses.category_id                          │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### 3.2 Cardinality Summary

| Parent Table | Child Table | Relationship | Type |
|--------------|-------------|--------------|------|
| `wp_users` | `wp_mp_reviews` | 1:N | User creates reviews |
| `wp_users` | `wp_mp_businesses` | 1:N | User claims business |
| `wp_users` | `wp_mp_resellers` | 1:N | User becomes reseller |
| `wp_users` | `wp_mp_notifications` | 1:N | User receives notifications |
| `wp_mp_businesses` | `wp_mp_reviews` | 1:N | Business has reviews |
| `wp_mp_businesses` | `wp_mp_traffic_signals` | 1:1 | One trust status per business |
| `wp_mp_reviews` | `wp_mp_review_images` | 1:N | Review has images |
| `wp_mp_resellers` | `wp_mp_commissions` | 1:N | Reseller earns commissions |
| `wp_mp_businesses` | `wp_mp_commissions` | 1:N | Business generates commissions |
| `wp_terms` | `wp_mp_businesses` | 1:N | Category has businesses |

---

## 4. Data Flow

### 4.1 Review Lifecycle

```
┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐
│ Visitor │───►│ Submit  │───►│ Valid-  │───►│ Moder-  │───►│ Publish │
│         │    │ Review  │    │ ation   │    │ ation   │    │         │
└─────────┘    └────┬────┘    └────┬────┘    └────┬────┘    └────┬────┘
                    │             │             │             │
                    ▼             ▼             ▼             ▼
              ┌─────────┐   ┌─────────┐   ┌─────────┐   ┌─────────┐
              │Insert:  │   │Check:   │   │Admin:   │   │Update:  │
              │wp_mp_  │   │Spam,    │   │Approve/ │   │wp_mp_   │
              │reviews │   │Content  │   │Reject   │   │business │
              │status= │   │Rules    │   │         │   │stats    │
              │pending │   │         │   │         │   │         │
              └─────────┘   └─────────┘   └─────────┘   └─────────┘
                                                        │
                                                        ▼
                                                  ┌─────────┐
                                                  │Notify:  │
                                                  │Business │
                                                  │         │
                                                  └─────────┘
```

### 4.2 Traffic Light Calculation Flow

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ Trigger:    │───►│ Calculate:  │───►│ Determine:  │───►│ Update:     │
│ Review      │    │ Trust Score │    │ Status      │    │ Traffic     │
│ Published   │    │ (Algorithm) │    │ (Walking/   │    │ Signal      │
│             │    │             │    │ Shopping/   │    │ Table       │
│             │    │             │    │ Bad)        │    │             │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
                                                    │
                                                    ▼
                                              ┌─────────────┐
                                              │Notify:      │
                                              │Business     │
                                              │             │
                                              └─────────────┘
```

### 4.3 Reseller Commission Flow

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ Referral    │───►│ Business    │───►│ Calculate   │───►│ Create:     │
│ Link Click  │    │ Signs Up    │    │ Commission  │    │ wp_mp_      │
│             │    │             │    │             │    │ commissions │
│             │    │             │    │             │    │ (pending)   │
└─────────────┘    └─────────────┘    └─────────────┘    └──────┬──────┘
                                                                   │
                                                                   ▼
                                                            ┌─────────────┐
                                                            │ Notify:     │
                                                            │ Reseller    │
                                                            │             │
                                                            └──────┬──────┘
                                                                   │
                                                                   ▼
                                                            ┌─────────────┐
                                                            │ Payout:     │
                                                            │ Update      │
                                                            │ status=paid │
                                                            └─────────────┘
```

### 4.4 Email Notification Flow

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ Event       │───►│ Create:     │───►│ Queue:      │───►│ Send via    │
│ Trigger     │    │ wp_mp_      │    │ Background  │    │ SMTP/API    │
│             │    │ notifications│   │ Process     │    │             │
│ (Review     │    │             │    │             │    │             │
│  Published) │    │             │    │             │    │             │
└─────────────┘    └─────────────┘    └─────────────┘    └──────┬──────┘
                                                                   │
                                                                   ▼
                                                            ┌─────────────┐
                                                            │ Log:        │
                                                            │ wp_mp_      │
                                                            │ email_logs  │
                                                            │             │
                                                            └─────────────┘
```

---

## 5. Performance Considerations

### 5.1 Index Strategy

| Table | Index Type | Purpose |
|-------|------------|---------|
| `wp_mp_reviews` | Composite (business_id, status) | Fast business review queries |
| `wp_mp_reviews` | Full-text | Search reviews |
| `wp_mp_reviews` | (published_at) | Sort by date |
| `wp_mp_businesses` | (avg_rating, total_reviews) | Featured/trending queries |
| `wp_mp_notifications` | Composite (user_id, is_read) | User notification list |
| `wp_mp_email_logs` | (send_status, queued_at) | Queue processing |

### 5.2 Partitioning Strategy (For Large Scale)

```sql
-- Email logs partition by month
ALTER TABLE wp_mp_email_logs
PARTITION BY RANGE (TO_DAYS(queued_at)) (
    PARTITION p_2026_01 VALUES LESS THAN (TO_DAYS('2026-02-01')),
    PARTITION p_2026_02 VALUES LESS THAN (TO_DAYS('2026-03-01')),
    PARTITION p_2026_03 VALUES LESS THAN (TO_DAYS('2026-04-01')),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Reviews partition by year
ALTER TABLE wp_mp_reviews
PARTITION BY RANGE (YEAR(created_at)) (
    PARTITION p_2024 VALUES LESS THAN (2025),
    PARTITION p_2025 VALUES LESS THAN (2026),
    PARTITION p_2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### 5.3 Estimated Storage

| Table | Est. Rows | Avg Row Size | Est. Storage |
|-------|-----------|--------------|--------------|
| `wp_mp_reviews` | 10,000,000 | 1,500 bytes | 15 GB |
| `wp_mp_review_images` | 50,000,000 | 500 bytes | 25 GB |
| `wp_mp_businesses` | 1,000,000 | 2,000 bytes | 2 GB |
| `wp_mp_traffic_signals` | 1,000,000 | 1,000 bytes | 1 GB |
| `wp_mp_resellers` | 100,000 | 2,000 bytes | 200 MB |
| `wp_mp_commissions` | 5,000,000 | 500 bytes | 2.5 GB |
| `wp_mp_notifications` | 100,000,000 | 500 bytes | 50 GB |
| `wp_mp_email_logs` | 500,000,000 | 1,000 bytes | 500 GB |
| **Total** | | | **~596 GB** |

---

## 6. WordPress Integration

### 6.1 Table Prefix

All tables use `wp_mp_` prefix to follow WordPress conventions with `wp_` prefix.

### 6.2 wp_users Integration

Foreign keys reference `wp_users.ID` for all user-related tables, enabling:
- Single WordPress user management
- Seamless WordPress authentication
- Role/capability integration

### 6.3 wp_terms Integration

`wp_mp_businesses.category_id` references `wp_terms.term_id` for:
- Reuse WordPress taxonomy system
- Category management via WordPress admin
- Integration with WordPress REST API

---

## 7. Installation SQL

```sql
-- Run all tables in order (respecting foreign key dependencies)

-- 1. Core tables without foreign keys
CREATE TABLE wp_mp_resellers (...);
CREATE TABLE wp_mp_businesses (...);

-- 2. Tables with foreign keys
CREATE TABLE wp_mp_traffic_signals (...);
CREATE TABLE wp_mp_reviews (...);
CREATE TABLE wp_mp_review_images (...);
CREATE TABLE wp_mp_commissions (...);
CREATE TABLE wp_mp_notifications (...);

-- 3. Standalone audit table
CREATE TABLE wp_mp_email_logs (...);

-- 4. Create trigger (separate)
DELIMITER //
CREATE TRIGGER ...
//
DELIMITER ;
```

---

*Document Version: 1.0*
*Generated: 2026-06-02*
*For MyProtector Platform - Stage 1*