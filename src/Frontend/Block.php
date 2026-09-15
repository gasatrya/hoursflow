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
	 * @param mixed $block Block instance, intentionally ignored.
	 * @return string
	 */
	public function render( $attributes = array(), $content = '', $block = null ): string {
		$overrides = array();
		if ( is_array( $attributes ) && array_key_exists( 'overrides', $attributes ) ) {
			$overrides = $attributes['overrides'];
		}
		unset( $content, $block );

		return $this->renderer->render( $overrides );
	}
}
