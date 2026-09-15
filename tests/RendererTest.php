<?php
namespace OpenNow\Tests;

use OpenNow\Config\Repository;
use OpenNow\Config\Schema;
use OpenNow\Frontend\Renderer;
use OpenNow\Schedule\Evaluator;
use PHPUnit\Framework\TestCase;

final class RendererTest extends TestCase
{
    protected function setUp(): void
    {
        opennow_reset_wp_stubs();
    }

    public function testAbsentConfigurationRendersNothingWithoutEnqueueingStyles(): void
    {
        $this->assertSame('', $this->rendererAt('2024-01-08 10:00:00')->render());
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);
    }

    public function testOpenHoursRenderOnlyTheOpenCta(): void
    {
        $config = $this->config();
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringContainsString('class="opennow-cta opennow-cta--open"', $output);
        $this->assertStringContainsString('class="opennow-cta__link"', $output);
        $this->assertStringContainsString('href="tel:+123456789"', $output);
        $this->assertStringContainsString('Call Now', $output);
        $this->assertStringContainsString('class="opennow-cta__status">We are open.</span>', $output);
        $this->assertStringNotContainsString('Book online', $output);
        $this->assertArrayHasKey('opennow-cta', $GLOBALS['opennow_test_enqueued_styles']);
    }

    public function testValidOpenOverridesReplaceContentAndPreserveGlobalAppearance(): void
    {
        $config = $this->config();
        $config['appearance'] = array(
            'background_color' => '#000000',
            'text_color' => '#FFFFFF',
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

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
            'style="--opennow-cta-background-color: #000000; --opennow-cta-text-color: #FFFFFF;"',
            $output
        );
    }

    public function testValidClosedOverridesReplaceClosedContent(): void
    {
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $this->config();

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
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $this->config();

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
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;
        $GLOBALS['opennow_test_enqueued_styles'] = array();

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
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);
    }

    public function testBlankOverrideStatusSuppressesGlobalStatusAndStateOverridesAreIsolated(): void
    {
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $this->config();
        $overrides = array(
            'open' => array(
                'label' => 'Open only',
                'status' => ' ',
            ),
        );

        $open_output = $this->rendererAt('2024-01-08 10:00:00')->render($overrides);
        $closed_output = $this->rendererAt('2024-01-08 18:00:00')->render($overrides);

        $this->assertStringContainsString('Open only', $open_output);
        $this->assertStringNotContainsString('opennow-cta__status', $open_output);
        $this->assertStringContainsString('Book online', $closed_output);
        $this->assertStringNotContainsString('Open only', $closed_output);
    }

    public function testAfterHoursRenderOnlyTheClosedCta(): void
    {
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $this->config();

        $output = $this->rendererAt('2024-01-08 18:00:00')->render();

        $this->assertStringContainsString('class="opennow-cta opennow-cta--closed"', $output);
        $this->assertStringContainsString('href="/booking/"', $output);
        $this->assertStringContainsString('Book online', $output);
        $this->assertStringNotContainsString('Call Now', $output);
    }

    public function testConfiguredClosedDayRendersTheClosedCta(): void
    {
        $config = $this->config();
        $config['schedule']['tuesday'] = array('type' => 'closed');
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-09 12:00:00')->render();

        $this->assertStringContainsString('opennow-cta--closed', $output);
        $this->assertStringContainsString('Book online', $output);
        $this->assertStringNotContainsString('Call Now', $output);
    }

    public function testMissingOrInvalidTimezoneSelectsClosedState(): void
    {
        $config = $this->config();
        $config['timezone'] = 'Not/A-Timezone';
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringContainsString('opennow-cta--closed', $output);
        $this->assertStringContainsString('Book online', $output);
        $this->assertStringNotContainsString('Call Now', $output);
    }

    public function testInvalidTimezoneWithInvalidClosedCtaDoesNotFallBackToOpen(): void
    {
        $config = $this->config();
        $config['timezone'] = 'Not/A-Timezone';
        $config['cta']['closed']['action'] = 'javascript:alert(1)';
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertSame('', $output);
        $this->assertStringNotContainsString('Call Now', $output);
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);
    }

    public function testBlankStatusIsOmitted(): void
    {
        $config = $this->config();
        $config['cta']['open']['status'] = '';
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringNotContainsString('opennow-cta__status', $output);
        $this->assertStringContainsString('Call Now', $output);
    }

    public function testMissingOrInvalidSelectedCtaDoesNotFallBackOrEnqueue(): void
    {
        $config = $this->config();
        unset($config['cta']['open']);
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $this->assertSame('', $this->rendererAt('2024-01-08 10:00:00')->render());
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);

        $config = $this->config();
        $config['cta']['open']['action'] = 'javascript:alert(1)';
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $this->assertSame('', $this->rendererAt('2024-01-08 10:00:00')->render());
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);
    }

    public function testHostileStoredCtaContentIsRejectedWithoutExecutableOutput(): void
    {
        $config = $this->config();
        $config['cta']['open']['label'] = '<script>alert(1)</script>';
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertSame('', $output);
        $this->assertStringNotContainsString('<script', $output);
        $this->assertSame(array(), $GLOBALS['opennow_test_enqueued_styles']);
    }

    public function testValidSpecialCharactersAndQueryValuesAreEscaped(): void
    {
        $config = $this->config();
        $config['cta']['open'] = array(
            'label' => 'Call & "now"',
            'action' => 'https://example.com/book?from=cta&next=1#form',
            'status' => 'Open & ready',
        );
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

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
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringContainsString(
            'style="--opennow-cta-background-color: #000000; --opennow-cta-text-color: #FFFFFF;"',
            $output
        );
        $this->assertStringNotContainsString(' role=', $output);
        $this->assertStringNotContainsString('aria-live', $output);
        $this->assertStringNotContainsString(' target=', $output);
        $this->assertSame(1, count($GLOBALS['opennow_test_enqueued_styles']));
        $style = $GLOBALS['opennow_test_enqueued_styles']['opennow-cta'];
        $this->assertStringEndsWith('/assets/public/cta.css', $style['src']);
        $expected_version = defined('OPENNOW_VERSION') ? OPENNOW_VERSION : null;
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
        $GLOBALS['opennow_test_options'][Schema::OPTION_NAME] = $config;

        $output = $this->rendererAt('2024-01-08 10:00:00')->render();

        $this->assertStringContainsString('Call Now', $output);
        $this->assertStringContainsString('--opennow-cta-background-color: #166534;', $output);
        $this->assertStringContainsString('--opennow-cta-text-color: #FFFFFF;', $output);
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
