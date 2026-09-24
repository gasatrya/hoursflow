<?php
namespace HoursFlow;

defined( 'ABSPATH' ) || exit;

/**
 * Small PSR-4 autoloader for the plugin namespace.
 */
final class Autoloader {

	/**
	 * Register the HoursFlow namespace loader.
	 *
	 * @param string $directory Directory containing the namespace classes.
	 * @return void
	 */
	public static function register( $directory ) {
		$directory = rtrim( $directory, '/\\' );
		$prefix    = __NAMESPACE__ . '\\';

		spl_autoload_register(
			static function ( $class_name ) use ( $directory, $prefix ) {
				if ( 0 !== strpos( $class_name, $prefix ) ) {
					return;
				}

				$relative_class = substr( $class_name, strlen( $prefix ) );
				if ( '' === $relative_class ) {
					return;
				}

				$file = $directory . DIRECTORY_SEPARATOR
					. str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

				if ( is_file( $file ) ) {
					require_once $file;
				}
			}
		);
	}
}
