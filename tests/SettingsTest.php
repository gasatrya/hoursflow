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
        $this->assertCount(4, $GLOBALS['opennow_test_settings_sections'][Settings::PAGE_SLUG]);
        $this->assertCount(1, $GLOBALS['opennow_test_settings_fields'][Settings::PAGE_SLUG][Settings::SCHEDULE_SECTION]);
    }

    public function testSettingsPageUsesManageOptionsAndSettingsApiNonce(): void
    {
        $settings = new Settings();
        $settings->register();

        do_action('admin_menu');
        do_action('admin_init');

        $page = $GLOBALS['opennow_test_admin_pages'][Settings::PAGE_SLUG];
        $this->assertSame(Settings::PAGE_CAPABILITY, $page['capability']);
        $this->assertSame(
            Settings::PAGE_CAPABILITY,
            apply_filters('option_page_capability_opennow', 'different_capability')
        );

        ob_start();
        call_user_func($page['callback']);
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('<form action="options.php" method="post">', $output);
        $this->assertStringContainsString('name="option_page" value="opennow"', $output);
        $this->assertStringContainsString('name="_wpnonce" value="test-nonce"', $output);
        $this->assertStringContainsString('name="opennow_config[timezone]"', $output);
        $this->assertStringContainsString('name="opennow_config[cta][open][label]"', $output);
        $this->assertStringContainsString('name="opennow_config[cta][closed][action]"', $output);
        $this->assertStringContainsString('name="opennow_config[appearance][background_color]"', $output);
        $this->assertSame(7, substr_count($output, 'data-opennow-schedule-day='));
        $this->assertSame(7, substr_count($output, 'checked="checked"'));
        $this->assertSame(array(), $GLOBALS['opennow_test_option_calls']);
        $this->assertStringContainsString('value="America/New_York"', $output);
        $this->assertStringContainsString('value="UTC"', $output);
        $this->assertStringNotContainsString('Manual Offsets', $output);
        $this->assertStringNotContainsString('value="UTC+1"', $output);
    }

    public function testUnauthorizedUserCannotRenderSettingsPage(): void
    {
        $settings = new Settings();
        $GLOBALS['opennow_test_current_user_can'] = false;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('You do not have permission');

        $settings->renderPage();
    }

    public function testAdminScriptLoadsOnlyOnTheAuthorizedSettingsPage(): void
    {
        $settings = new Settings();
        $settings->register();
        do_action('admin_menu');

        do_action('admin_enqueue_scripts', 'settings_page_other');
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_scripts']);

        $GLOBALS['opennow_test_current_user_can'] = false;
        do_action('admin_enqueue_scripts', 'settings_page_opennow');
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_scripts']);

        $GLOBALS['opennow_test_current_user_can'] = true;
        do_action('admin_enqueue_scripts', 'settings_page_opennow');

        $this->assertArrayHasKey('opennow-admin-settings', $GLOBALS['opennow_test_enqueued_scripts']);
        $script = $GLOBALS['opennow_test_enqueued_scripts']['opennow-admin-settings'];
        $this->assertStringEndsWith('/assets/admin/settings.js', $script['src']);
        $this->assertFileExists(dirname(__DIR__) . '/assets/admin/settings.js');
        $this->assertSame(array(), $script['deps']);
        $this->assertTrue($script['args']);
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

        ob_start();
        (new Settings())->renderSettingsErrors();
        $error_output = (string) ob_get_clean();
        $this->assertStringContainsString('role="alert"', $error_output);
        $this->assertStringContainsString('setting-error-opennow_', $error_output);

        ob_start();
        (new Settings())->renderTimezoneField();
        $timezone_output = (string) ob_get_clean();
        $this->assertStringContainsString('aria-invalid="true"', $timezone_output);
        $this->assertStringContainsString('setting-error-opennow_timezone', $timezone_output);
    }

    public function testInvalidSubmissionWithoutAnExistingOptionReturnsFalse(): void
    {
        $result = (new Settings())->sanitize(array());

        $this->assertFalse($result);
        $this->assertNotEmpty($GLOBALS['opennow_test_settings_errors']);
    }

    /**
     * @dataProvider representativeInvalidUpdates
     */
    public function testRepresentativeInvalidUpdatesPreserveTheLastKnownGoodConfiguration(
        $mutator,
        $expected_code,
        $expected_context
    ): void {
        $existing = $this->validConfig();
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $existing;
        $submitted = $existing;
        $mutator($submitted);

        $result = (new Settings())->sanitize($submitted);

        $this->assertSame($existing, $result);
        $matching_errors = array_values(array_filter(
            $GLOBALS['opennow_test_settings_errors'],
            static function ($error) use ($expected_code): bool {
                return $expected_code === $error['code'];
            }
        ));
        $this->assertNotEmpty($matching_errors);
        $this->assertStringContainsString($expected_context, $matching_errors[0]['message']);
    }

    public function representativeInvalidUpdates(): array
    {
        return array(
            'invalid timezone' => array(
                static function (&$config): void {
                    $config['timezone'] = '+02:00';
                },
                'opennow_timezone',
                'Business timezone',
            ),
            'equal interval' => array(
                static function (&$config): void {
                    $config['schedule']['monday']['closes'] = '09:00';
                },
                'opennow_schedule_monday',
                'Monday',
            ),
            'unsafe action' => array(
                static function (&$config): void {
                    $config['cta']['open']['action'] = 'javascript:alert(1)';
                },
                'opennow_cta_open_action',
                'Open CTA',
            ),
        );
    }

    public function testACompleteClosedWeekNeedsNoOpeningOrClosingValues(): void
    {
        $config = $this->validConfig();
        foreach (Schema::days() as $day) {
            $config['schedule'][$day] = array('type' => 'closed');
        }

        $result = (new Settings())->sanitize($config);

        $this->assertIsArray($result);
        foreach (Schema::days() as $day) {
            $this->assertSame(array('type' => 'closed'), $result['schedule'][$day]);
        }
        $this->assertSame(array(), $GLOBALS['opennow_test_settings_errors']);
    }

    public function testScheduleMarkupDisablesPeriodValuesForClosedDays(): void
    {
        $config = $this->validConfig();
        $GLOBALS['wp_locale'] = new class() {
            public function get_weekday($weekday_number)
            {
                return 'Localized weekday ' . $weekday_number;
            }
        };
        $config['schedule']['monday'] = array(
            'type' => 'period',
            'opens' => '09:00',
            'closes' => '17:00',
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;
        $settings = new Settings();

        ob_start();
        $settings->renderScheduleField();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString(
            'id="opennow-schedule-monday-opens" name="opennow_config[schedule][monday][opens]" value="09:00"',
            $output
        );
        $this->assertStringContainsString(
            'id="opennow-schedule-tuesday-closed" name="opennow_config[schedule][tuesday][type]" value="closed"',
            $output
        );
        $this->assertStringContainsString('<legend>Localized weekday 1</legend>', $output);
        $this->assertLessThan(
            strpos($output, 'id="opennow-schedule-tuesday-closed"'),
            strpos($output, 'name="opennow_config[schedule][tuesday][type]" value="period"')
        );
        $this->assertMatchesRegularExpression(
            '/id="opennow-schedule-tuesday-opens"[^>]*disabled="disabled"/',
            $output
        );
        $this->assertStringContainsString('24-hour HH:MM local business time', $output);
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
