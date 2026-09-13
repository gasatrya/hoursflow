<?php
namespace OpenNow\Tests;

use OpenNow\Config\Schema;
use OpenNow\Lifecycle;
use OpenNow\Uninstaller;
use PHPUnit\Framework\TestCase;

final class LifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        opennow_reset_wp_stubs();
    }

    public function testActivationIsRepeatSafeAndDoesNotCreateConfiguration(): void
    {
        Lifecycle::activate();
        Lifecycle::activate();

        $this->assertSame(1, $GLOBALS['opennow_test_options'][Schema::SCHEMA_OPTION_NAME]);
        $this->assertArrayNotHasKey(Schema::OPTION_NAME, $GLOBALS['opennow_test_options']);
        $this->assertCount(2, $GLOBALS['opennow_test_option_calls']);
        $this->assertSame(
            array(
                'function' => 'add_option',
                'option' => Schema::SCHEMA_OPTION_NAME,
                'value' => 1,
                'deprecated' => '',
                'autoload' => false,
            ),
            $GLOBALS['opennow_test_option_calls'][0]
        );
    }

    public function testActivationPreservesRetainedConfigurationAndDeactivationIsNoOp(): void
    {
        $retained = array('retained' => true);
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $retained;

        Lifecycle::activate();
        Lifecycle::deactivate();

        $this->assertSame($retained, $GLOBALS['opennow_test_options'][Schema::OPTION_NAME]);
        $this->assertArrayHasKey(Schema::SCHEMA_OPTION_NAME, $GLOBALS['opennow_test_options']);
        $this->assertCount(1, $GLOBALS['opennow_test_option_calls']);
    }

    public function testUninstallRemovesConfigurationAndSchemaMarker(): void
    {
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = array('saved' => true);
        $GLOBALS['opennow_test_options'][Schema::SCHEMA_OPTION_NAME] = Schema::VERSION;

        Uninstaller::uninstall();

        $this->assertArrayNotHasKey(Schema::OPTION_NAME, $GLOBALS['opennow_test_options']);
        $this->assertArrayNotHasKey(Schema::SCHEMA_OPTION_NAME, $GLOBALS['opennow_test_options']);
        $this->assertSame(
            array('delete_option', 'delete_option'),
            array_column($GLOBALS['opennow_test_option_calls'], 'function')
        );
    }
}
