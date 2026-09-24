<?php
namespace HoursFlow\Tests;

use HoursFlow\Config\Repository;
use HoursFlow\Config\Schema;
use HoursFlow\Frontend\Renderer;
use HoursFlow\Schedule\Evaluator;
use PHPUnit\Framework\TestCase;

final class RendererTest extends TestCase
{
    protected function setUp(): void
    {
        hoursflow_reset_wp_stubs();
    }

    public function testAbsentConfigurationRendersNothingWithoutEnqueueingStyles(): void
    {
        $this->assertSame('', $this->rendererAt('2024-01-08 10:00:00')->render());
        $this->assertSame(array(), $GLOBALS['hoursflow_test_enqueued_styles']);
    }

    public function testOpenHoursRenderOnlyTheOpenCta(): void
    {
        $config = $this->config();
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringContainsString('class="hoursflow-cta hoursflow-cta--open"', $output);
        $this->assertStringContainsString('class="hoursflow-cta__link"', $output);
        $this->assertStringContainsString('href="tel:+123456789"', $output);
        $this->assertStringContainsString('Call Now', $output);
        $this->assertStringContainsString('class="hoursflow-cta__status">We are open.</span>', $output);
        $this->assertStringNotContainsString('Book online', $output);
        $this->assertArrayHasKey('hoursflow-cta', $GLOBALS['hoursflow_test_enqueued_styles']);
    }

    public function testValidOpenOverridesReplaceContentAndPreserveGlobalAppearance(): void
    {
        $config = $this->config();
        $config['appearance'] = array(
            'background_color' => '#000000',
            'text_color' => '#FFFFFF',
        );
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render(
            array(
                'open' => array(
                    'label' => ' Override now ',
                    'action' => ' /override/ ',
                    'status' => ' Override status ',
                ),
            )
        );

        $this->assertStringContainsString('href="/override/"', $output);
        $this->assertStringContainsString('Override now', $output);
        $this->assertStringContainsString('Override status', $output);
        $this->assertStringNotContainsString('Call Now', $output);
        $this->assertStringNotContainsString('We are open.', $output);
        $this->assertStringContainsString(
            'style="--hoursflow-cta-background-color: #000000; --hoursflow-cta-text-color: #FFFFFF;"',
            $output
        );
    }

    public function testValidClosedOverridesReplaceClosedContent(): void
    {
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $this->config();

        $output = $this->rendererAt('2024-01-08 18:00:00')->render(
            array(
                'closed' => array(
                    'label' => ' Schedule a visit ',
                    'action' => 'https://example.com/visit ',
                    'status' => ' Closed now. ',
                ),
            )
        );

        $this->assertStringContainsString('href="https://example.com/visit"', $output);
        $this->assertStringContainsString('Schedule a visit', $output);
        $this->assertStringContainsString('Closed now.', $output);
        $this->assertStringNotContainsString('Book online', $output);
    }

    public function testInvalidOverrideFieldsFallBackIndependentlyAndDoNotRescueGlobals(): void
    {
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $this->config();

        $output = $this->rendererAt('2024-01-08 10:00:00')->render(
            array(
                'open' => array(
                    'label' => '<strong>unsafe</strong>',
                    'action' => ' /valid/ ',
                    'status' => array('wrong type'),
                ),
            )
        );

        $this->assertStringContainsString('href="/valid/"', $output);
        $this->assertStringContainsString('Call Now', $output);
        $this->assertStringContainsString('We are open.', $output);
        $this->assertStringNotContainsString('<strong>', $output);

        $config = $this->config();
        $config['cta']['open']['action'] = 'javascript:bad';
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;
        $GLOBALS['hoursflow_test_enqueued_styles'] = array();

        $this->assertSame(
            '',
            $this->rendererAt('2024-01-08 10:00:00')->render(
                array(
                    'open' => array(
                        'label' => 'Rescue attempt',
                        'action' => '/rescue/',
                        'status' => 'Rescued',
                    ),
                )
            )
        );
        $this->assertSame(array(), $GLOBALS['hoursflow_test_enqueued_styles']);
    }

