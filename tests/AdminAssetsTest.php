<?php
namespace OpenNow\Tests;

use PHPUnit\Framework\TestCase;

final class AdminAssetsTest extends TestCase
{
    public function testSettingsStylesheetKeepsTheSchedulePreviewAndPromotionScopedAndResponsive(): void
    {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'settings.css';
        $css = file_get_contents($path);

        $this->assertIsString($css);
        $this->assertStringContainsString('#opennow-schedule', $css);
        $this->assertStringContainsString('border: 1px solid #8C8F94;', $css);
        $this->assertStringContainsString('.opennow-schedule-day--open', $css);
        $this->assertStringContainsString('.opennow-schedule-day--closed', $css);
        $this->assertStringContainsString('font-weight: 700;', $css);
        $this->assertStringContainsString('.opennow-schedule-summary', $css);
        $this->assertStringContainsString('display: flex;', $css);
        $this->assertStringContainsString('gap: 0.5rem 1rem;', $css);
        $this->assertStringContainsString('.opennow-schedule-closed-toggle', $css);
        $this->assertStringContainsString('input[type="checkbox"]', $css);
        $this->assertStringContainsString('flex: 0 0 auto;', $css);
        $this->assertStringContainsString('.opennow-schedule-row', $css);
        $this->assertStringContainsString('align-items: center;', $css);
        $this->assertStringContainsString('display: grid;', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(9rem, 12rem) minmax(10rem, 20rem);', $css);
        $this->assertStringContainsString('input:disabled', $css);
        $this->assertStringContainsString('opacity: 0.55;', $css);
        $this->assertStringContainsString('box-sizing: border-box;', $css);
        $this->assertStringContainsString('min-width: 0;', $css);
        $this->assertStringContainsString('max-width: 100%;', $css);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $css);

        $this->assertStringContainsString('#opennow-settings-layout', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) minmax(16rem, 22rem);', $css);
        $this->assertStringContainsString('.opennow-settings-sidebar', $css);
        $this->assertStringContainsString('#opennow-cta-preview', $css);
        $this->assertStringContainsString('.opennow-developer-promotion', $css);
        $this->assertStringContainsString('position: sticky;', $css);
        $this->assertStringContainsString('.opennow-cta-preview__state-button[aria-pressed="true"]', $css);
        $this->assertStringContainsString('background: transparent;', $css);
        $this->assertStringContainsString('border-bottom: 3px solid transparent;', $css);
        $this->assertStringContainsString('border-bottom-color: #2271B1;', $css);
        $this->assertStringContainsString('.opennow-cta-preview__state-button:focus', $css);
        $this->assertStringContainsString('outline: 2px solid #000000;', $css);
        $this->assertStringContainsString('.opennow-cta__link', $css);
        $this->assertStringContainsString('min-height: 44px;', $css);
        $this->assertStringContainsString('min-width: 44px;', $css);
        $this->assertStringContainsString('padding: 0.5em 1em;', $css);
        $this->assertStringContainsString('border-radius: 0.25em;', $css);
        $this->assertStringContainsString('.opennow-cta__status', $css);
        $this->assertStringContainsString('margin-top: 0.5em;', $css);

        $this->assertStringContainsString('.opennow-developer-promotion__links', $css);
        $this->assertStringContainsString('flex-direction: column;', $css);
        $this->assertStringContainsString('.opennow-developer-promotion__hire', $css);
        $this->assertStringContainsString('background: #2271B1;', $css);
        $this->assertStringContainsString('.opennow-developer-promotion__support', $css);
        $this->assertStringContainsString('.opennow-developer-promotion__review', $css);
        $this->assertStringContainsString('.opennow-developer-promotion__links a:focus', $css);
        $this->assertStringContainsString('outline-offset: 2px;', $css);
        $this->assertMatchesRegularExpression(
            '/#opennow-settings-layout \.opennow-developer-promotion__links a:focus\s*\{[^}]*outline: 2px solid #000000;[^}]*\}/s',
            $css
        );

        $this->assertStringContainsString('@media (max-width: 782px)', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr);', $css);
        $this->assertStringContainsString('#opennow-settings-layout > .opennow-settings-sidebar', $css);
        $this->assertStringContainsString('position: static;', $css);
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 782px\)\s*\{.*?#opennow-settings-layout > \.opennow-settings-sidebar\s*\{\s*position: static;\s*\}/s',
            $css
        );
        $this->assertStringNotContainsString('#opennow-schedule *', $css);
        $this->assertStringNotContainsString('@import', $css);
        $this->assertStringNotContainsString('url(', $css);

        $this->assertSame(
            0,
            preg_match(
                '/(?m)^(?!\s*(?:#opennow-schedule|#opennow-settings-layout|#opennow-cta-preview|@media|\}))[^\s].*\{/',
                $css
            )
        );
    }
}
