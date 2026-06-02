<?php
/**
 * MyProtector Core - Bootstrap
 * 
 * Initializes plugin components and registers hooks
 * 
 * @package MyProtector\Core
 * @version 1.0.0
 */

namespace MyProtector\Core;

class Bootstrap {
    /**
     * Plugin instance
     * 
     * @var MyProtector
     */
    protected $plugin;

    /**
     * Hook registry
     * 
     * @var Bootstrap\Hooks
     */
    protected $hooks;

    /**
     * Service container reference
     * 
     * @var Services\Container\ServiceContainer
     */
    protected $container;

    /**
     * Constructor
     * 
     * @param MyProtector $plugin
     */
    public function __construct(MyProtector $plugin) {
        $this->plugin = $plugin;
        $this->container = $plugin->getContainer();
    }

    /**
     * Boot all registered modules
     * 
     * @return void
     */
    public function bootModules(): void {
        $modules = $this->plugin->getModules();
        
        foreach ($modules as $module) {
            $module->boot();
        }
    }

    /**
     * Register all plugin hooks
     * 
     * @return void
     */
    public function registerHooks(): void {
        // Register module hooks
        $modules = $this->plugin->getModules();
        foreach ($modules as $module) {
            $module->registerHooks();
        }
        
        // Register core hooks
        $this->registerCoreHooks();
    }

    /**
     * Register core WordPress hooks
     * 
     * @return void
     */
    protected function registerCoreHooks(): void {
        // Initialize hook - Priority 1
        add_action('init', [$this, 'onInit'], 1);
        
        // Widgets init
        add_action('widgets_init', [$this, 'onWidgetsInit'], 10);
        
        // Admin init
        add_action('admin_init', [$this, 'onAdminInit'], 10);
        
        // Frontend assets
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets'], 50);
        
        // Admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets'], 50);
        
        // Cron schedules
        add_filter('cron_schedules', [$this, 'addCronSchedules'], 10, 1);
        
        // Plugin row meta
        add_filter('plugin_row_meta', [$this, 'addPluginRowMeta'], 10, 2);
        
        // Plugin action links
        add_filter('plugin_action_links_' . MYPROTECTOR_BASENAME, [$this, 'addPluginActionLinks'], 10, 1);
    }

    /**
     * WordPress init hook
     * 
     * @return void
     */
    public function onInit(): void {
        // Check WordPress version
        $this->checkWordPressVersion();
        
        // Check PHP version
        $this->checkPhpVersion();
        
        // Register custom post types
        $this->registerPostTypes();
        
        // Register custom taxonomies
        $this->registerTaxonomies();
        
        // Register rewrite rules
        $this->addRewriteRules();
        
        // Load plugin components
        $this->loadComponents();
    }

    /**
     * Widgets init hook
     * 
     * @return void
     */
    public function onWidgetsInit(): void {
        // Register widgets
        register_widget(Modules\Widgets\Handlers\ClassicBadgeWidget::class);
        register_widget(Modules\Widgets\Handlers\MiniBadgeWidget::class);
        register_widget(Modules\Widgets\Handlers\ReviewsSliderWidget::class);
    }

    /**
     * Admin init hook
     * 
     * @return void
     */
    public function onAdminInit(): void {
        // Register settings
        $this->registerSettings();
        
        // Register meta boxes
        $this->registerMetaBoxes();
    }

    /**
     * Initialize REST API
     * 
     * @return void
     */
    public function initRestApi(): void {
        add_action('rest_api_init', [$this, 'registerApiRoutes'], 20);
    }

