<?php
namespace OpenNow\Admin;

use OpenNow\Config\Schema;
use OpenNow\Config\Validator;

defined('ABSPATH') || exit;

/**
 * Conditional Settings API registration for the atomic configuration option.
 */
final class Settings
{
    /**
     * Register the admin_init callback. No settings UI is created here.
     *
     * @return void
     */
    public function register()
    {
        add_action('admin_init', array($this, 'registerSetting'));
    }

    /**
     * Register the one atomic option with the Settings API.
     *
     * @return void
     */
    public function registerSetting()
    {
        register_setting(
            'opennow',
            Schema::OPTION_NAME,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize'),
                'show_in_rest' => false,
            )
        );
    }

    /**
     * Validate a Settings API submission without ever partially persisting it.
     *
     * @param mixed $value
     * @return array|false
     */
    public function sanitize($value)
    {
        $validated = Validator::validate($value);
        if (!is_wp_error($validated)) {
            return $validated;
        }

        foreach ($validated->get_error_codes() as $code) {
            foreach ($validated->get_error_messages($code) as $message) {
                add_settings_error(
                    Schema::OPTION_NAME,
                    (string) $code,
                    $message,
                    'error'
                );
            }
        }

        return get_option(Schema::OPTION_NAME, false);
    }
}
