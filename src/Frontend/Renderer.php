<?php
namespace OpenNow\Frontend;

use OpenNow\Config\Repository;
use OpenNow\Config\Validator;
use OpenNow\Schedule\Evaluator;

defined( 'ABSPATH' ) || exit;

/**
 * Render the current state CTA and its shared frontend stylesheet.
 */
final class Renderer {

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
	 * @param Evaluator|null  $evaluator
	 */
	public function __construct( ?Repository $repository = null, ?Evaluator $evaluator = null ) {
		$this->repository = null === $repository ? new Repository() : $repository;
		$this->evaluator  = null === $evaluator ? new Evaluator() : $evaluator;
	}

	/**
	 * Render the CTA for the current runtime state.
	 *
	 * @param mixed $overrides Raw per-state override candidate.
	 * @param bool  $is_block_render Whether to apply WordPress block wrapper supports.
	 * @param mixed $block_colors Canonical per-block color attributes.
	 * @return string
	 */
	public function render( $overrides = array(), bool $is_block_render = false, $block_colors = array() ): string {
		try {
			$config = $this->repository->getRuntimeConfig();
			if ( ! is_array( $config ) ) {
				return '';
			}

			$schedule = isset( $config['schedule'] ) ? $config['schedule'] : null;
			$timezone = array_key_exists( 'timezone', $config ) ? $config['timezone'] : null;
			$state    = $this->evaluator->isOpen( $schedule, $timezone ) ? 'open' : 'closed';

			if ( ! isset( $config['cta'] ) || ! is_array( $config['cta'] )
				|| ! array_key_exists( $state, $config['cta'] )
				|| ! is_array( $config['cta'][ $state ] )
			) {
				return '';
			}

			$cta = $config['cta'][ $state ];
			if ( ! array_key_exists( 'label', $cta )
				|| ! array_key_exists( 'action', $cta )
				|| ! array_key_exists( 'status', $cta )
				|| ! is_string( $cta['label'] )
				|| ! is_string( $cta['action'] )
				|| ! is_string( $cta['status'] )
				|| '' === trim( $cta['label'] )
				|| '' === trim( $cta['action'] )
			) {
				return '';
			}

			$canonical_overrides = Validator::canonicalCtaOverrides( $overrides );
			$hide_status         = false;
			if ( isset( $canonical_overrides[ $state ] ) ) {
				$state_overrides = $canonical_overrides[ $state ];
				$hide_status     = isset( $state_overrides['hideStatus'] )
					&& true === $state_overrides['hideStatus'];
				unset( $state_overrides['hideStatus'] );
				$cta = array_replace( $cta, $state_overrides );
			}

			$appearance = isset( $config['appearance'] ) && is_array( $config['appearance'] )
				? $config['appearance']
				: array();
			if ( ! isset( $appearance['background_color'] ) || ! isset( $appearance['text_color'] )
				|| ! is_string( $appearance['background_color'] )
				|| ! is_string( $appearance['text_color'] )
				|| '' === $appearance['background_color']
				|| '' === $appearance['text_color']
			) {
				return '';
			}

			if ( ! function_exists( 'esc_url' ) || ! function_exists( 'esc_html' ) || ! function_exists( 'esc_attr' ) ) {
				return '';
			}

			$action = esc_url( $cta['action'], array( 'https', 'tel' ) );
			if ( ! is_string( $action ) || '' === $action ) {
				return '';
			}

			$wrapper_class      = 'opennow-cta opennow-cta--' . $state;
			$style              = '--opennow-cta-background-color: ' . $appearance['background_color']
				. '; --opennow-cta-text-color: ' . $appearance['text_color'] . ';';
			$wrapper_attributes = 'class="' . esc_attr( $wrapper_class ) . '" style="'
				. esc_attr( $style ) . '"';
			if ( $is_block_render && function_exists( 'get_block_wrapper_attributes' ) ) {
				$wrapper_attributes = get_block_wrapper_attributes(
					array(
						'class' => $wrapper_class,
						'style' => $style,
					)
				);
			}
			$link_attributes = $this->linkAttributes( $block_colors );
			$markup          = '<div ' . $wrapper_attributes . '>'
				. '<a ' . $link_attributes . ' href="' . $action . '">'
				. esc_html( $cta['label'] ) . '</a>';

			if ( ! $hide_status && '' !== $cta['status'] ) {
				$markup .= '<span class="' . esc_attr( 'opennow-cta__status' ) . '">'
					. esc_html( $cta['status'] ) . '</span>';
			}

			$markup .= '</div>';

			$this->enqueueStyle();

			return $markup;
		} catch ( \Throwable $exception ) {
			return '';
		}
	}