    /**
     * Register REST API routes
     * 
     * @return void
     */
    public function registerApiRoutes(): void {
        // Reviews API
        register_rest_route(MYPROTECTOR_API_NAMESPACE, '/reviews', [
            'methods'  => 'GET',
            'callback' => [$this->container->get('api.reviews'), 'getReviews'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(MYPROTECTOR_API_NAMESPACE, '/reviews', [
            'methods'  => 'POST',
            'callback' => [$this->container->get('api.reviews'), 'createReview'],
            'permission_callback' => '__return_true',
        ]);

        // Companies API
        register_rest_route(MYPROTECTOR_API_NAMESPACE, '/companies', [
            'methods'  => 'GET',
            'callback' => [$this->container->get('api.companies'), 'getCompanies'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(MYPROTECTOR_API_NAMESPACE, '/companies/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this->container->get('api.companies'), 'getCompany'],
            'permission_callback' => '__return_true',
        ]);

        // Widgets API
        register_rest_route(MYPROTECTOR_API_NAMESPACE, '/widgets', [
            'methods'  => 'GET',
            'callback' => [$this->container->get('api.widgets'), 'getWidget'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Register shortcodes
     * 
     * @return void
     */
    public function registerShortcodes(): void {
        // Reviews list shortcode
        add_shortcode('mp_reviews', [$this, 'renderReviewsShortcode']);
        
        // Company search shortcode
        add_shortcode('mp_company_search', [$this, 'renderSearchShortcode']);
        
        // Trust badge shortcode
        add_shortcode('mp_trust_badge', [$this, 'renderTrustBadgeShortcode']);
        
        // Traffic light shortcode
        add_shortcode('mp_traffic_light', [$this, 'renderTrafficLightShortcode']);
    }

    /**
     * Render reviews shortcode
     * 
     * @param array $atts
     * @return string
     */
    public function renderReviewsShortcode($atts): string {
        $atts = shortcode_atts([
            'company_id' => null,
            'limit' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ], $atts, 'mp_reviews');

        $reviewsService = $this->container->get('reviews.service');
        $reviews = $reviewsService->getReviews([
            'company_id' => $atts['company_id'],
            'limit' => $atts['limit'],
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        ]);

        ob_start();
        include MYPROTECTOR_PATH . 'Templates/partials/reviews-list.php';
        return ob_get_clean();
    }

    /**
     * Render company search shortcode
     * 
     * @param array $atts
     * @return string
     */
    public function renderSearchShortcode($atts): string {
        ob_start();
        include MYPROTECTOR_PATH . 'Templates/partials/company-search.php';
        return ob_get_clean();
    }

    /**
     * Render trust badge shortcode
     * 
     * @param array $atts
     * @return string
     */
    public function renderTrustBadgeShortcode($atts): string {
        $atts = shortcode_atts([
            'company_id' => null,
            'style' => 'default',
        ], $atts, 'mp_trust_badge');

        $trafficService = $this->container->get('traffic.service');
        $status = $trafficService->getStatusDisplay($atts['company_id']);

        ob_start();
        include MYPROTECTOR_PATH . 'Templates/partials/trust-badge.php';
        return ob_get_clean();
    }

    /**
     * Render traffic light shortcode
     * 
     * @param array $atts
     * @return string
     */
    public function renderTrafficLightShortcode($atts): string {
        $atts = shortcode_atts([
            'company_id' => null,
            'size' => 'medium',
        ], $atts, 'mp_traffic_light');

        $trafficService = $this->container->get('traffic.service');
        $status = $trafficService->getStatusDisplay($atts['company_id']);

        ob_start();
        include MYPROTECTOR_PATH . 'Templates/partials/traffic-light.php';
        return ob_get_clean();
    }

    /**
     * Enqueue frontend assets
     * 
     * @return void
     */
    public function enqueueFrontendAssets(): void {
        // Global styles
        wp_enqueue_style(
            'myprotector-frontend',
            MYPROTECTOR_URL . 'Assets/css/frontend.css',
            [],
            MYPROTECTOR_ASSETS_VERSION
        );

        // Global scripts
        wp_enqueue_script(
            'myprotector-frontend',
            MYPROTECTOR_URL . 'Assets/js/frontend.js',
            ['jquery'],
            MYPROTECTOR_ASSETS_VERSION,
            true
        );

        // Localize script
        wp_localize_script('myprotector-frontend', 'mpConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mp_frontend'),
            'restUrl' => rest_url(MYPROTECTOR_API_NAMESPACE),
        ]);
    }

    /**
     * Enqueue admin assets
     * 
     * @param string $hook
     * @return void
     */
    public function enqueueAdminAssets(string $hook): void {
        // Admin styles
        wp_enqueue_style(
            'myprotector-admin',
            MYPROTECTOR_URL . 'Admin/assets/css/admin.css',
            [],
            MYPROTECTOR_ASSETS_VERSION
        );

        // Admin scripts
        wp_enqueue_script(
            'myprotector-admin',
            MYPROTECTOR_URL . 'Admin/assets/js/admin.js',
            ['jquery', 'wp-util'],
            MYPROTECTOR_ASSETS_VERSION,
            true
        );

        // Localize script
        wp_localize_script('myprotector-admin', 'mpAdminConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mp_admin'),
            'screen' => $hook,
        ]);
    }

    /**
     * Add custom cron schedules
     * 
     * @param array $schedules
     * @return array
     */
    public function addCronSchedules(array $schedules): array {
        $schedules['every_five_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => __('Every 5 Minutes', 'myprotector-platform'),
        ];

        $schedules['twice_daily'] = [
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Twice Daily', 'myprotector-platform'),
        ];

        return $schedules;
    }

    /**
     * Register custom post types
     * 
     * @return void
     */
    protected function registerPostTypes(): void {
        // Reviews CPT
        register_post_type('mp_review', [
            'labels' => [
                'name' => __('Reviews', 'myprotector-platform'),
                'singular_name' => __('Review', 'myprotector-platform'),
            ],
            'public' => true,
            'has_archive' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'reviews'],
            'supports' => ['title', 'editor', 'author', 'custom-fields'],
        ]);

        // Companies CPT
        register_post_type('mp_company', [
            'labels' => [
                'name' => __('Companies', 'myprotector-platform'),
                'singular_name' => __('Company', 'myprotector-platform'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'companies'],
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        ]);
    }

    /**
     * Register custom taxonomies
     * 
     * @return void
     */
    protected function registerTaxonomies(): void {
        // Company Categories
        register_taxonomy('mp_company_category', 'mp_company', [
            'labels' => [
                'name' => __('Company Categories', 'myprotector-platform'),
                'singular_name' => __('Category', 'myprotector-platform'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'company-category'],
        ]);

        // Review Tags
        register_taxonomy('mp_review_tag', 'mp_review', [
            'labels' => [
                'name' => __('Review Tags', 'myprotector-platform'),
                'singular_name' => __('Tag', 'myprotector-platform'),
            ],
            'hierarchical' => false,
            'show_in_rest' => true,
            'rewrite' => ['slug' => 'review-tag'],
        ]);
    }

    /**
     * Add rewrite rules
     * 
     * @return void
     */
    protected function addRewriteRules(): void {
        add_rewrite_rule(
            'company/([^/]+)/?$',
            'index.php?post_type=mp_company&name=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            'review/([^/]+)/?$',
            'index.php?post_type=mp_review&name=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            'dashboard/?$',
            'index.php?mp_dashboard=1',
            'top'
        );
    }

    /**
     * Register settings
     * 
     * @return void
     */
    protected function registerSettings(): void {
        register_setting('myprotector_general', 'mp_review_auto_approve', [
            'type' => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);

        register_setting('myprotector_general', 'mp_email_from_name', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting('myprotector_general', 'mp_email_from_email', [
            'sanitize_callback' => 'sanitize_email',
        ]);
    }

    /**
     * Register meta boxes
     * 
     * @return void
     */
    protected function registerMetaBoxes(): void {
        add_meta_box(
            'mp_review_details',
            __('Review Details', 'myprotector-platform'),
            [$this, 'renderReviewMetaBox'],
            'mp_review',
            'side'
        );

        add_meta_box(
            'mp_company_details',
            __('Company Details', 'myprotector-platform'),
            [$this, 'renderCompanyMetaBox'],
            'mp_company',
            'side'
        );
    }

    /**
     * Render review meta box
     * 
     * @param WP_Post $post
     * @return void
     */
    public function renderReviewMetaBox($post): void {
        $rating = get_post_meta($post->ID, '_mp_rating', true);
        $company_id = get_post_meta($post->ID, '_mp_company_id', true);
        $status = get_post_meta($post->ID, '_mp_status', true);
        ?>
        <p>
            <label for="mp_rating"><?php _e('Rating:', 'myprotector-platform'); ?></label>
            <select name="mp_rating" id="mp_rating">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php selected($rating, $i); ?>><?php echo str_repeat('⭐', $i); ?></option>
                <?php endfor; ?>
            </select>
        </p>
        <p>
            <label for="mp_company_id"><?php _e('Company ID:', 'myprotector-platform'); ?></label>
            <input type="number" name="mp_company_id" id="mp_company_id" value="<?php echo esc_attr($company_id); ?>">
        </p>
        <p>
            <label for="mp_status"><?php _e('Status:', 'myprotector-platform'); ?></label>
            <select name="mp_status" id="mp_status">
                <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending', 'myprotector-platform'); ?></option>
                <option value="approved" <?php selected($status, 'approved'); ?>><?php _e('Approved', 'myprotector-platform'); ?></option>
                <option value="rejected" <?php selected($status, 'rejected'); ?>><?php _e('Rejected', 'myprotector-platform'); ?></option>
            </select>
        </p>
        <?php
    }

    /**
     * Render company meta box
     * 
     * @param WP_Post $post
     * @return void
     */
    public function renderCompanyMetaBox($post): void {
        $trust_status = get_post_meta($post->ID, '_mp_trust_status', true);
        $claimed = get_post_meta($post->ID, '_mp_claimed', true);
        $total_reviews = get_post_meta($post->ID, '_mp_total_reviews', true);
        ?>
        <p>
            <label for="mp_trust_status"><?php _e('Trust Status:', 'myprotector-platform'); ?></label>
            <select name="mp_trust_status" id="mp_trust_status">
                <option value="bad" <?php selected($trust_status, 'bad'); ?>>🟢 <?php _e('Walking', 'myprotector-platform'); ?></option>
                <option value="shopping" <?php selected($trust_status, 'shopping'); ?>>🟡 <?php _e('Shopping', 'myprotector-platform'); ?></option>
                <option value="walking" <?php selected($trust_status, 'walking'); ?>>🔴 <?php _e('Bad', 'myprotector-platform'); ?></option>
            </select>
        </p>
        <p>
            <label>
                <input type="checkbox" name="mp_claimed" value="1" <?php checked($claimed, '1'); ?>>
                <?php _e('Claimed by Business', 'myprotector-platform'); ?>
            </label>
        </p>
        <p>
            <strong><?php _e('Total Reviews:', 'myprotector-platform'); ?></strong> <?php echo esc_html($total_reviews); ?>
        </p>
        <?php
    }

    /**
     * Load plugin components
     * 
     * @return void
     */
    protected function loadComponents(): void {
        // Register core services
        $this->registerCoreServices();
    }

    /**
     * Register core services
     * 
     * @return void
     */
    protected function registerCoreServices(): void {
        // Database service
        $this->container->singleton('database', function () {
            return new Services\Database\DatabaseService();
        });

        // Logger service
        $this->container->singleton('logger', function () {
            return new Services\Logger\LoggerService();
        });

        // Cache service
        $this->container->singleton('cache', function () {
            return new Services\Cache\CacheService();
        });

        // Reviews service
        $this->container->singleton('reviews.service', function () {
            return new Modules\Reviews\Services\ReviewService($this->container);
        });

        // Business service
        $this->container->singleton('business.service', function () {
            return new Modules\BusinessProfiles\Services\BusinessService($this->container);
        });

        // Traffic light service
        $this->container->singleton('traffic.service', function () {
            return new Modules\TrafficSignals\Services\TrafficLightService($this->container);
        });

        // Email service
        $this->container->singleton('email.service', function () {
            return new Modules\Emails\Services\EmailService($this->container);
        });

        // Widget service
        $this->container->singleton('widget.service', function () {
            return new Modules\Widgets\Services\WidgetService($this->container);
        });

        // API controllers
        $this->container->singleton('api.reviews', function () {
            return new API\Controllers\ReviewsController();
        });

        $this->container->singleton('api.companies', function () {
            return new API\Controllers\CompaniesController();
        });

        $this->container->singleton('api.widgets', function () {
            return new API\Controllers\WidgetsController();
        });
    }

    /**
     * Add plugin row meta
     * 
     * @param array $links
     * @param string $file
     * @return array
     */
    public function addPluginRowMeta(array $links, string $file): array {
        if ($file === MYPROTECTOR_BASENAME) {
            $links[] = '<a href="https://docs.myprotector.example.com" target="_blank">' . 
                       __('Documentation', 'myprotector-platform') . '</a>';
            $links[] = '<a href="https://support.myprotector.example.com" target="_blank">' . 
                       __('Support', 'myprotector-platform') . '</a>';
        }
        return $links;
    }

    /**
     * Add plugin action links
     * 
     * @param array $links
     * @return array
     */
    public function addPluginActionLinks(array $links): array {
        $new_links = [
            '<a href="' . admin_url('admin.php?page=myprotector-settings') . '">' . 
                         __('Settings', 'myprotector-platform') . '</a>',
        ];
        return array_merge($new_links, $links);
    }

    /**
     * Check WordPress version
     * 
     * @return void
     */
    protected function checkWordPressVersion(): void {
        global $wp_version;
        
        if (version_compare($wp_version, MYPROTECTOR_WP_MIN_VERSION, '<')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' .
                     sprintf(
                         __('MyProtector Platform requires WordPress %s or higher.', 'myprotector-platform'),
                         MYPROTECTOR_WP_MIN_VERSION
                     ) . '</p></div>';
            });
        }
    }

    /**
     * Check PHP version
     * 
     * @return void
     */
    protected function checkPhpVersion(): void {
        if (version_compare(PHP_VERSION, MYPROTECTOR_PHP_MIN_VERSION, '<')) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' .
                     sprintf(
                         __('MyProtector Platform requires PHP %s or higher.', 'myprotector-platform'),
                         MYPROTECTOR_PHP_MIN_VERSION
                     ) . '</p></div>';
            });
        }
    }
}