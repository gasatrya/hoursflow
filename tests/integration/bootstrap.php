<?php

$hoursflow_tests_dir = getenv('WP_TESTS_DIR');
if (!is_string($hoursflow_tests_dir) || '' === $hoursflow_tests_dir) {
    $hoursflow_tests_dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'wordpress-tests-lib';
}

$hoursflow_polyfills = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if (!is_string($hoursflow_polyfills) || '' === $hoursflow_polyfills) {
    $hoursflow_polyfills = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor'
        . DIRECTORY_SEPARATOR . 'yoast' . DIRECTORY_SEPARATOR . 'phpunit-polyfills';
    putenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH=' . $hoursflow_polyfills);
}

$hoursflow_tests_bootstrap = rtrim($hoursflow_tests_dir, '/\\')
    . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'bootstrap.php';
if (!is_file($hoursflow_tests_bootstrap)) {
    throw new RuntimeException(
        'WordPress tests are not installed. Run bin/install-wp-tests.sh with an exact WordPress version.'
    );
}

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once rtrim($hoursflow_tests_dir, '/\\') . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';

/**
 * Load the plugin through the WordPress test bootstrap.
 *
 * @return void
 */
function hoursflow_integration_load_plugin()
{
    $plugin_file = getenv('HOURSFLOW_TEST_PLUGIN_FILE');
    if (!is_string($plugin_file) || '' === $plugin_file) {
        $plugin_file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'hoursflow.php';
    }

    if (!is_file($plugin_file)) {
        throw new RuntimeException('The HoursFlow plugin package entry point is missing.');
    }

    require $plugin_file;
}

tests_add_filter('muplugins_loaded', 'hoursflow_integration_load_plugin');
require $hoursflow_tests_bootstrap;
