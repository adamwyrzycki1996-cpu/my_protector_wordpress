# MyProtector Platform - WordPress Role Architecture

## Overview

This document defines the complete role-based access control (RBAC) system for the MyProtector Platform. Five distinct roles manage different aspects of the review platform, from submitting reviews to full system administration.

---

## 1. Role Summary

| Role | Slug | Access Level | Users |
|------|------|--------------|-------|
| **Administrator** | `mp_admin` | Full Access | 2-5 |
| **Customer Support** | `mp_support` | Support Operations | 5-20 |
| **Business** | `mp_business` | Own Business Only | Unlimited |
| **Reseller** | `mp_reseller` | Partner/Referral | Unlimited |
| **Individual** | `mp_individual` | Consumer/Reviewer | Unlimited |

---

## 2. Individual Role (mp_individual)

### Description
Regular consumers who browse businesses and submit reviews.

### Dashboard Access
```
Individual Dashboard (/dashboard)
├── My Reviews
├── My Profile
├── Notifications
├── Saved Businesses
└── Account Settings
```

### Capabilities
```php
// Reading & Browsing
'read'                                 // Access dashboard
'mp_view_businesses'                   // View business profiles
'mp_search_businesses'                 // Search for businesses
'mp_view_reviews'                      // Read reviews

// Reviews
'mp_create_reviews'                    // Submit new reviews
'mp_edit_own_reviews'                 // Edit own reviews (24h window)
'mp_delete_own_reviews'               // Delete own reviews
'mp_upload_review_images'             // Upload images with reviews

// Engagement
'mp_mark_reviews_helpful'              // Mark reviews as helpful
'mp_report_reviews'                   // Report inappropriate reviews
'mp_share_reviews'                     // Share reviews on social

// Profile
'mp_edit_profile'                      // Edit own profile
'mp_upload_avatar'                     // Upload profile picture
'mp_change_password'                   // Change account password

// Notifications
'mp_view_notifications'                // View in-app notifications
'mp_manage_notification_prefs'        // Manage email preferences

// Support
'mp_create_support_tickets'            // Submit support tickets
'mp_view_own_tickets'                  // View own tickets

// Data
'mp_export_own_data'                   // Export personal data (GDPR)
'mp_delete_account'                    // Delete own account
```

### Restrictions
- ❌ Cannot access admin area
- ❌ Cannot moderate reviews
- ❌ Cannot manage businesses
- ❌ Cannot view other users' private data
- ❌ Cannot access reseller features
- ❌ Cannot manage system settings

---

## 3. Business Role (mp_business)

### Description
Business owners who have claimed and manage their company profile.

### Dashboard Access
```
Business Dashboard (/dashboard/business)
├── Overview (Trust Score, Reviews, Stats)
├── Reviews
│   ├── All Reviews
│   ├── Write Response
│   └── Reviews Needing Response
├── Analytics
│   ├── Rating Trends
│   ├── Review Sources
│   └── Competitor Comparison
├── Profile
│   ├── Company Info
│   ├── Documents (Insurance, Terms, Promise)
│   ├── Logo & Images
│   └── Contact Settings
├── Marketing
│   ├── Invite Customers
│   ├── Widget Codes
│   └── Share Links
├── Team
│   └── Manage Team Members
├── Settings
│   ├── Notifications
│   ├── Integrations
│   └── Billing
└── Support
    ├── Tickets
    └── Help Center
```

