<?php
/**
 * MyProtector - Business Profiles Module
 * 
 * @package MyProtector\Modules\BusinessProfiles
 */

namespace MyProtector\Modules\BusinessProfiles;

use MyProtector\Core\Module;

class BusinessProfiles extends Module {
    protected $name = 'business-profiles';

    protected function getModuleDirectory(): string {
        return 'BusinessProfiles';
    }

    public function boot(): void {
        // Initialize business profile functionality
    }

    public function registerHooks(): void {
        // Register business profile hooks
    }
}