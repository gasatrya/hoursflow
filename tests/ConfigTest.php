<?php
namespace HoursFlow\Tests;

use HoursFlow\Config\Color;
use HoursFlow\Config\Schema;
use HoursFlow\Config\Validator;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        hoursflow_reset_wp_stubs();
    }

    public function testSchemaDefaultsContainSevenClosedDaysAndAccessibleColors(): void
    {
        $runtime = Schema::runtimeDefaults();

        $this->assertSame(
            array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'),
            array_keys($runtime['schedule'])
        );
        foreach ($runtime['schedule'] as $entry) {
            $this->assertSame(array('type' => 'closed'), $entry);
        }
        $this->assertSame(
            array(
                'background_color' => '#166534',
                'text_color' => '#FFFFFF',
            ),
            $runtime['appearance']
        );
        $this->assertGreaterThanOrEqual(
            4.5,
            Color::contrastRatio('#166534', '#FFFFFF')
        );
    }

    public function testValidatorReturnsTrimmedCanonicalConfiguration(): void
    {
        $config = $this->validConfig();
        $config['timezone'] = ' UTC ';
        $config['schedule']['monday']['opens'] = ' 09:00 ';
        $config['cta']['open']['label'] = ' Call Now ';
        $config['cta']['open']['status'] = ' Open now. ';
        $config['appearance']['background_color'] = ' #166534 ';

        $validated = Validator::validate($config);

        $this->assertIsArray($validated);
        $this->assertSame('UTC', $validated['timezone']);
        $this->assertSame('09:00', $validated['schedule']['monday']['opens']);
        $this->assertSame('Call Now', $validated['cta']['open']['label']);
        $this->assertSame('Open now.', $validated['cta']['open']['status']);
        $this->assertSame('#166534', $validated['appearance']['background_color']);
        $this->assertSame($config['cta']['closed'], $validated['cta']['closed']);
    }

    /**
     * @dataProvider invalidCompleteConfigurations
     */
    public function testValidatorRejectsMalformedAndMaliciousConfigurations($mutator): void
    {
        $config = $this->validConfig();
        $mutator($config);

        $validated = Validator::validate($config);

        $this->assertInstanceOf('WP_Error', $validated);
        $this->assertNotEmpty($validated->get_error_codes());
    }

    public function invalidCompleteConfigurations(): array
    {
        return array(
            'unknown top-level key' => array(static function (&$config): void {
                $config['unexpected'] = true;
            }),
            'missing top-level key' => array(static function (&$config): void {
                unset($config['timezone']);
            }),
            'raw offset timezone' => array(static function (&$config): void {
                $config['timezone'] = '+02:00';
            }),
            'timezone abbreviation' => array(static function (&$config): void {
                $config['timezone'] = 'EST';
            }),
            'missing weekday' => array(static function (&$config): void {
                unset($config['schedule']['sunday']);
            }),
            'unknown schedule key' => array(static function (&$config): void {
                $config['schedule']['monday']['unexpected'] = 'x';
            }),
            'equal period times' => array(static function (&$config): void {
                $config['schedule']['monday']['closes'] = '09:00';
            }),
            'bad period time' => array(static function (&$config): void {
                $config['schedule']['monday']['opens'] = '25:00';
            }),
            'missing CTA status' => array(static function (&$config): void {
                unset($config['cta']['open']['status']);
            }),
            'HTML label' => array(static function (&$config): void {
                $config['cta']['open']['label'] = '<strong>Call</strong>';
            }),
            'ASCII whitespace-only label' => array(static function (&$config): void {
                $config['cta']['open']['label'] = " \t\n ";
            }),
            'Unicode whitespace-only label' => array(static function (&$config): void {
                $config['cta']['open']['label'] = "\u{00A0}\u{2003}";
            }),
            'HTML status' => array(static function (&$config): void {
                $config['cta']['closed']['status'] = 'Closed <today>';
            }),
            'wrong status type' => array(static function (&$config): void {
                $config['cta']['open']['status'] = array('unsafe');
            }),
            'unknown CTA key' => array(static function (&$config): void {
                $config['cta']['open']['target'] = '_blank';
            }),
            'bad color' => array(static function (&$config): void {
                $config['appearance']['text_color'] = '#fff';
            }),
            'low contrast' => array(static function (&$config): void {
                $config['appearance']['background_color'] = '#FFFFFF';
                $config['appearance']['text_color'] = '#FFFFFF';
            }),
        );
    }

    public function testCtaOverridesCanonicalizePartialTrimmedValuesAndPreserveBlankStatus(): void
    {
        $overrides = Validator::canonicalCtaOverrides(
            array(
                'open' => array(
                    'label' => ' Call now ',
                    'action' => ' /booking/ ',
                    'status' => ' ',
                ),
                'closed' => array(
                    'status' => ' Closed. ',
                ),
            )
        );

        $this->assertSame(
            array(
                'open' => array(
                    'label' => 'Call now',
                    'action' => '/booking/',
                    'status' => '',
                ),
                'closed' => array(
                    'status' => 'Closed.',
                ),
            ),
            $overrides
        );
    }

    public function testCtaOverridesIgnoreMalformedContainersUnknownFieldsAndInvalidValuesIndependently(): void
    {
        $overrides = Validator::canonicalCtaOverrides(
            array(
                'open' => array(
                    'label' => '<strong>unsafe</strong>',
                    'action' => ' /valid/ ',
                    'status' => array('wrong type'),
                    'unknown' => 'ignored',
                ),
                'closed' => array(
                    'label' => ' Closed override ',
                    'action' => 'javascript:bad',
                    'status' => '',
                ),
                'unknown' => array('label' => 'ignored'),
                'malformed' => 'not an array',
            )
        );

        $this->assertSame(
            array(
                'open' => array(
                    'action' => '/valid/',
                ),
                'closed' => array(
                    'label' => 'Closed override',
                    'status' => '',
                ),
            ),
            $overrides
        );
        $this->assertSame(array(), Validator::canonicalCtaOverrides('not an array'));
        $this->assertSame(array(), Validator::canonicalCtaOverrides(array('open' => array('label' => '   '))));
    }

    public function testCtaOverridesPreserveOnlyStrictTrueHideStatusAndKeepOtherFieldsIndependent(): void
    {
        $this->assertSame(
            array(
                'open' => array(
                    'status' => 'Open status',
                    'hideStatus' => true,
                ),
                'closed' => array(
                    'status' => 'Closed status',
                ),
            ),
            Validator::canonicalCtaOverrides(
                array(
                    'open' => array(
                        'hideStatus' => true,
                        'status' => ' Open status ',
                    ),
                    'closed' => array(
                        'hideStatus' => false,
                        'status' => ' Closed status ',
                    ),
                )
            )
        );

        foreach (array(false, 'true', 1, array('true')) as $invalid_value) {
            $this->assertSame(
                array(
                    'open' => array(
                        'status' => 'Still valid',
                    ),
                ),
                Validator::canonicalCtaOverrides(
                    array(
                        'open' => array(
                            'hideStatus' => $invalid_value,
                            'status' => ' Still valid ',
                        ),
                    )
                )
            );
        }
    }

    /**
     * @dataProvider actionValues
     */
    public function testActionValidationPreservesValidCasingAndRejectsUnsafeForms($action, $valid): void
    {
        $canonical = Validator::canonicalAction($action);

        if ($valid) {
            $this->assertSame(trim($action), $canonical);
        } else {
            $this->assertNull($canonical);
        }
    }

    public function actionValues(): array
    {
        return array(
            'root relative' => array('/contact/?from=cta#form', true),
            'telephone' => array('tel:+1 (234) 567-8900', true),
            'uppercase telephone scheme' => array('TEL:+123456789', true),
            'https' => array('https://example.com/book', true),
            'uppercase https scheme' => array('HTTPS://example.com/book', true),
            'punycode host' => array('https://xn--r8jz45g.xn--zckzah/book', true),
            'trimmed root relative' => array(' /booking/ ', true),
            'bare relative' => array('booking/', false),
            'protocol relative' => array('//example.com/book', false),
            'javascript' => array('javascript:alert(1)', false),
            'http' => array('http://example.com', false),
            'credentials' => array('https://user:pass@example.com/book', false),
            'unicode host' => array('https://例え.テスト/book', false),
            'invalid port' => array('https://example.com:99999/book', false),
            'empty port' => array('https://example.com:/book', false),
            'whitespace root path' => array('/booking now/', false),
            'backslash' => array('/booking\\evil', false),
            'control character' => array("/booking\n", false),
            'invalid telephone characters' => array('tel:+123abc', false),
            'empty action' => array('', false),
        );
    }

    public function testBlankColorsUseDefaultsAndLowContrastUsesCompleteDefaults(): void
    {
        $this->assertSame(
            array(
                'background_color' => '#166534',
                'text_color' => '#FFFFFF',
            ),
            Color::effectivePair('', '')
        );
        $this->assertSame(
            array(
                'background_color' => '#166534',
                'text_color' => '#FFFFFF',
            ),
            Color::effectivePair('#FFFFFF', '#FFFFFF')
        );
        $this->assertSame(
            array(
                'background_color' => '#000000',
                'text_color' => '#FFFFFF',
            ),
            Color::effectivePair('#000000', '#FFFFFF')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validConfig(): array
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
            'timezone' => 'America/New_York',
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
