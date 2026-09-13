<?php
namespace OpenNow\Frontend;

defined('ABSPATH') || exit;

/**
 * Register the OpenNow CTA shortcode.
 */
final class Shortcode
{
    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @param Renderer|null $renderer
     */
    public function __construct(?Renderer $renderer = null)
    {
        $this->renderer = null === $renderer ? new Renderer() : $renderer;
    }

    /**
     * Register the shortcode callback.
     *
     * @return void
     */
    public function register()
    {
        add_shortcode('opennow_cta', array($this, 'render'));
    }

    /**
     * Render the shortcode without accepting attribute or content overrides.
     *
     * @param mixed  $attributes
     * @param mixed  $content
     * @param string $tag
     * @return string
     */
    public function render($attributes = array(), $content = null, $tag = ''): string
    {
        return $this->renderer->render();
    }
}
