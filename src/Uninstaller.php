<?php
namespace HoursFlow;

use HoursFlow\Config\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Permanent HoursFlow data removal.
 */
final class Uninstaller {

	/**
	 * Remove every option created by this MVP.
	 *
	 * @return void
	 */
	public static function uninstall() {
		delete_option( Schema::OPTION_NAME );
		delete_option( Schema::SCHEMA_OPTION_NAME );
	}
}
