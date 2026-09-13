<?php
namespace OpenNow\Tests;

use OpenNow\Admin\Settings;
use OpenNow\Config\Schema;
use PHPUnit\Framework\TestCase;

final class SettingsTest extends TestCase
{
    protected function setUp(): void
    {
        opennow_reset_wp_stubs();
    }

    public function testSettingsRegistersOnlyTheAtomicOptionOnAdminInit(): void
    {
        $settings = new Settings();
        $settings->register();

        $this->assertArrayHasKey('admin_init', $GLOBALS['opennow_test_hooks']);
        $this->assertCount(1, $GLOBALS['opennow_test_hooks']['admin_init']);
        $this->assertSame(array(), $GLOBALS['opennow_test_registered_settings']);

        do_action('admin_init');

        $this->assertArrayHasKey(
            Schema::OPTION_NAME,
            $GLOBALS['opennow_test_registered_settings']
        );
        $registration = $GLOBALS['opennow_test_registered_settings'][Schema::OPTION_NAME];
        $this->assertSame('opennow', $registration['group']);
        $this->assertSame('array', $registration['args']['type']);
        $this->assertFalse($registration['args']['show_in_rest']);
        $this->assertIsArray($registration['args']['sanitize_callback']);
        $this->assertSame('sanitize', $registration['args']['sanitize_callback'][1]);
    }

    public function testInvalidSubmissionReturnsTheExactExistingOptionAndAddsErrors(): void
    {
        $existing = array('retained' => 'unchanged');
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $existing;

        $result = (new Settings())->sanitize(array('timezone' => '+02:00'));

        $this->assertSame($existing, $result);
        $this->assertCount(0, array_filter(
            $GLOBALS['opennow_test_option_calls'],
            static function ($call): bool {
                return 'update_option' === $call['function'];
            }
        ));
        $this->assertNotEmpty($GLOBALS['opennow_test_settings_errors']);
        $this->assertSame(Schema::OPTION_NAME, $GLOBALS['opennow_test_settings_errors'][0]['setting']);
        $this->assertSame('error', $GLOBALS['opennow_test_settings_errors'][0]['type']);
        $this->assertStringStartsWith('opennow_', $GLOBALS['opennow_test_settings_errors'][0]['code']);
    }

    public function testInvalidSubmissionWithoutAnExistingOptionReturnsFalse(): void
    {
        $result = (new Settings())->sanitize(array());

        $this->assertFalse($result);
        $this->assertNotEmpty($GLOBALS['opennow_test_settings_errors']);
    }

    public function testValidSubmissionReturnsCanonicalValue(): void
    {
        $config = $this->validConfig();
        $config['timezone'] = ' UTC ';
        $config['cta']['closed']['label'] = ' Book online ';

        $result = (new Settings())->sanitize($config);

        $this->assertIsArray($result);
        $this->assertSame('UTC', $result['timezone']);
        $this->assertSame('Book online', $result['cta']['closed']['label']);
        $this->assertSame(array(), $GLOBALS['opennow_test_settings_errors']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validConfig(): array
    {
        $schedule = array();
        foreach (array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') as $day) {
            $schedule[$day] = array('type' => 'closed');
        }

        return array(
            'timezone' => 'America/New_York',
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
    }
}
