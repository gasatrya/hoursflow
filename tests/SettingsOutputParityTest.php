<?php
namespace HoursFlow\Tests;

use HoursFlow\Admin\Settings;
use HoursFlow\Config\Repository;
use HoursFlow\Config\Schema;
use HoursFlow\Frontend\Block;
use HoursFlow\Frontend\Renderer;
use HoursFlow\Frontend\Shortcode;
use HoursFlow\Schedule\Evaluator;
use PHPUnit\Framework\TestCase;

final class SettingsOutputParityTest extends TestCase
{
    protected function setUp(): void
    {
        hoursflow_reset_wp_stubs();
    }

    /**
     * @dataProvider deterministicInstants
     */
    public function testSanitizedSettingsSavedThroughWordPressDriveEquivalentShortcodeAndBlockOutput(
        string $instant,
        string $expected_state
    ): void {
        $settings = new Settings();
        $saved = $settings->sanitize($this->config());

        $this->assertIsArray($saved);
        $this->assertSame(array(), $GLOBALS['hoursflow_test_settings_errors']);
        update_option(Schema::OPTION_NAME, $saved);

        $renderer = new Renderer(
            new Repository(),
            new Evaluator(static function () use ($instant): \DateTimeInterface {
                return new \DateTimeImmutable($instant, new \DateTimeZone('UTC'));
            })
        );
        $block = new Block($renderer);
        $shortcode = new Shortcode($renderer);
        $block->register();
        $shortcode->register();
        do_action('init');

        $block_output = call_user_func(
            $GLOBALS['hoursflow_test_registered_blocks'][0]['args']['render_callback'],
            array(),
            '',
            null
        );
        $shortcode_output = call_user_func(
            $GLOBALS['hoursflow_test_shortcodes']['hoursflow_cta'],
            array(),
            null,
            'hoursflow_cta'
        );

        $this->assertSame($saved, $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME]);
        $this->assertSame($shortcode_output, $block_output);
        $this->assertStringContainsString('hoursflow-cta--' . $expected_state, $block_output);
        $this->assertStringContainsString(
            'open' === $expected_state ? 'Call Now' : 'Book online',
            $block_output
        );
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
