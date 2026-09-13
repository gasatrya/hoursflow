<?php
namespace OpenNow\Frontend;

use OpenNow\Config\Repository;
use OpenNow\Schedule\Evaluator;

defined('ABSPATH') || exit;

/**
 * Render the current state CTA and its shared frontend stylesheet.
 */
final class Renderer
{
    /**
     * @var Repository
     */
    private $repository;

    /**
     * @var Evaluator
     */
    private $evaluator;

    /**
     * @param Repository|null $repository
     * @param Evaluator|null $evaluator
     */
    public function __construct(?Repository $repository = null, ?Evaluator $evaluator = null)
    {
        $this->repository = null === $repository ? new Repository() : $repository;
        $this->evaluator = null === $evaluator ? new Evaluator() : $evaluator;
    }

    /**
     * Render the CTA for the current runtime state.
     *
     * @return string
     */
    public function render(): string
    {
        try {
            $config = $this->repository->getRuntimeConfig();
            if (!is_array($config)) {
                return '';
            }

            $schedule = isset($config['schedule']) ? $config['schedule'] : null;
            $timezone = array_key_exists('timezone', $config) ? $config['timezone'] : null;
            $state = $this->evaluator->isOpen($schedule, $timezone) ? 'open' : 'closed';

            if (!isset($config['cta']) || !is_array($config['cta'])
                || !array_key_exists($state, $config['cta'])
                || !is_array($config['cta'][$state])
            ) {
                return '';
            }

            $cta = $config['cta'][$state];
            if (!array_key_exists('label', $cta)
                || !array_key_exists('action', $cta)
                || !array_key_exists('status', $cta)
                || !is_string($cta['label'])
                || !is_string($cta['action'])
                || !is_string($cta['status'])
                || '' === trim($cta['label'])
                || '' === trim($cta['action'])
            ) {
                return '';
            }

            $appearance = isset($config['appearance']) && is_array($config['appearance'])
                ? $config['appearance']
                : array();
            if (!isset($appearance['background_color']) || !isset($appearance['text_color'])
                || !is_string($appearance['background_color'])
                || !is_string($appearance['text_color'])
                || '' === $appearance['background_color']
                || '' === $appearance['text_color']
            ) {
                return '';
            }

            if (!function_exists('esc_url') || !function_exists('esc_html') || !function_exists('esc_attr')) {
                return '';
            }

            $action = esc_url($cta['action'], array('https', 'tel'));
            if (!is_string($action) || '' === $action) {
                return '';
            }

            $wrapper_class = 'opennow-cta opennow-cta--' . $state;
            $style = '--opennow-cta-background-color: ' . $appearance['background_color']
                . '; --opennow-cta-text-color: ' . $appearance['text_color'] . ';';
            $markup = '<div class="' . esc_attr($wrapper_class) . '" style="'
                . esc_attr($style) . '">'
                . '<a class="' . esc_attr('opennow-cta__link') . '" href="'
                . $action . '">' . esc_html($cta['label']) . '</a>';

            if ('' !== $cta['status']) {
                $markup .= '<span class="' . esc_attr('opennow-cta__status') . '">'
                    . esc_html($cta['status']) . '</span>';
            }

            $markup .= '</div>';

            $this->enqueueStyle();

            return $markup;
        } catch (\Throwable $exception) {
            return '';
        }
    }

    /**
     * Enqueue the shared stylesheet after valid markup has been built.
     *
     * @return void
     */
    private function enqueueStyle()
    {
        if (!function_exists('wp_enqueue_style') || !function_exists('plugins_url')) {
            return;
        }

        $plugin_file = defined('OPENNOW_PLUGIN_FILE')
            ? OPENNOW_PLUGIN_FILE
            : dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'opennow.php';
        $version = defined('OPENNOW_VERSION') ? OPENNOW_VERSION : null;

        wp_enqueue_style(
            'opennow-cta',
            plugins_url('assets/public/cta.css', $plugin_file),
            array(),
            $version
        );
    }
}
