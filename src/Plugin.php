<?php
namespace OpenNow;

use OpenNow\Admin\Settings;

defined('ABSPATH') || exit;

/**
 * Main plugin service bootstrap.
 */
final class Plugin
{
    /**
     * @var self|null
     */
    private static $instance;

    /**
     * @var Settings|null
     */
    private $settings;

    /**
     * Boot the plugin once after all plugins have loaded.
     *
     * @return self
     */
    public static function boot()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return void
     */
    private function __construct()
    {
        if (is_admin()) {
            $this->settings = new Settings();
            $this->settings->register();
        }
    }
}
