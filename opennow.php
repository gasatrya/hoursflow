<?php
/**
 * Plugin Name: OpenNow CTA
 * Description: Automatically show the right call to action based on business hours.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: opennow
 * Domain Path: /languages
 */

defined('ABSPATH') || exit;

if (!defined('OPENNOW_VERSION')) {
    define('OPENNOW_VERSION', '0.1.0');
}

if (!defined('OPENNOW_PLUGIN_FILE')) {
    define('OPENNOW_PLUGIN_FILE', __FILE__);
}

if (!defined('OPENNOW_PLUGIN_DIR')) {
    define('OPENNOW_PLUGIN_DIR', __DIR__ . '/');
}

require_once OPENNOW_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . 'Autoloader.php';
\OpenNow\Autoloader::register(OPENNOW_PLUGIN_DIR . 'src');

register_activation_hook(OPENNOW_PLUGIN_FILE, array('OpenNow\\Lifecycle', 'activate'));
register_deactivation_hook(OPENNOW_PLUGIN_FILE, array('OpenNow\\Lifecycle', 'deactivate'));

add_action('plugins_loaded', array('OpenNow\\Plugin', 'boot'));
