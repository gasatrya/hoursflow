<?php
namespace HoursFlow\Tests;

use HoursFlow\Config\Schema;
use HoursFlow\Frontend\Renderer;
use HoursFlow\Frontend\Shortcode;
use HoursFlow\Config\Repository;
use HoursFlow\Schedule\Evaluator;
use PHPUnit\Framework\TestCase;

final class ShortcodeTest extends TestCase
{
    protected function setUp(): void
    {
        hoursflow_reset_wp_stubs();
    }

    public function testShortcodeRegistersAPluginNamespacedObjectCallback(): void
    {
        $shortcode = new Shortcode($this->renderer());
        $shortcode->register();

        $this->assertArrayHasKey('hoursflow_cta', $GLOBALS['hoursflow_test_shortcodes']);
        $callback = $GLOBALS['hoursflow_test_shortcodes']['hoursflow_cta'];
        $this->assertIsArray($callback);
        $this->assertSame($shortcode, $callback[0]);
        $this->assertSame('render', $callback[1]);
        $this->assertSame(array(), $GLOBALS['hoursflow_test_enqueued_styles']);
    }

    public function testShortcodeIgnoresAttributesAndContentAndDelegatesToRenderer(): void
    {
        $shortcode = new Shortcode($this->renderer());
        $shortcode->register();

        $output = call_user_func(
            $GLOBALS['hoursflow_test_shortcodes']['hoursflow_cta'],
            array(
                'label' => 'Override',
                'action' => 'javascript:bad',
                'overrides' => array(
                    'open' => array(
                        'label' => 'Shortcode override',
                        'action' => '/shortcode-override/',
                    ),
                ),
            ),
            '<script>override</script>',
            'hoursflow_cta'
        );

        $this->assertStringContainsString('Call Now', $output);
        $this->assertStringContainsString('hoursflow-cta--open', $output);
        $this->assertStringNotContainsString('Override', $output);
        $this->assertStringNotContainsString('shortcode-override', $output);
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertArrayHasKey('hoursflow-cta', $GLOBALS['hoursflow_test_enqueued_styles']);
    }

    public function testExactHideStatusAttributeHidesTheSelectedStateStatus(): void
    {
        $open_shortcode = new Shortcode($this->renderer());
        $open_output = $open_shortcode->render(array('hide_status' => '1'));

        $closed_shortcode = new Shortcode($this->renderer('2024-01-08 18:00:00'));
        $closed_output = $closed_shortcode->render(array('hide_status' => '1'));

        $this->assertStringContainsString('Call Now', $open_output);
        $this->assertStringNotContainsString('hoursflow-cta__status', $open_output);
        $this->assertStringContainsString('Book online', $closed_output);
        $this->assertStringNotContainsString('hoursflow-cta__status', $closed_output);
    }

    public function testNonExactHideStatusValuesAndMalformedContainersAreNoOp(): void
    {
        $invalid_attributes = array(
            array('hide_status' => 1),
            array('hide_status' => true),
            array('hide_status' => false),
            array('hide_status' => 'true'),
            array('hide_status' => ' 1'),
            array('hide_status' => array('1')),
            array('hideStatus' => '1'),
            'not an array',
            (object) array('hide_status' => '1'),
        );

        foreach ($invalid_attributes as $attributes) {
            $output = (new Shortcode($this->renderer()))->render($attributes);

            $this->assertStringContainsString('We are open.', $output);
            $this->assertStringContainsString('hoursflow-cta__status', $output);
        }
    }

    private function renderer(string $instant = '2024-01-08 10:00:00'): Renderer
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
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = array(
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

        return new Renderer(
            new Repository(),
            new Evaluator(static function () use ($instant): \DateTimeInterface {
                return new \DateTimeImmutable($instant, new \DateTimeZone('UTC'));
            })
        );
    }
}
