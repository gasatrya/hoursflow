<?php
/**
 * OpenNow CTA uninstall handler.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;
defined( 'ABSPATH' ) || exit;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Autoloader.php';
\OpenNow\Autoloader::register( __DIR__ . DIRECTORY_SEPARATOR . 'src' );
\OpenNow\Uninstaller::uninstall();
