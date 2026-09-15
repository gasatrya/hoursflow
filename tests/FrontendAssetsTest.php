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
        $this->assertSame(1, preg_match('/^[ \t]*\.opennow-cta[ \t]*\{([^}]*)\}/m', $css, $wrapper_rule));
        $this->assertSame(1, preg_match('/^[ \t]*\.opennow-cta__link[ \t]*\{([^}]*)\}/m', $css, $link_rule));
        $this->assertSame(1, preg_match('/^[ \t]*\.opennow-cta__status[ \t]*\{([^}]*)\}/m', $css, $status_rule));

        $this->assertStringContainsString('var(--opennow-cta-background-color)', $link_rule[1]);
        $this->assertStringContainsString('var(--opennow-cta-text-color)', $link_rule[1]);
        $this->assertStringContainsString('display: inline-flex;', $link_rule[1]);
        $this->assertStringContainsString('align-items: center;', $link_rule[1]);
        $this->assertStringContainsString('justify-content: center;', $link_rule[1]);
        $this->assertStringContainsString('box-sizing: border-box;', $link_rule[1]);
        $this->assertStringContainsString('min-width: 44px;', $link_rule[1]);
        $this->assertStringContainsString('min-height: 44px;', $link_rule[1]);
        $this->assertStringContainsString('max-width: 100%;', $link_rule[1]);
        $this->assertStringContainsString('padding: 0.5em 1em;', $link_rule[1]);
        $this->assertStringContainsString('border-radius: 0.25em;', $link_rule[1]);
        $this->assertStringContainsString('text-align: center;', $link_rule[1]);
        $this->assertStringContainsString('text-decoration: none;', $link_rule[1]);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $link_rule[1]);

        $this->assertStringContainsString('--opennow-cta-background-color: #166534;', $wrapper_rule[1]);
        $this->assertStringContainsString('--opennow-cta-text-color: #FFFFFF;', $wrapper_rule[1]);
        $this->assertStringContainsString('display: block;', $status_rule[1]);
        $this->assertStringContainsString('margin-top: 0.5em;', $status_rule[1]);
        $this->assertStringContainsString('overflow-wrap: anywhere;', $status_rule[1]);
        $this->assertStringContainsString('.opennow-cta__link:hover', $css);
        $this->assertStringContainsString('text-decoration: underline;', $css);
        $this->assertStringContainsString('.opennow-cta__link:focus-visible', $css);
        $this->assertStringContainsString('outline: 2px solid #FFFFFF;', $css);
        $this->assertStringContainsString('box-shadow: 0 0 0 4px #000000;', $css);
        $this->assertStringNotContainsString('@import', $css);
        $this->assertStringNotContainsString('url(', $css);
    }
}
