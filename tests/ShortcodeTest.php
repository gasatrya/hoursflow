<?php
namespace OpenNow\Tests;

use OpenNow\Config\Schema;
use OpenNow\Frontend\Renderer;
use OpenNow\Frontend\Shortcode;
use OpenNow\Config\Repository;
use OpenNow\Schedule\Evaluator;
use PHPUnit\Framework\TestCase;

final class ShortcodeTest extends TestCase
{
    protected function setUp(): void
    {
        opennow_reset_wp_stubs();
    }

    public function testShortcodeRegistersAPluginNamespacedObjectCallback(): void
    {
        $shortcode = new Shortcode($this->renderer());
        $shortcode->register();

        $this->assertArrayHasKey('opennow_cta', $GLOBALS['opennow_test_shortcodes']);
        $callback = $GLOBALS['opennow_test_shortcodes']['opennow_cta'];
        $this->assertIsArray($callback);
        $this->assertSame($shortcode, $callback[0]);
        $this->assertSame('render', $callback[1]);
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);
    }

    public function testShortcodeIgnoresAttributesAndContentAndDelegatesToRenderer(): void
    {
        $shortcode = new Shortcode($this->renderer());
        $shortcode->register();

        $output = call_user_func(
            $GLOBALS['opennow_test_shortcodes']['opennow_cta'],
            array('label' => 'Override', 'action' => 'javascript:bad'),
            '<script>override</script>',
            'opennow_cta'
        );

        $this->assertStringContainsString('Call Now', $output);
        $this->assertStringContainsString('opennow-cta--open', $output);
        $this->assertStringNotContainsString('Override', $output);
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertArrayHasKey('opennow-cta', $GLOBALS['opennow_test_enqueued_styles']);
    }

    private function renderer(): Renderer
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
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = array(
            'timezone' => 'UTC',
            'schedule' => $schedule,
            'cta' => array(
                'open' => array(
                    'label' => 'Call Now',
                    'action' => 'tel:+123456789',
                    'status' => '',
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

        return new Renderer(
            new Repository(),
            new Evaluator(static function (): \DateTimeInterface {
                return new \DateTimeImmutable('2024-01-08 10:00:00', new \DateTimeZone('UTC'));
            })
        );
    }
}