	/**
	 * Build link attributes with optional WordPress color support output.
	 *
	 * @param mixed $block_colors Canonical per-block color attributes.
	 * @return string
	 */
	private function linkAttributes( $block_colors ): string {
		$classes = array( 'opennow-cta__link' );
		$styles  = array();
		if ( is_array( $block_colors ) && function_exists( 'wp_style_engine_get_styles' ) ) {
			$custom_colors = isset( $block_colors['style'] ) && is_array( $block_colors['style'] )
				&& isset( $block_colors['style']['color'] ) && is_array( $block_colors['style']['color'] )
				? $block_colors['style']['color']
				: array();
			foreach (
				array(
					'text'       => 'textColor',
					'background' => 'backgroundColor',
				) as $color => $preset_attribute
			) {
				$is_preset = isset( $block_colors[ $preset_attribute ] )
					&& is_string( $block_colors[ $preset_attribute ] )
					&& '' !== $block_colors[ $preset_attribute ];
				$value     = $is_preset
					? 'var:preset|color|' . $block_colors[ $preset_attribute ]
					: ( isset( $custom_colors[ $color ] ) && is_string( $custom_colors[ $color ] )
						? $custom_colors[ $color ]
						: '' );
				if ( '' === $value ) {
					continue;
				}

				$generated = wp_style_engine_get_styles(
					array( 'color' => array( $color => $value ) ),
					array( 'convert_vars_to_classnames' => true )
				);
				if ( ! is_array( $generated ) ) {
					continue;
				}

				$generated_style = '';
				if ( isset( $generated['css'] ) && is_string( $generated['css'] )
					&& '' !== $generated['css'] && function_exists( 'safecss_filter_attr' )
				) {
					$generated_style = rtrim( trim( safecss_filter_attr( $generated['css'] ) ), ';' );
				}
				if ( ! $is_preset && '' === $generated_style ) {
					continue;
				}
				if ( isset( $generated['classnames'] ) && is_string( $generated['classnames'] )
					&& '' !== $generated['classnames']
				) {
					$classes[] = $generated['classnames'];
				}
				if ( '' !== $generated_style ) {
					$styles[] = $generated_style;
				}
			}
		}

		$attributes = 'class="' . esc_attr( implode( ' ', $classes ) ) . '"';
		if ( ! empty( $styles ) ) {
			$attributes .= ' style="' . esc_attr( implode( ';', $styles ) ) . '"';
		}

		return $attributes;
	}

	/**
	 * Enqueue the shared stylesheet after valid markup has been built.
	 *
	 * @return void
	 */
	private function enqueueStyle() {
		if ( ! function_exists( 'wp_enqueue_style' ) || ! function_exists( 'plugins_url' ) ) {
			return;
		}

		$plugin_file = defined( 'OPENNOW_PLUGIN_FILE' )
			? OPENNOW_PLUGIN_FILE
			: dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR . 'opennow.php';
		$version     = defined( 'OPENNOW_VERSION' ) ? OPENNOW_VERSION : null;

		wp_enqueue_style(
			'opennow-cta',
			plugins_url( 'assets/public/cta.css', $plugin_file ),
			array(),
			$version
		);
	}
}
