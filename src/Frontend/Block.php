<?php
namespace OpenNow\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Register and render the dynamic OpenNow CTA block.
 */
final class Block {

	const BLOCK_DIRECTORY = 'build/blocks/cta';

	/**
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * @param Renderer|null $renderer
	 */
	public function __construct( ?Renderer $renderer = null ) {
		$this->renderer = null === $renderer ? new Renderer() : $renderer;
	}

	/**
	 * Register the block on WordPress's init hook.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'registerBlockType' ) );
	}

	/**
	 * Register the generated block metadata with the shared renderer callback.
	 *
	 * @return void
	 */
	public function registerBlockType() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$block_directory = str_replace( '/', DIRECTORY_SEPARATOR, self::BLOCK_DIRECTORY );
		$directory       = defined( 'OPENNOW_PLUGIN_DIR' )
			? rtrim( OPENNOW_PLUGIN_DIR, '/\\' ) . DIRECTORY_SEPARATOR . $block_directory
			: dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR . $block_directory;

		register_block_type(
			$directory,
			array( 'render_callback' => array( $this, 'render' ) )
		);
	}

	/**
	 * Render the block from the shared current-state renderer.
	 *
	 * @param mixed $attributes Block attributes.
	 * @param mixed $content Block content, intentionally ignored.
	 * @param mixed $block Block instance used to identify WordPress block rendering.
	 * @return string
	 */
	public function render( $attributes = array(), $content = '', $block = null ): string {
		$overrides = array();
		if ( is_array( $attributes ) && array_key_exists( 'overrides', $attributes ) ) {
			$overrides = $attributes['overrides'];
		}
		$is_block_render      = is_object( $block );
		$has_block_typography = $is_block_render && $this->hasTypography( $attributes );
		$block_colors         = $is_block_render ? $this->getColors( $attributes ) : array();
		unset( $content );

		return $this->renderer->render( $overrides, $has_block_typography, $block_colors );
	}

	/**
	 * Determine whether the block has typography attributes for the supports API.
	 *
	 * @param mixed $attributes Block attributes.
	 * @return bool
	 */
	private function hasTypography( $attributes ): bool {
		if ( ! is_array( $attributes ) ) {
			return false;
		}
		foreach ( array( 'fontFamily', 'fontSize' ) as $attribute ) {
			if ( isset( $attributes[ $attribute ] ) && is_string( $attributes[ $attribute ] )
				&& '' !== $attributes[ $attribute ]
			) {
				return true;
			}
		}
		if ( ! isset( $attributes['style'] ) || ! is_array( $attributes['style'] ) ) {
			return false;
		}

		return isset( $attributes['style']['typography'] )
			&& is_array( $attributes['style']['typography'] )
			&& ! empty( $attributes['style']['typography'] );
	}

	/**
	 * Extract the color attributes that are rendered on the CTA link.
	 *
	 * @param mixed $attributes Block attributes.
	 * @return array<string, mixed>
	 */
	private function getColors( $attributes ): array {
		if ( ! is_array( $attributes ) ) {
			return array();
		}

		$colors = array();
		foreach ( array( 'backgroundColor', 'textColor' ) as $attribute ) {
			if ( isset( $attributes[ $attribute ] ) && is_string( $attributes[ $attribute ] )
				&& '' !== $attributes[ $attribute ]
			) {
				$colors[ $attribute ] = $attributes[ $attribute ];
			}
		}
		if ( ! isset( $attributes['style'] ) || ! is_array( $attributes['style'] )
			|| ! isset( $attributes['style']['color'] )
			|| ! is_array( $attributes['style']['color'] )
		) {
			return $colors;
		}

		$custom_colors = array();
		foreach ( array( 'background', 'text' ) as $color ) {
			if ( isset( $attributes['style']['color'][ $color ] )
				&& is_string( $attributes['style']['color'][ $color ] )
				&& '' !== $attributes['style']['color'][ $color ]
			) {
				$custom_colors[ $color ] = $attributes['style']['color'][ $color ];
			}
		}
		if ( ! empty( $custom_colors ) ) {
			$colors['style'] = array( 'color' => $custom_colors );
		}

		return $colors;
	}
}
