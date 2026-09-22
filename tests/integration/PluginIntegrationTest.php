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
        if (function_exists('wp_set_current_user')) {
            wp_set_current_user(0);
        }
        delete_option(Schema::OPTION_NAME);
        delete_option(Schema::SCHEMA_OPTION_NAME);
        parent::tearDown();
    }

    public function testRunningWordPressPatchMatchesTheConfiguredCiVersion(): void
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

    public function testSerializedDynamicBlockUsesNestedOverridesAndKeepsLegacyBlocksWorking(): void
    {
        $config = $this->config();
        $config['cta']['closed']['status'] = 'We are closed.';
        update_option(Schema::OPTION_NAME, $config, false);

        $overrides = array(
            'open' => array(
                'label' => 'Serialized CTA',
                'action' => '/serialized/',
                'status' => '',
            ),
            'closed' => array(
                'label' => 'Serialized CTA',
                'action' => '/serialized/',
                'status' => '',
            ),
        );
        $attributes = array('overrides' => $overrides);
        $serialized = $this->serializedCta($overrides);
        $parsed = parse_blocks($serialized);

        $this->assertCount(1, $parsed);
        $this->assertSame($attributes, $parsed[0]['attrs']);

        $output = do_blocks($serialized);

        $this->assertSame(1, preg_match('/opennow-cta--(open|closed)/', $output));
        $this->assertStringContainsString('Serialized CTA', $output);
        $this->assertStringContainsString('href="/serialized/"', $output);
        $this->assertStringNotContainsString('Call Now', $output);
        $this->assertStringNotContainsString('Book online', $output);
        $this->assertStringNotContainsString('We are open.', $output);
        $this->assertStringNotContainsString('We are closed.', $output);
        $this->assertStringNotContainsString('opennow-cta__status', $output);

        $legacy_output = do_blocks('<!-- wp:opennow/cta /-->');
        $this->assertSame(1, preg_match('/opennow-cta--(open|closed)/', $legacy_output));
        $this->assertSame(1, preg_match('/Call Now|Book online/', $legacy_output));
        $this->assertStringNotContainsString('Serialized CTA', $legacy_output);
    }

    public function testRealShortcodeAndBlockStatusHidingPreservesLegacyOutput(): void
    {
        $config = $this->config();
        // Keep the real callbacks on a deterministic closed state; production evaluates the current instant.
        $config['schedule']['monday'] = array('type' => 'closed');
        $config['cta']['closed']['status'] = 'We are closed.';
        update_option(Schema::OPTION_NAME, $config, false);

        $legacy_shortcode = do_shortcode('[opennow_cta]');
        $legacy_block = do_blocks('<!-- wp:opennow/cta /-->');

        $this->assertSame($legacy_shortcode, $legacy_block);
        $this->assertStringContainsString('href="/booking/"', $legacy_shortcode);
        $this->assertStringContainsString('Book online', $legacy_shortcode);
        $this->assertStringContainsString('opennow-cta__status', $legacy_shortcode);
        $this->assertSame(
            1,
            preg_match('/opennow-cta--(open|closed)/', $legacy_shortcode, $matches)
        );
        $selected_state = $matches[1];
        $other_state = 'open' === $selected_state ? 'closed' : 'open';

        $hidden_overrides = array(
            'open' => array(
                'hideStatus' => true,
            ),
            'closed' => array(
                'hideStatus' => true,
            ),
        );
        $hidden_shortcode = do_shortcode(
            '[opennow_cta hide_status="1" label="Ignored"]Ignored content[/opennow_cta]'
        );
        $hidden_block = do_blocks($this->serializedCta($hidden_overrides));

        $this->assertSame($hidden_shortcode, $hidden_block);
        $this->assertStringNotContainsString('opennow-cta__status', $hidden_shortcode);
        $this->assertStringNotContainsString('Ignored', $hidden_shortcode);

        $this->assertSame(
            $legacy_block,
            do_blocks(
                $this->serializedCta(
                    array(
                        $other_state => array('hideStatus' => true),
                    )
                )
            )
        );
        $this->assertSame(
            $legacy_block,
            do_blocks(
                $this->serializedCta(
                    array(
                        $selected_state => array('hideStatus' => 'true'),
                    )
                )
            )
        );
        $this->assertStringContainsString(
            'opennow-cta__status',
            do_shortcode('[opennow_cta hide_status="true"]')
        );
    }

    public function testSerializedDynamicBlockFallsBackPerFieldAndNeverRescuesInvalidGlobalState(): void
    {
        update_option(Schema::OPTION_NAME, $this->config(), false);

        $overrides = array(
            'open' => array(
                'label' => 'Override label',
                'action' => 'javascript:bad',
                'status' => 'Override status',
            ),
            'closed' => array(
                'label' => 'Override label',
                'action' => 'javascript:bad',
                'status' => 'Override status',
            ),
        );
        $output = do_blocks($this->serializedCta($overrides));

        $this->assertSame(
            1,
            preg_match('/opennow-cta opennow-cta--(open|closed)/', $output, $matches)
        );
        $state = $matches[1];
        $selected_action = 'open' === $state ? 'tel:+123456789' : '/booking/';
        $other_action = 'open' === $state ? '/booking/' : 'tel:+123456789';

        $this->assertStringContainsString('Override label', $output);
        $this->assertStringContainsString('href="' . $selected_action . '"', $output);
        $this->assertStringContainsString('Override status', $output);
        $this->assertStringNotContainsString('javascript:', $output);
        $this->assertStringNotContainsString('href="' . $other_action . '"', $output);

        $invalid_config = $this->config();
        $invalid_config['cta']['open']['action'] = 'javascript:bad';
        $invalid_config['cta']['closed']['action'] = 'javascript:bad';
        update_option(Schema::OPTION_NAME, $invalid_config, false);

        $valid_overrides = array(
            'open' => array(
                'label' => 'Rescue attempt',
                'action' => '/rescue/',
                'status' => 'Rescued',
            ),
            'closed' => array(
                'label' => 'Rescue attempt',
                'action' => '/rescue/',
                'status' => 'Rescued',
            ),
        );

        $this->assertSame('', do_blocks($this->serializedCta($valid_overrides)));
    }

    public function testTypographyBlockSupportsReachTheRenderedCtaWrapper(): void
    {
        update_option(Schema::OPTION_NAME, $this->config(), false);
        $attributes = array(
            'fontFamily' => 'heading',
            'fontSize' => 'large',
            'style' => array(
                'typography' => array(
                    'fontStyle' => 'italic',
                    'fontWeight' => '700',
                    'letterSpacing' => '0.05em',
                    'lineHeight' => '1.4',
                    'textTransform' => 'uppercase',
                ),
            ),
        );
        $serialized = serialize_blocks(
            array(
                array(
                    'blockName' => 'opennow/cta',
                    'attrs' => $attributes,
                    'innerBlocks' => array(),
                    'innerHTML' => '',
                    'innerContent' => array(),
                ),
            )
        );

        $output = do_blocks($serialized);

        $this->assertStringContainsString('has-heading-font-family', $output);
        $this->assertStringContainsString('has-large-font-size', $output);
        $this->assertStringContainsString('font-style:italic', $output);
        $this->assertStringContainsString('font-weight:700', $output);
        $this->assertStringContainsString('letter-spacing:0.05em', $output);
        $this->assertStringContainsString('line-height:1.4', $output);
        $this->assertStringContainsString('text-transform:uppercase', $output);
        $this->assertStringContainsString('--opennow-cta-background-color:', $output);
        $this->assertStringContainsString('--opennow-cta-text-color:', $output);
    }

    public function testColorBlockSupportsOverrideOnlyTheRenderedCtaLink(): void
    {
        update_option(Schema::OPTION_NAME, $this->config(), false);
        $attributes = array(
            'textColor' => 'vivid-red',
            'style' => array(
                'color' => array(
                    'background' => '#123456',
                ),
            ),
        );
        $serialized = serialize_blocks(
            array(
                array(
                    'blockName' => 'opennow/cta',
                    'attrs' => $attributes,
                    'innerBlocks' => array(),
                    'innerHTML' => '',
                    'innerContent' => array(),
                ),
            )
        );

        $output = do_blocks($serialized);
        $shortcode_output = do_shortcode('[opennow_cta]');

        $this->assertStringContainsString('has-text-color', $output);
        $this->assertStringContainsString('has-vivid-red-color', $output);
        $this->assertStringContainsString('has-background', $output);
        $this->assertStringContainsString('background-color:', $output);
        $this->assertStringContainsString('#123456', $output);
        $this->assertStringNotContainsString('has-vivid-red-color', $shortcode_output);
        $this->assertStringNotContainsString('#123456', $shortcode_output);
    }

    public function testRestBlockRendererReturnsAuthenticatedNestedOverrideOutput(): void
    {
        $config = $this->config();
        $config['cta']['closed']['status'] = 'Closed globally.';
        update_option(Schema::OPTION_NAME, $config, false);
        $administrator = self::factory()->user->create(array('role' => 'administrator'));
        wp_set_current_user($administrator);

        $overrides = array(
            'open' => array(
                'label' => 'REST CTA',
                'action' => '/rest-cta/',
                'status' => 'REST status',
                'hideStatus' => true,
            ),
            'closed' => array(
                'label' => 'REST CTA',
                'action' => '/rest-cta/',
                'status' => 'REST status',
                'hideStatus' => true,
            ),
        );
        $request = new \WP_REST_Request('POST', '/wp/v2/block-renderer/opennow/cta');
        $request->set_header('content-type', 'application/json');
        $request->set_body(
            wp_json_encode(
                array(
                    'context' => 'edit',
                    'attributes' => array(
                        'overrides' => $overrides,
                        'fontFamily' => 'heading',
                        'fontSize' => 'large',
                        'textColor' => 'vivid-red',
                        'style' => array(
                            'color' => array(
                                'background' => '#123456',
                            ),
                            'typography' => array(
                                'fontWeight' => '700',
                                'lineHeight' => '1.4',
                            ),
                        ),
                    ),
                )
            )
        );

        $response = rest_do_request($request);
        $this->assertNotWPError($response);
        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('rendered', $data);
        $this->assertStringContainsString('REST CTA', $data['rendered']);
        $this->assertStringContainsString('href="/rest-cta/"', $data['rendered']);
        $this->assertStringContainsString('has-heading-font-family', $data['rendered']);
        $this->assertStringContainsString('has-large-font-size', $data['rendered']);
        $this->assertStringContainsString('has-vivid-red-color', $data['rendered']);
        $this->assertStringContainsString('#123456', $data['rendered']);
        $this->assertStringContainsString('font-weight:700', $data['rendered']);
        $this->assertStringContainsString('line-height:1.4', $data['rendered']);
        $this->assertStringNotContainsString('opennow-cta__status', $data['rendered']);
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
     * Serialize a dynamic CTA block with the same shape WordPress stores.
     *
     * @param array<string, mixed> $overrides
     * @return string
     */
    private function serializedCta(array $overrides): string
    {
        return serialize_blocks(
            array(
                array(
                    'blockName' => 'opennow/cta',
                    'attrs' => array('overrides' => $overrides),
                    'innerBlocks' => array(),
                    'innerHTML' => '',
                    'innerContent' => array(),
                ),
            )
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