    public function testBlankOverrideStatusSuppressesGlobalStatusAndStateOverridesAreIsolated(): void
    {
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $this->config();
        $overrides = array(
            'open' => array(
                'label' => 'Open only',
                'status' => ' ',
            ),
        );

        $open_output = $this->rendererAt('2024-01-08 10:00:00')->render($overrides);
        $closed_output = $this->rendererAt('2024-01-08 18:00:00')->render($overrides);

        $this->assertStringContainsString('Open only', $open_output);
        $this->assertStringNotContainsString('hoursflow-cta__status', $open_output);
        $this->assertStringContainsString('Book online', $closed_output);
        $this->assertStringNotContainsString('Open only', $closed_output);
    }

    public function testHideStatusAppliesOnlyToTheSelectedState(): void
    {
        $config = $this->config();
        $config['cta']['closed']['status'] = 'We are closed.';
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;
        $overrides = array(
            'open' => array(
                'hideStatus' => true,
            ),
            'closed' => array(
                'status' => 'Closed override',
            ),
        );

        $open_output = $this->rendererAt('2024-01-08 10:00:00')->render($overrides);
        $closed_output = $this->rendererAt('2024-01-08 18:00:00')->render($overrides);

        $this->assertStringContainsString('Call Now', $open_output);
        $this->assertStringNotContainsString('hoursflow-cta__status', $open_output);
        $this->assertStringContainsString('Book online', $closed_output);
        $this->assertStringContainsString('Closed override', $closed_output);
        $this->assertStringContainsString('hoursflow-cta__status', $closed_output);
    }

    public function testHideStatusWinsWhenItCoexistsWithAStatusOverride(): void
    {
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $this->config();

        $output = $this->rendererAt('2024-01-08 10:00:00')->render(
            array(
                'open' => array(
                    'status' => 'Override status',
                    'hideStatus' => true,
                ),
            )
        );

        $this->assertStringContainsString('Call Now', $output);
        $this->assertStringNotContainsString('We are open.', $output);
        $this->assertStringNotContainsString('Override status', $output);
        $this->assertStringNotContainsString('hoursflow-cta__status', $output);
    }

    public function testInvalidHideStatusValuesFallBackWithoutAffectingValidFields(): void
    {
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $this->config();

        foreach (array(false, 'true', 1, array('invalid')) as $invalid_value) {
            $output = $this->rendererAt('2024-01-08 10:00:00')->render(
                array(
                    'open' => array(
                        'hideStatus' => $invalid_value,
                        'status' => 'Override status',
                    ),
                )
            );

            $this->assertStringContainsString('Override status', $output);
            $this->assertStringContainsString('hoursflow-cta__status', $output);
        }
    }

    public function testAfterHoursRenderOnlyTheClosedCta(): void
    {
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $this->config();

        $output = $this->rendererAt('2024-01-08 18:00:00')->render();

        $this->assertStringContainsString('class="hoursflow-cta hoursflow-cta--closed"', $output);
        $this->assertStringContainsString('href="/booking/"', $output);
        $this->assertStringContainsString('Book online', $output);
        $this->assertStringNotContainsString('Call Now', $output);
    }

    public function testConfiguredClosedDayRendersTheClosedCta(): void
    {
        $config = $this->config();
        $config['schedule']['tuesday'] = array('type' => 'closed');
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-09 12:00:00')->render();

        $this->assertStringContainsString('hoursflow-cta--closed', $output);
        $this->assertStringContainsString('Book online', $output);
        $this->assertStringNotContainsString('Call Now', $output);
    }

    public function testMissingOrInvalidTimezoneSelectsClosedState(): void
    {
        $config = $this->config();
        $config['timezone'] = 'Not/A-Timezone';
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringContainsString('hoursflow-cta--closed', $output);
        $this->assertStringContainsString('Book online', $output);
        $this->assertStringNotContainsString('Call Now', $output);
    }