### Capabilities
```php
// Basic Access
'read'                                 // Access dashboard
'mp_business_access'                   // Access business dashboard

// Business Profile
'mp_manage_own_business'               // Full business management
'mp_update_business_info'              // Update company details
'mp_upload_business_logo'              // Upload logo
'mp_upload_cover_image'                 // Upload cover image
'mp_add_business_gallery'              // Add gallery images

// Documents (Trust Requirements)
'mp_add_insurance_info'                // Add insurance name/URL
'mp_add_terms_url'                     // Add terms page URL
'mp_add_promise_page'                  // Add promise page URL

// Reviews
'mp_view_business_reviews'             // View reviews for own business
'mp_respond_to_reviews'               // Respond to reviews
'mp_edit_responses'                    // Edit own responses
'mp_request_review_removal'            // Request inappropriate review removal
'mp_view_review_analytics'             // View review statistics

// Widgets
'mp_access_widgets'                    // Access widget generator
'mp_generate_widget_code'             // Generate embed codes

// Team Management
'mp_invite_team_members'               // Invite team members
'mp_manage_team_members'              // Manage team access
'mp_assign_team_roles'                 // Assign roles to team

// Notifications
'mp_manage_notification_settings'     // Configure notifications

// Marketing
'mp_access_review_invitations'        // Access invitation tools
'mp_share_business'                   // Share business profile

// Financial
'mp_view_billing_history'             // View payment history
'mp_manage_subscription'              // Manage subscription
'mp_download_invoices'               // Download invoices
```

### Restrictions
- ❌ Cannot access admin area
- ❌ Cannot moderate any reviews (only own business)
- ❌ Cannot view other businesses
- ❌ Cannot access reseller features
- ❌ Cannot manage system settings
- ❌ Cannot access other business data
- ❌ Cannot modify user accounts

---

## 4. Reseller Role (mp_reseller)

### Description
Partners who refer businesses to the platform and earn commissions.

### Dashboard Access
```
Reseller Dashboard (/dashboard/reseller)
├── Overview (Earnings, Referrals, Stats)
├── Referrals
│   ├── Active Referrals
│   ├── Converted
│   └── Pending
├── Earnings
│   ├── Pending Commissions
│   ├── Paid Commissions
│   └── Earnings History
├── Payouts
│   ├── Request Payout
│   ├── Payout History
│   └── Payment Settings
├── Marketing
│   ├── Referral Links
│   ├── Banners
│   └── Email Templates
├── Reports
│   ├── Performance Reports
│   └── Export Data
└── Account
    ├── Profile
    ├── API Access
    └── Support
```

### Capabilities
```php
// Basic Access
'read'                                 // Access dashboard
'mp_reseller_access'                   // Access reseller features

// Referrals
'mp_create_referral_links'             // Create tracking links
'mp_track_referrals'                   // Track referral performance
'mp_view_referral_analytics'           // View analytics
'mp_export_referral_data'              // Export referral reports

// Earnings
'mp_view_earnings'                     // View earnings dashboard
'mp_view_pending_commissions'          // View pending commissions
'mp_view_paid_commissions'             // View paid commissions
'mp_request_payout'                   // Request payout
'mp_view_payment_history'              // View payment history

// Marketing
'mp_access_marketing_materials'       // Access promotional materials
'mp_access_banners'                    // Access banner assets
'mp_access_email_templates'            // Access email templates
'mp_share_referral_links'              // Share referral links

// API
'mp_api_access'                       // API key access
'mp_view_api_documentation'            // View API docs

// Profile
'mp_manage_payout_settings'           // Manage payment details
'mp_update_profile'                    // Update profile info

// Support
'mp_create_reseller_tickets'          // Submit support tickets
'mp_view_reseller_faqs'               // View FAQ section
```

### Restrictions
- ❌ Cannot access admin area
- ❌ Cannot manage businesses
- ❌ Cannot moderate reviews
- ❌ Cannot access other resellers' data
- ❌ Cannot manage system settings
- ❌ Cannot approve businesses

---

## 5. Customer Support Role (mp_support)

### Description
Support agents who handle user inquiries and ticket management.

### Dashboard Access
```
Support Dashboard (/dashboard/support)
├── Overview (Open Tickets, Stats)
├── Tickets
│   ├── All Tickets
│   ├── Open
│   ├── In Progress
│   ├── Pending Response
│   └── Resolved
├── Users
│   ├── User Lookup
│   ├── User History
│   └── Impersonate (audit only)
├── Quick Tools
│   ├── Review Lookup
│   ├── Business Lookup
│   └── Common Solutions
├── Reports
│   ├── Ticket Metrics
│   └── Response Times
└── Settings
    ├── Canned Responses
    └── Notifications
```

