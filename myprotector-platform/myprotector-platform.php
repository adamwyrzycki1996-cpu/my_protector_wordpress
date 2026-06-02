<?php
/**
 * MyProtector Platform - Main Plugin File
 * 
 * @package MyProtector
 * @version 1.0.0
 */

namespace MyProtector;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load configuration
require_once __DIR__ . '/config.php';

// Autoloader
require_once __DIR__ . '/myprotector-platform-loader.php';

// Bootstrap the plugin
use MyProtector\Core\MyProtector;

/**
 * Get the main plugin instance
 * 
 * @return MyProtector
 */
function myprotector(): MyProtector {
    return MyProtector::getInstance();
}

// Initialize the plugin
myprotector()->run();