    public function testInvalidTimezoneWithInvalidClosedCtaDoesNotFallBackToOpen(): void
    {
        $config = $this->config();
        $config['timezone'] = 'Not/A-Timezone';
        $config['cta']['closed']['action'] = 'javascript:alert(1)';
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertSame('', $output);
        $this->assertStringNotContainsString('Call Now', $output);
        $this->assertSame(array(), $GLOBALS['hoursflow_test_enqueued_styles']);
    }

    public function testBlankStatusIsOmitted(): void
    {
        $config = $this->config();
        $config['cta']['open']['status'] = '';
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringNotContainsString('hoursflow-cta__status', $output);
        $this->assertStringContainsString('Call Now', $output);
    }

    public function testMissingOrInvalidSelectedCtaDoesNotFallBackOrEnqueue(): void
    {
        $config = $this->config();
        unset($config['cta']['open']);
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $this->assertSame('', $this->rendererAt('2024-01-08 10:00:00')->render());
        $this->assertSame(array(), $GLOBALS['hoursflow_test_enqueued_styles']);

        $config = $this->config();
        $config['cta']['open']['action'] = 'javascript:alert(1)';
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $this->assertSame('', $this->rendererAt('2024-01-08 10:00:00')->render());
        $this->assertSame(array(), $GLOBALS['hoursflow_test_enqueued_styles']);
    }

    public function testHostileStoredCtaContentIsRejectedWithoutExecutableOutput(): void
    {
        $config = $this->config();
        $config['cta']['open']['label'] = '<script>alert(1)</script>';
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertSame('', $output);
        $this->assertStringNotContainsString('<script', $output);
        $this->assertSame(array(), $GLOBALS['hoursflow_test_enqueued_styles']);
    }

    public function testValidSpecialCharactersAndQueryValuesAreEscaped(): void
    {
        $config = $this->config();
        $config['cta']['open'] = array(
            'label' => 'Call & "now"',
            'action' => 'https://example.com/book?from=cta&next=1#form',
            'status' => 'Open & ready',
        );
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringContainsString('Call &amp; &quot;now&quot;', $output);
        $this->assertStringContainsString('href="https://example.com/book?from=cta&amp;next=1#form"', $output);
        $this->assertStringContainsString('Open &amp; ready', $output);
        $this->assertStringNotContainsString('href="javascript:', $output);
    }

    public function testEffectiveColorsAndAccessibilityMarkupAreStable(): void
    {
        $config = $this->config();
        $config['appearance'] = array(
            'background_color' => '#000000',
            'text_color' => '#FFFFFF',
        );
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringContainsString(
            'style="--hoursflow-cta-background-color: #000000; --hoursflow-cta-text-color: #FFFFFF;"',
            $output
        );
        $this->assertStringNotContainsString(' role=', $output);
        $this->assertStringNotContainsString('aria-live', $output);
        $this->assertStringNotContainsString(' target=', $output);
        $this->assertSame(1, count($GLOBALS['hoursflow_test_enqueued_styles']));
        $style = $GLOBALS['hoursflow_test_enqueued_styles']['hoursflow-cta'];
        $this->assertStringEndsWith('/assets/public/cta.css', $style['src']);
        $expected_version = defined('HOURSFLOW_VERSION') ? HOURSFLOW_VERSION : null;
        $this->assertSame($expected_version, $style['ver']);
        $this->assertSame(array(), $style['deps']);
    }

    public function testInvalidColorsUseTheRepositoryDefaultsWithoutSuppressingCta(): void
    {
        $config = $this->config();
        $config['appearance'] = array(
            'background_color' => 'javascript:bad',
            'text_color' => '#FFFFFF',
        );
        $GLOBALS['hoursflow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringContainsString('Call Now', $output);
        $this->assertStringContainsString('--hoursflow-cta-background-color: #166534;', $output);
        $this->assertStringContainsString('--hoursflow-cta-text-color: #FFFFFF;', $output);
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
