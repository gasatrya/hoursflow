<?php
namespace OpenNow\Tests;

use OpenNow\Config\Repository;
use OpenNow\Config\Schema;
use OpenNow\Frontend\Block;
use OpenNow\Frontend\Renderer;
use OpenNow\Frontend\Shortcode;
use OpenNow\Schedule\Evaluator;
use PHPUnit\Framework\TestCase;

final class BlockTest extends TestCase
{
    protected function setUp(): void
    {
        opennow_reset_wp_stubs();
    }

    public function testBlockRegistersGeneratedMetadataOnInitWithItsRendererCallback(): void
    {
        $renderer = $this->rendererAt('2024-01-08 10:00:00');
        $block = new Block($renderer);
        $block->register();

        $this->assertArrayHasKey('init', $GLOBALS['opennow_test_hooks']);
        $this->assertCount(1, $GLOBALS['opennow_test_hooks']['init']);
        $this->assertSame(array(), $GLOBALS['opennow_test_registered_blocks']);

        do_action('init');

        $this->assertCount(1, $GLOBALS['opennow_test_registered_blocks']);
        $registration = $GLOBALS['opennow_test_registered_blocks'][0];
        $this->assertSame(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'blocks' . DIRECTORY_SEPARATOR . 'cta',
            $registration['block_type']
        );
        $this->assertSame(
            array($block, 'render'),
            $registration['args']['render_callback']
        );
    }

