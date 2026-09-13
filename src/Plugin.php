<?php
namespace OpenNow;

use OpenNow\Admin\Settings;
use OpenNow\Frontend\Block;
use OpenNow\Frontend\Renderer;
use OpenNow\Frontend\Shortcode;

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
     * @var Renderer
     */
    private $renderer;

    /**
     * @var Shortcode
     */
    private $shortcode;

    /**
     * @var Block
     */
    private $block;

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
        $this->renderer = new Renderer();
        $this->shortcode = new Shortcode($this->renderer);
        $this->shortcode->register();
        $this->block = new Block($this->renderer);
        $this->block->register();

        if (is_admin()) {
            $this->settings = new Settings();
            $this->settings->register();
        }
    }
}
