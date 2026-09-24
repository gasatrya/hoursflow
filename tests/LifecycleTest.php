<?php
namespace HoursFlow\Tests;

use HoursFlow\Config\Schema;
use HoursFlow\Lifecycle;
use HoursFlow\Uninstaller;
use PHPUnit\Framework\TestCase;

final class LifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        hoursflow_reset_wp_stubs();
    }

    public function testActivationIsRepeatSafeAndDoesNotCreateConfiguration(): void
    {
        Lifecycle::activate();
        Lifecycle::activate();

        $this->assertSame(1, $GLOBALS['hoursflow_test_options'][Schema::SCHEMA_OPTION_NAME]);
        $this->assertArrayNotHasKey(Schema::OPTION_NAME, $GLOBALS['hoursflow_test_options']);
        $this->assertCount(2, $GLOBALS['hoursflow_test_option_calls']);
        $this->assertSame(
            array(
                'function' => 'add_option',
                'option' => Schema::SCHEMA_OPTION_NAME,
                'value' => 1,
                'deprecated' => '',
                'autoload' => false,
            ),
            $GLOBALS['hoursflow_test_option_calls'][0]
        );
    }

    public function testActivationPreservesRetainedConfigurationAndDeactivationIsNoOp(): void
    {
        $retained = array('retained' => true);
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $retained;

        Lifecycle::activate();
        Lifecycle::deactivate();

        $this->assertSame($retained, $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME]);
        $this->assertArrayHasKey(Schema::SCHEMA_OPTION_NAME, $GLOBALS['hoursflow_test_options']);
        $this->assertCount(1, $GLOBALS['hoursflow_test_option_calls']);
    }

    public function testUninstallRemovesConfigurationAndSchemaMarker(): void
    {
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = array('saved' => true);
        $GLOBALS['hoursflow_test_options'][Schema::SCHEMA_OPTION_NAME] = Schema::VERSION;

        Uninstaller::uninstall();

        $this->assertArrayNotHasKey(Schema::OPTION_NAME, $GLOBALS['hoursflow_test_options']);
        $this->assertArrayNotHasKey(Schema::SCHEMA_OPTION_NAME, $GLOBALS['hoursflow_test_options']);
        $this->assertSame(
            array('delete_option', 'delete_option'),
            array_column($GLOBALS['hoursflow_test_option_calls'], 'function')
        );
    }
}
