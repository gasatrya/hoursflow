<?php
namespace OpenNow;

use OpenNow\Config\Schema;

defined('ABSPATH') || exit;

/**
 * Plugin lifecycle callbacks.
 */
final class Lifecycle
{
    /**
     * Initialise only the schema marker. Existing configuration is retained.
     *
     * @return void
     */
    public static function activate()
    {
        add_option(Schema::SCHEMA_OPTION_NAME, Schema::VERSION, '', false);
    }

    /**
     * Deactivation intentionally does not mutate configuration.
     *
     * @return void
     */
    public static function deactivate()
    {
        // Settings are retained for a future reactivation.
    }
}