### Capabilities
```php
// Basic Access
'read'                                 // Access dashboard
'mp_support_access'                     // Access support features

// Tickets
'mp_view_all_tickets'                   // View all support tickets
'mp_respond_to_tickets'               // Respond to tickets
'mp_update_ticket_status'              // Update ticket status
'mp_close_tickets'                      // Close resolved tickets
'mp_escalate_tickets'                  // Escalate to admin
'mp_merge_tickets'                      // Merge duplicate tickets

// Users
'mp_view_user_accounts'               // View user accounts
'mp_view_user_activity'                // View user activity history
'mp_reset_user_passwords'              // Reset user passwords
'mp_suspend_users'                      // Suspend user accounts (temporary)
'mp_view_user_reviews'                 // View user reviews

// Reviews (Limited)
'mp_view_flagged_reviews'              // View flagged/reported reviews
'mp_flag_for_moderation'              // Flag reviews for admin review
'mp_view_pending_reviews'             // View pending reviews (no edit)

// Businesses
'mp_view_business_profiles'            // View business profiles
'mp_verify_business_claims'            // Verify business claim requests
'mp_update_business_contact'          // Update business contact info

// Communication
'mp_send_user_emails'                  // Send emails to users
'mp_create_canned_responses'           // Create template responses
'mp_broadcast_notifications'          // Send platform notifications

// Reports
'mp_view_ticket_reports'               // View ticket metrics
'mp_export_ticket_data'               // Export ticket data
'mp_view_response_time_stats'         // View response time reports

// Restrictions Override
'mp_bypass_review_timeout'            // Can moderate reviews anytime
```

### Restrictions
- ❌ Cannot delete users
- ❌ Cannot permanently ban users
- ❌ Cannot modify system settings
- ❌ Cannot access financial data
- ❌ Cannot manage resellers
- ❌ Cannot approve blacklist entries
- ❌ Cannot override traffic light status

---

## 6. Administrator Role (mp_admin)

### Description
Full platform administrators with complete system access.

### Dashboard Access
```
Admin Dashboard (/wp-admin/admin.php?page=myprotector)
├── Dashboard (Platform Stats)
├── Reviews
│   ├── All Reviews
│   ├── Pending Approval
│   ├── Flagged Reviews
│   ├── AI Moderation
│   └── Bulk Actions
├── Companies
│   ├── All Companies
│   ├── Pending Claims
│   ├── Verification Queue
│   ├── Trust Settings
│   └── Blacklist
├── Users
│   ├── All Users
│   ├── Individuals
│   ├── Businesses
│   ├── Resellers
│   ├── Support Agents
│   └── Admin Users
├── Resellers
│   ├── All Resellers
│   ├── Applications
│   ├── Commissions
│   ├── Payouts
│   └── Reports
├── Blacklist
│   ├── Individuals
│   ├── Businesses
│   └── Appeals
├── Support
│   ├── All Tickets
│   ├── SLAs
│   └── Canned Responses
├── Communications
│   ├── Email Templates
│   ├── Send Campaign
│   ├── Email Logs
│   └── Notifications
├── Finance
│   ├── Transactions
│   ├── Invoices
│   ├── Commissions Report
│   └── Reseller Payouts
├── SEO
│   ├── Sitemap
│   ├── Schema
│   └── Page SEO
├── Settings
│   ├── General
│   ├── Email
│   ├── Widgets
│   ├── WooCommerce
│   ├── API Keys
│   └── Security
├── Tools
│   ├── Import/Export
│   ├── Cache Management
│   └── Debug Logs
└── Audit Log
    ├── All Activity
    ├── User Actions
    └── System Events
```

