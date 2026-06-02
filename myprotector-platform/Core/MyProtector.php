<?php
/**
 * MyProtector Core - Main Plugin Class
 * 
 * @package MyProtector\Core
 * @version 1.0.0
 */

namespace MyProtector\Core;

class MyProtector {
    /**
     * Plugin instance
     * 
     * @var MyProtector
     */
    protected static $instance = null;

    /**
     * Bootstrap instance
     * 
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * Service container
     * 
     * @var Services\Container\ServiceContainer
     */
    protected $container;

    /**
     * Registered modules
     * 
     * @var array
     */
    protected $modules = [];

    /**
     * Get plugin instance (Singleton)
     * 
     * @return MyProtector
     */
    public static function getInstance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    protected function __construct() {
        $this->bootstrap = new Bootstrap($this);
        $this->container = new Services\Container\ServiceContainer();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Run the plugin
     * 
     * @return void
     */
    public function run(): void {
        // Register activation/deactivation hooks
        $this->registerActivationHooks();
        
        // Load plugin textdomain
        $this->loadTextdomain();
        
        // Register modules
        $this->registerModules();
        
        // Boot all modules
        $this->bootstrap->bootModules();
        
        // Register hooks
        $this->bootstrap->registerHooks();
        
        // Initialize REST API
        $this->bootstrap->initRestApi();
        
        // Register shortcodes
        $this->bootstrap->registerShortcodes();
    }

    /**
     * Register activation/deactivation hooks
     * 
     * @return void
     */
    protected function registerActivationHooks(): void {
        // Activation is handled via register_activation_hook in main file
        // This method can register additional hooks if needed
    }

    /**
     * Load plugin textdomain
     * 
     * @return void
     */
    protected function loadTextdomain(): void {
        load_plugin_textdomain(
            'myprotector-platform',
            false,
            dirname(MYPROTECTOR_BASENAME) . '/languages'
        );
    }

    /**
     * Register all modules
     * 
     * @return void
     */
    protected function registerModules(): void {
        $moduleClasses = [
            Modules\Reviews\Reviews::class,
            Modules\BusinessProfiles\BusinessProfiles::class,
            Modules\TrafficSignals\TrafficSignals::class,
            Modules\Dashboards\Dashboards::class,
            Modules\Resellers\Resellers::class,
            Modules\Widgets\Widgets::class,
            Modules\Emails\Emails::class,
            Modules\WooCommerce\WooCommerce::class,
            Modules\Admin\Admin::class,
        ];

        foreach ($moduleClasses as $moduleClass) {
            $this->modules[$moduleClass::getName()] = new $moduleClass($this);
        }
    }

    /**
     * Get module by name
     * 
     * @param string $name
     * @return ModuleInterface|null
     */
    public function getModule(string $name): ?ModuleInterface {
        return $this->modules[$name] ?? null;
    }

    /**
     * Get all registered modules
     * 
     * @return array
     */
    public function getModules(): array {
        return $this->modules;
    }

    /**
     * Get service from container
     * 
     * @param string $id
     * @return mixed
     */
    public function get(string $id) {
        return $this->container->get($id);
    }

    /**
     * Check if service exists in container
     * 
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool {
        return $this->container->has($id);
    }

    /**
     * Register a service in the container
     * 
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function register(string $id, callable $factory): void {
        $this->container->set($id, $factory);
    }

    /**
     * Register a singleton service
     * 
     * @param string $id
     * @param callable $factory
     * @return void
     */
    public function singleton(string $id, callable $factory): void {
        $this->container->singleton($id, $factory);
    }

    /**
     * Get plugin path
     * 
     * @param string $path
     * @return string
     */
    public function getPath(string $path = ''): string {
        return MYPROTECTOR_PATH . ltrim($path, '/');
    }

    /**
     * Get plugin URL
     * 
     * @param string $path
     * @return string
     */
    public function getUrl(string $path = ''): string {
        return MYPROTECTOR_URL . ltrim($path, '/');
    }

    /**
     * Get plugin version
     * 
     * @return string
     */
    public function getVersion(): string {
        return MYPROTECTOR_VERSION;
    }

    /**
     * Get plugin basename
     * 
     * @return string
     */
    public function getBasename(): string {
        return MYPROTECTOR_BASENAME;
    }

    /**
     * Get bootstrap instance
     * 
     * @return Bootstrap
     */
    public function getBootstrap(): Bootstrap {
        return $this->bootstrap;
    }

    /**
     * Get container instance
     * 
     * @return Services\Container\ServiceContainer
     */
    public function getContainer(): Services\Container\ServiceContainer {
        return $this->container;
    }
}