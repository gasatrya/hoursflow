<?php
namespace HoursFlow\Tests;

use HoursFlow\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class BootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        hoursflow_reset_wp_stubs();

        $reflection = new ReflectionClass(Plugin::class);
        $property = $reflection->getProperty('instance');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $property->setValue(null, null);
    }

    public function testMainBootstrapRegistersLifecycleAndAdminSettingsConditionally(): void
    {
        $GLOBALS['hoursflow_test_is_admin'] = true;
        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'hoursflow.php';

        $this->assertTrue(defined('HOURSFLOW_VERSION'));
        $this->assertSame('0.1.0', HOURSFLOW_VERSION);
        $this->assertSame(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'hoursflow.php', HOURSFLOW_PLUGIN_FILE);
        $this->assertDirectoryExists(HOURSFLOW_PLUGIN_DIR);
        $this->assertCount(1, $GLOBALS['hoursflow_test_activation_hooks']);
        $this->assertCount(1, $GLOBALS['hoursflow_test_deactivation_hooks']);
        $this->assertCount(1, $GLOBALS['hoursflow_test_hooks']['plugins_loaded']);

        do_action('plugins_loaded');

        $this->assertArrayHasKey('hoursflow_cta', $GLOBALS['hoursflow_test_shortcodes']);
        $this->assertArrayHasKey('init', $GLOBALS['hoursflow_test_hooks']);
        $this->assertCount(1, $GLOBALS['hoursflow_test_hooks']['init']);
        do_action('init');
        $this->assertCount(1, $GLOBALS['hoursflow_test_registered_blocks']);
        $this->assertStringEndsWith(
            DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'blocks' . DIRECTORY_SEPARATOR . 'cta',
            $GLOBALS['hoursflow_test_registered_blocks'][0]['block_type']
        );
        $this->assertArrayHasKey('render_callback', $GLOBALS['hoursflow_test_registered_blocks'][0]['args']);
        $this->assertArrayHasKey('admin_init', $GLOBALS['hoursflow_test_hooks']);
        $this->assertCount(2, $GLOBALS['hoursflow_test_hooks']['admin_init']);
        $this->assertArrayNotHasKey('admin_notices', $GLOBALS['hoursflow_test_hooks']);
        $this->assertSame(array(), $GLOBALS['hoursflow_test_enqueued_styles']);
    }

    public function testFrontendBootstrapDoesNotInstantiateOrRegisterAdminSettings(): void
    {
        $GLOBALS['hoursflow_test_is_admin'] = false;
        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'hoursflow.php';

        do_action('plugins_loaded');

        $this->assertArrayHasKey('hoursflow_cta', $GLOBALS['hoursflow_test_shortcodes']);
        $this->assertArrayHasKey('init', $GLOBALS['hoursflow_test_hooks']);
        do_action('init');
        $this->assertCount(1, $GLOBALS['hoursflow_test_registered_blocks']);
        $this->assertArrayNotHasKey('admin_init', $GLOBALS['hoursflow_test_hooks']);
        $this->assertSame(array(), $GLOBALS['hoursflow_test_enqueued_styles']);
    }
}