### Capabilities
```php
// Full Access
'manage_myprotector'                   // Master capability
'read'                                 // Access all areas

// Reviews Management
'mp_edit_all_reviews'                  // Edit any review
'mp_delete_all_reviews'               // Delete any review
'mp_moderate_reviews'                 // Approve/reject reviews
'mp_feature_reviews'                  // Feature/unfeature reviews
'mp_bulk_review_actions'              // Bulk operations
'mp_access_ai_moderation'            // AI moderation settings
'mp_view_review_reports'              // View detailed reports

// Company Management
'mp_edit_all_companies'              // Edit any company
'mp_delete_companies'                // Delete companies
'mp_verify_companies'                // Verify business claims
'mp_approve_verification'            // Approve verification
'mp_override_trust_status'           // Manual trust override
'mp_manage_featured_companies'      // Feature companies
'mp_access_company_reports'          // View company analytics

// User Management
'mp_manage_all_users'               // Full user management
'mp_ban_users'                       // Ban users permanently
'mp_impersonate_users'               // Impersonate users (audit)
'mp_export_user_data'               // Export user data
'mp_view_user_permissions'          // View user capabilities

// Reseller Management
'mp_manage_resellers'               // Full reseller management
'mp_approve_reseller_applications'  // Approve/reseller applications
'mp_release_commissions'            // Release commission payments
'mp_manage_reseller_tiers'         // Manage tier system
'mp_view_reseller_reports'          // View reseller reports

// Blacklist Management
'mp_manage_blacklist'               // Full blacklist control
'mp_approve_blacklist_entries'     // Approve blacklist additions
'mp_view_blacklist_reports'        // View blacklist analytics

// Support Management
'mp_manage_all_tickets'            // Full ticket management
'mp_manage_sla_settings'           // Configure SLAs
'mp_assign_tickets'                 // Assign tickets to agents

// Communications
'mp_manage_email_templates'        // Create/edit email templates
'mp_send_email_campaigns'          // Send marketing campaigns
'mp_manage_notifications'         // Configure notifications
'mp_view_email_logs'               // View email history

// Financial
'mp_view_financial_reports'        // View financial reports
'mp_manage_invoices'              // Manage invoices
'mp_process_payouts'              // Process reseller payouts
'mp_view_commission_history'      // View commission records

// SEO & Content
'mp_manage_page_seo'              // Manage page SEO settings
'mp_edit_page_content'            // Edit page content
'mp_manage_sitemap'              // Manage XML sitemap
'mp_configure_schema'             // Configure structured data

// Settings
'mp_manage_general_settings'      // General plugin settings
'mp_manage_email_settings'       // Email configuration
'mp_manage_widget_settings'      // Widget configuration
'mp_manage_woocommerce_settings' // WooCommerce settings
'mp_manage_api_keys'            // Manage API access
'mp_manage_security_settings'   // Security settings

// Tools & Maintenance
'mp_access_import_export'        // Import/export data
'mp_clear_cache'                 // Clear system cache
'mp_view_debug_logs'             // View debug information
'mp_run_system_checks'           // Run system diagnostics

// Audit
'mp_view_audit_log'              // View complete audit log
'mp_export_audit_data'          // Export audit records
'mp_configure_audit_settings'   // Configure audit retention
```

### Restrictions
- None (full access)

---

## 7. Capability Matrix

### Legend
- ✅ **Full Access** - Can perform action
- ⚠️ **Limited Access** - Can perform with limitations
- ❌ **No Access** - Cannot perform action

