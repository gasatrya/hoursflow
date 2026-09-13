<?php
namespace OpenNow\Tests\Integration;

use OpenNow\Admin\Settings;
use OpenNow\Config\Repository;
use OpenNow\Config\Schema;
use OpenNow\Frontend\Block;
use OpenNow\Frontend\Renderer;
use OpenNow\Frontend\Shortcode;
use OpenNow\Lifecycle;
use OpenNow\Schedule\Evaluator;
use OpenNow\Uninstaller;
use WP_UnitTestCase;

final class PluginIntegrationTest extends WP_UnitTestCase
{
    protected function tearDown(): void
    {
        if (function_exists('deactivate_plugins')) {
            deactivate_plugins('opennow/opennow.php', true);
        }
        delete_option(Schema::OPTION_NAME);
        delete_option(Schema::SCHEMA_OPTION_NAME);
        parent::tearDown();
    }

    public function testRunningWordPressPatchMatchesTheExactCiMatrixVersion(): void
    {
        $expected_version = getenv('WP_VERSION');
        $this->assertIsString($expected_version);
        $this->assertNotSame('', $expected_version);
        $this->assertSame($expected_version, get_bloginfo('version'));
    }

    public function testRealWordPressRegistersTheDynamicBlockAndShortcode(): void
    {
        $registry = \WP_Block_Type_Registry::get_instance();
        $this->assertTrue($registry->is_registered('opennow/cta'));
        $block_type = $registry->get_registered('opennow/cta');
        $this->assertIsObject($block_type);
        $this->assertIsArray($block_type->render_callback);
        $this->assertSame('render', $block_type->render_callback[1]);
        $this->assertTrue(shortcode_exists('opennow_cta'));
    }

    public function testProductionPackageActivatesWithoutOutputOrDevelopmentDependencies(): void
    {
        $plugin_file = getenv('OPENNOW_TEST_PLUGIN_FILE');
        if (!is_string($plugin_file) || '' === $plugin_file) {
            $this->markTestSkipped('Set OPENNOW_TEST_PLUGIN_FILE to test a production package.');
        }

        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugin = plugin_basename($plugin_file);

        ob_start();
        $result = activate_plugin($plugin, '', false, false);
        $output = (string) ob_get_clean();

        $this->assertNull($result);
        $this->assertSame('', $output);
        $this->assertTrue(is_plugin_active($plugin));
        $this->assertFileDoesNotExist(dirname($plugin_file) . '/vendor');
        $this->assertFileDoesNotExist(dirname($plugin_file) . '/node_modules');
    }

    /**
     * @dataProvider deterministicInstants
     */
    public function testSavedSettingsKeepShortcodeAndBlockOutputInParity(
        string $instant,
        string $expected_state
    ): void {
        $settings = new Settings();
        $saved = $settings->sanitize($this->config());
        $this->assertIsArray($saved);
        $this->assertSame(array(), get_settings_errors(Schema::OPTION_NAME));
        update_option(Schema::OPTION_NAME, $saved, false);

        $renderer = new Renderer(
            new Repository(),
            new Evaluator(static function () use ($instant): \DateTimeInterface {
                return new \DateTimeImmutable($instant, new \DateTimeZone('UTC'));
            })
        );
        $shortcode = new Shortcode($renderer);
        $shortcode->register();
        $block = new Block($renderer);

        $shortcode_output = do_shortcode('[opennow_cta]');
        $block_output = $block->render(array(), '', null);

        $this->assertSame($saved, get_option(Schema::OPTION_NAME));
        $this->assertSame($shortcode_output, $block_output);
        $this->assertStringContainsString('opennow-cta--' . $expected_state, $block_output);
    }

    public function testRealWordPressLifecycleRetainsOnDeactivateAndDeletesOnUninstall(): void
    {
        Lifecycle::activate();
        $this->assertSame(1, get_option(Schema::SCHEMA_OPTION_NAME));

        $saved = $this->config();
        update_option(Schema::OPTION_NAME, $saved, false);
        Lifecycle::deactivate();
        $this->assertSame($saved, get_option(Schema::OPTION_NAME));

        Uninstaller::uninstall();
        $this->assertFalse(get_option(Schema::OPTION_NAME, false));
        $this->assertFalse(get_option(Schema::SCHEMA_OPTION_NAME, false));
    }

    public function deterministicInstants(): array
    {
        return array(
            'open instant' => array('2024-01-08 10:00:00', 'open'),
            'closed instant' => array('2024-01-08 18:00:00', 'closed'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        $schedule = array();
        foreach (Schema::days() as $day) {
            $schedule[$day] = array('type' => 'closed');
        }
        $schedule['monday'] = array(
            'type' => 'period',
            'opens' => '09:00',
            'closes' => '17:00',
        );

        return array(
            'timezone' => 'UTC',
            'schedule' => $schedule,
            'cta' => array(
                'open' => array(
                    'label' => 'Call Now',
                    'action' => 'tel:+123456789',
                    'status' => 'We are open.',
                ),
                'closed' => array(
                    'label' => 'Book online',
                    'action' => '/booking/',
                    'status' => '',
                ),
            ),
            'appearance' => array(
                'background_color' => '',
                'text_color' => '',
            ),
        );
    }
}
