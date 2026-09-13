<?php
namespace OpenNow\Tests;

use PHPUnit\Framework\TestCase;

final class FrontendAssetsTest extends TestCase
{
    public function testFrontendStylesProvideStableHooksAndVisibleInteractionStates(): void
    {
        $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'cta.css';
        $css = file_get_contents($path);

        $this->assertIsString($css);
        $this->assertStringContainsString('.opennow-cta {', $css);
        $this->assertStringContainsString('.opennow-cta__link {', $css);
        $this->assertStringContainsString('.opennow-cta__status {', $css);
        $this->assertStringContainsString('var(--opennow-cta-background-color)', $css);
        $this->assertStringContainsString('var(--opennow-cta-text-color)', $css);
        $this->assertStringContainsString('.opennow-cta__link:hover', $css);
        $this->assertStringContainsString('text-decoration: underline;', $css);
        $this->assertStringContainsString('.opennow-cta__link:focus-visible', $css);
        $this->assertStringContainsString('outline: 2px solid #FFFFFF;', $css);
        $this->assertStringContainsString('box-shadow: 0 0 0 4px #000000;', $css);
        $this->assertStringNotContainsString('@import', $css);
        $this->assertStringNotContainsString('url(', $css);
    }
}