| Capability | Individual | Business | Reseller | Support | Admin |
|-----------|------------|----------|----------|---------|-------|
| **Dashboard Access** | | | | | |
| View Personal Dashboard | ✅ | ✅ | ✅ | ✅ | ✅ |
| View Business Dashboard | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| View Support Dashboard | ❌ | ❌ | ❌ | ✅ | ✅ |
| View Admin Dashboard | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Reviews** | | | | | |
| Create Reviews | ✅ | ❌ | ❌ | ❌ | ✅ |
| Edit Own Reviews | ✅ | ❌ | ❌ | ❌ | ✅ |
| Delete Own Reviews | ✅ | ❌ | ❌ | ❌ | ✅ |
| View Business Reviews | ⚠️ | ✅ | ❌ | ⚠️ | ✅ |
| Respond to Reviews | ❌ | ✅ | ❌ | ❌ | ✅ |
| Moderate All Reviews | ❌ | ❌ | ❌ | ⚠️ | ✅ |
| Feature Reviews | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Business** | | | | | |
| Claim Business | ✅ | ✅ | ❌ | ❌ | ✅ |
| Manage Own Business | ❌ | ✅ | ❌ | ⚠️ | ✅ |
| Verify Business Claims | ❌ | ❌ | ❌ | ✅ | ✅ |
| Override Trust Status | ❌ | ❌ | ❌ | ❌ | ✅ |
| View All Businesses | ❌ | ❌ | ❌ | ⚠️ | ✅ |
| **Users** | | | | | |
| View Own Profile | ✅ | ✅ | ✅ | ✅ | ✅ |
| Edit Own Profile | ✅ | ✅ | ✅ | ✅ | ✅ |
| View Other Users | ❌ | ❌ | ❌ | ✅ | ✅ |
| Reset User Passwords | ❌ | ❌ | ❌ | ✅ | ✅ |
| Suspend Users | ❌ | ❌ | ❌ | ⚠️ | ✅ |
| Ban Users | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Reseller** | | | | | |
| Create Referral Links | ❌ | ❌ | ✅ | ❌ | ✅ |
| View Earnings | ❌ | ❌ | ✅ | ❌ | ✅ |
| Request Payout | ❌ | ❌ | ✅ | ❌ | ✅ |
| Release Commissions | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Support** | | | | | |
| View Tickets | ⚠️ | ⚠️ | ⚠️ | ✅ | ✅ |
| Respond to Tickets | ❌ | ❌ | ❌ | ✅ | ✅ |
| Escalate Tickets | ❌ | ❌ | ❌ | ✅ | ✅ |
| **Financial** | | | | | |
| View Own Billing | ❌ | ✅ | ✅ | ❌ | ✅ |
| View Financial Reports | ❌ | ❌ | ❌ | ❌ | ✅ |
| Process Payouts | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Settings** | | | | | |
| Manage Own Settings | ✅ | ✅ | ✅ | ✅ | ✅ |
| Manage Plugin Settings | ❌ | ❌ | ❌ | ❌ | ✅ |
| **Audit** | | | | | |
| View Own Activity | ✅ | ✅ | ✅ | ⚠️ | ✅ |
| View System Audit Log | ❌ | ❌ | ❌ | ❌ | ✅ |

---

## 8. Role Hierarchy

```
                    ┌─────────────────┐
                    │   Administrator │
                    │   (Full Access) │
                    └────────┬────────┘
                             │
           ┌─────────────────┼─────────────────┐
           │                 │                 │
           ▼                 ▼                 ▼
   ┌───────────────┐ ┌───────────────┐ ┌───────────────┐
   │Customer Support│ │   Business    │ │   Reseller   │
   │   (Tickets)    │ │   (Profile)   │ │  (Referrals)  │
   └───────┬───────┘ └───────────────┘ └───────────────┘
           │
           ▼
   ┌───────────────┐
   │  Individual   │
   │  (Reviews)    │
   └───────────────┘
```

---

## 9. User Role Assignments

| User Type | Assigned Role | Notes |
|-----------|---------------|-------|
| Anonymous Visitor | None | Limited to public pages |
| Registered Consumer | `mp_individual` | Default role |
| Business Owner | `mp_business` | Plus `mp_individual` |
| Reseller Partner | `mp_reseller` | Plus `mp_individual` |
| Support Agent | `mp_support` | Plus `mp_individual` |
| Support Manager | `mp_support` + Custom | Additional capabilities |
| Developer/Co-Founder | `administrator` | WP Admin access |
| Super Admin | WordPress Super Admin | Network admin |

---

## 10. Implementation Notes

### Role Creation Order
1. Create Individual role first (default for registration)
2. Create Business, Reseller, Support roles
3. Create Admin role last
4. Add capabilities to Administrator role

### Capability Groups
- **Core**: Basic read/edit capabilities
- **Reviews**: Review management
- **Businesses**: Business profile management
- **Users**: User management
- **Resellers**: Partner functionality
- **Support**: Helpdesk operations
- **Finance**: Payment processing
- **System**: Administrative functions

### Future Considerations
- Add custom roles per business (Team roles)
- Add role hierarchy inheritance
- Add conditional capabilities based on subscription level
- Add audit logging for all role changes

---

*Document Version: 1.0*
*Generated: 2026-06-02*