    public function testBlockAndShortcodeStayInOutputParityAcrossStateChanges(): void
    {
        $instant = '2024-01-08 10:00:00';
        $renderer = new Renderer(
            new Repository(),
            new Evaluator(static function () use (&$instant): \DateTimeInterface {
                return new \DateTimeImmutable($instant, new \DateTimeZone('UTC'));
            })
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $this->config();

        $block = new Block($renderer);
        $shortcode = new Shortcode($renderer);
        $block->register();
        $shortcode->register();
        do_action('init');

        $block_callback = $GLOBALS['opennow_test_registered_blocks'][0]['args']['render_callback'];
        $shortcode_callback = $GLOBALS['opennow_test_shortcodes']['opennow_cta'];

        $open_block = call_user_func(
            $block_callback,
            array('label' => 'Block override', 'action' => 'javascript:bad'),
            '<script>Block override</script>',
            null
        );
        $open_shortcode = call_user_func(
            $shortcode_callback,
            array('label' => 'Shortcode override', 'action' => 'javascript:bad'),
            '<script>Shortcode override</script>',
            'opennow_cta'
        );

        $this->assertSame($open_shortcode, $open_block);
        $this->assertStringContainsString('opennow-cta--open', $open_block);
        $this->assertStringNotContainsString('Block override', $open_block);
        $this->assertStringNotContainsString('<script>', $open_block);

        $instant = '2024-01-08 18:00:00';
        $closed_block = call_user_func($block_callback, array(), 'ignored content', null);
        $closed_shortcode = call_user_func($shortcode_callback, array(), 'ignored content', 'opennow_cta');

        $this->assertSame($closed_shortcode, $closed_block);
        $this->assertStringContainsString('opennow-cta--closed', $closed_block);
        $this->assertStringContainsString('Book online', $closed_block);
        $this->assertStringNotContainsString('Call Now', $closed_block);
    }

    public function testBlockAndShortcodeStayInOutputParityWhenBothRequestStatusHiding(): void
    {
        $instant = '2024-01-08 10:00:00';
        $renderer = new Renderer(
            new Repository(),
            new Evaluator(static function () use (&$instant): \DateTimeInterface {
                return new \DateTimeImmutable($instant, new \DateTimeZone('UTC'));
            })
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $this->config();

        $block = new Block($renderer);
        $shortcode = new Shortcode($renderer);
        $block->register();
        $shortcode->register();
        do_action('init');

        $block_callback = $GLOBALS['opennow_test_registered_blocks'][0]['args']['render_callback'];
        $shortcode_callback = $GLOBALS['opennow_test_shortcodes']['opennow_cta'];
        $block_attributes = array(
            'overrides' => array(
                'open' => array('hideStatus' => true),
                'closed' => array('hideStatus' => true),
            ),
        );

        $open_block = call_user_func($block_callback, $block_attributes, '', null);
        $open_shortcode = call_user_func(
            $shortcode_callback,
            array('hide_status' => '1'),
            '',
            'opennow_cta'
        );

        $this->assertSame($open_shortcode, $open_block);
        $this->assertStringNotContainsString('opennow-cta__status', $open_block);

        $instant = '2024-01-08 18:00:00';
        $closed_block = call_user_func($block_callback, $block_attributes, '', null);
        $closed_shortcode = call_user_func(
            $shortcode_callback,
            array('hide_status' => '1'),
            '',
            'opennow_cta'
        );

        $this->assertSame($closed_shortcode, $closed_block);
        $this->assertStringNotContainsString('opennow-cta__status', $closed_block);
    }

    public function testBlockPassesOnlyNestedOverridesAndIgnoresContentAndTopLevelFields(): void
    {
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $this->config();
        $block = new Block($this->rendererAt('2024-01-08 10:00:00'));
        $block->register();
        do_action('init');

        $callback = $GLOBALS['opennow_test_registered_blocks'][0]['args']['render_callback'];
        $output = call_user_func(
            $callback,
            array(
                'label' => 'Top-level ignored',
                'overrides' => array(
                    'open' => array(
                        'label' => 'Nested label',
                        'action' => ' /nested/ ',
                        'status' => '',
                    ),
                ),
            ),
            '<script>ignored content</script>',
            (object) array('overrides' => array('open' => array('label' => 'ignored block')))
        );

        $this->assertStringContainsString('Nested label', $output);
        $this->assertStringContainsString('href="/nested/"', $output);
        $this->assertStringNotContainsString('Call Now', $output);
        $this->assertStringNotContainsString('Top-level ignored', $output);
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringNotContainsString('opennow-cta__status', $output);
    }

    public function testBlockColorsOverrideGlobalLinkColorsWithoutAffectingDirectRendering(): void
    {
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $this->config();
        $block = new Block($this->rendererAt('2024-01-08 10:00:00'));
        $attributes = array(
            'textColor' => 'vivid-red',
            'style' => array(
                'color' => array(
                    'background' => '#123456',
                ),
            ),
        );

        $block_output = $block->render($attributes, '', (object) array());
        $direct_output = $block->render($attributes, '', null);

        $this->assertStringContainsString(
            'class="opennow-cta__link has-text-color has-vivid-red-color has-background"',
            $block_output
        );
        $this->assertStringContainsString('style="background-color:#123456"', $block_output);
        $this->assertStringNotContainsString('has-vivid-red-color', $direct_output);
        $this->assertStringNotContainsString('style="background-color:#123456"', $direct_output);
        $this->assertStringContainsString('--opennow-cta-background-color:', $direct_output);
        $this->assertStringContainsString('--opennow-cta-text-color:', $direct_output);
    }

    public function testBlockHandlesMalformedAttributesWithoutWarningsOrOverrideRescue(): void
    {
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $this->config();
        $block = new Block($this->rendererAt('2024-01-08 10:00:00'));

        $output = $block->render(
            array(
                'overrides' => array(
                    'open' => 'malformed',
                    'closed' => array('action' => 'javascript:bad'),
                ),
                'textColor' => array('malformed'),
                'style' => 'malformed',
            ),
            '<strong>ignored</strong>',
            (object) array()
        );

        $this->assertStringContainsString('Call Now', $output);
        $this->assertStringContainsString('href="tel:+123456789"', $output);
        $this->assertStringNotContainsString('javascript:', $output);

        $malformed_top_level = $block->render('not an array', 'ignored', null);
        $this->assertStringContainsString('Call Now', $malformed_top_level);
    }

    public function testMissingConfigurationRendersNothingAndDoesNotEnqueueStyles(): void
    {
        $block = new Block($this->rendererAt('2024-01-08 10:00:00'));

        $this->assertSame('', $block->render());
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);
    }

    public function testBlockCannotOverrideInvalidConfigurationAndDoesNotEnqueueStyles(): void
    {
        $config = $this->config();
        $config['cta']['open']['action'] = 'javascript:bad';
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $block = new Block($this->rendererAt('2024-01-08 10:00:00'));
        $block->register();
        do_action('init');

        $callback = $GLOBALS['opennow_test_registered_blocks'][0]['args']['render_callback'];
        $output = call_user_func(
            $callback,
            array(
                'label' => 'Injected label',
                'action' => 'https://example.com/override',
            ),
            '<strong>Injected content</strong>',
            null
        );

        $this->assertSame('', $output);
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);
    }

    private function rendererAt(string $instant): Renderer
    {
        return new Renderer(
            new Repository(),
            new Evaluator(static function () use ($instant): \DateTimeInterface {
                return new \DateTimeImmutable($instant, new \DateTimeZone('UTC'));
            })
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
                    'status' => 'We are closed.',
                ),
            ),
            'appearance' => array(
                'background_color' => '',
                'text_color' => '',
            ),
        );
    }
}
