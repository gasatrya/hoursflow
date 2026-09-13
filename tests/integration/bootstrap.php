<?php

$opennow_tests_dir = getenv('WP_TESTS_DIR');
if (!is_string($opennow_tests_dir) || '' === $opennow_tests_dir) {
    $opennow_tests_dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'wordpress-tests-lib';
}

$opennow_polyfills = getenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH');
if (!is_string($opennow_polyfills) || '' === $opennow_polyfills) {
    $opennow_polyfills = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor'
        . DIRECTORY_SEPARATOR . 'yoast' . DIRECTORY_SEPARATOR . 'phpunit-polyfills';
    putenv('WP_TESTS_PHPUNIT_POLYFILLS_PATH=' . $opennow_polyfills);
}

$opennow_tests_bootstrap = rtrim($opennow_tests_dir, '/\\')
    . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'bootstrap.php';
if (!is_file($opennow_tests_bootstrap)) {
    throw new RuntimeException(
        'WordPress tests are not installed. Run bin/install-wp-tests.sh with an exact WordPress version.'
    );
}

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once rtrim($opennow_tests_dir, '/\\') . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'functions.php';

/**
 * Load the plugin through the WordPress test bootstrap.
 *
 * @return void
 */
function opennow_integration_load_plugin()
{
    $plugin_file = getenv('OPENNOW_TEST_PLUGIN_FILE');
    if (!is_string($plugin_file) || '' === $plugin_file) {
        $plugin_file = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'opennow.php';
    }

    if (!is_file($plugin_file)) {
        throw new RuntimeException('The OpenNow plugin package entry point is missing.');
    }

    require $plugin_file;
}

tests_add_filter('muplugins_loaded', 'opennow_integration_load_plugin');
require $opennow_tests_bootstrap;
