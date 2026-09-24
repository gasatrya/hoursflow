<?php
/**
 * Plugin Name: HoursFlow — Business Hours CTA
 * Description: Show an open or closed call to action based on your weekly business hours.
 * Version: 0.1.0
 * Author: Ga Satrya
 * Author URI: https://gasatrya.com/
 * Plugin URI: https://gasatrya.com/wp-plugins/hoursflow/
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hoursflow
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'HOURSFLOW_VERSION' ) ) {
	define( 'HOURSFLOW_VERSION', '0.1.0' );
}

if ( ! defined( 'HOURSFLOW_PLUGIN_FILE' ) ) {
	define( 'HOURSFLOW_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'HOURSFLOW_PLUGIN_DIR' ) ) {
	define( 'HOURSFLOW_PLUGIN_DIR', __DIR__ . '/' );
}

require_once HOURSFLOW_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . 'Autoloader.php';
\HoursFlow\Autoloader::register( HOURSFLOW_PLUGIN_DIR . 'src' );

register_activation_hook( HOURSFLOW_PLUGIN_FILE, array( 'HoursFlow\\Lifecycle', 'activate' ) );
register_deactivation_hook( HOURSFLOW_PLUGIN_FILE, array( 'HoursFlow\\Lifecycle', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'HoursFlow\\Plugin', 'boot' ) );
