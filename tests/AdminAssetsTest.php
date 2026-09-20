<?php
namespace OpenNow\Tests;

use PHPUnit\Framework\TestCase;

final class AdminAssetsTest extends TestCase
{
    public function testSettingsStylesheetKeepsTheScheduleScopedAndResponsive(): void
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
        $this->assertStringContainsString('@media (max-width: 782px)', $css);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr);', $css);
        $this->assertStringNotContainsString('#opennow-schedule *', $css);
        $this->assertStringNotContainsString('@import', $css);
        $this->assertStringNotContainsString('url(', $css);

        $this->assertSame(
            0,
            preg_match('/(?m)^(?!\s*#opennow-schedule|\s*@media|\s*\})[^\s].*\{/', $css)
        );
    }
}
