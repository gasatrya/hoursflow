<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
}

$GLOBALS['opennow_test_options'] = array();
$GLOBALS['opennow_test_option_calls'] = array();
$GLOBALS['opennow_test_hooks'] = array();
$GLOBALS['opennow_test_activation_hooks'] = array();
$GLOBALS['opennow_test_deactivation_hooks'] = array();
$GLOBALS['opennow_test_registered_settings'] = array();
$GLOBALS['opennow_test_settings_errors'] = array();
$GLOBALS['opennow_test_is_admin'] = false;

if (!class_exists('WP_Error')) {
    /**
     * Minimal WP_Error test double.
     */
    class WP_Error
    {
        /**
         * @var array<string, array<int, string>>
         */
        private $errors = array();

        /**
         * @var array<string, mixed>
         */
        private $data = array();

        public function __construct($code = '', $message = '', $data = '')
        {
            if ('' !== $code) {
                $this->add($code, $message, $data);
            }
        }

        public function add($code, $message, $data = '')
        {
            if (!isset($this->errors[$code])) {
                $this->errors[$code] = array();
            }

            $this->errors[$code][] = $message;
            if ('' !== $data) {
                $this->data[$code] = $data;
            }
        }

        public function has_errors()
        {
            return !empty($this->errors);
        }

        public function get_error_codes()
        {
            return array_keys($this->errors);
        }

        public function get_error_messages($code = '')
        {
            if ('' === $code) {
                $messages = array();
                foreach ($this->errors as $code_messages) {
                    $messages = array_merge($messages, $code_messages);
                }

                return $messages;
            }

            return isset($this->errors[$code]) ? $this->errors[$code] : array();
        }

        public function get_error_data($code = '')
        {
            return isset($this->data[$code]) ? $this->data[$code] : null;
        }
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($value)
    {
        return $value instanceof WP_Error;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false)
    {
        return array_key_exists($option, $GLOBALS['opennow_test_options'])
            ? $GLOBALS['opennow_test_options'][$option]
            : $default;
    }
}

if (!function_exists('add_option')) {
    function add_option($option, $value = '', $deprecated = '', $autoload = null)
    {
        $GLOBALS['opennow_test_option_calls'][] = array(
            'function' => 'add_option',
            'option' => $option,
            'value' => $value,
            'deprecated' => $deprecated,
            'autoload' => $autoload,
        );

        if (array_key_exists($option, $GLOBALS['opennow_test_options'])) {
            return false;
        }

        $GLOBALS['opennow_test_options'][$option] = $value;
        return true;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null)
    {
        $GLOBALS['opennow_test_option_calls'][] = array(
            'function' => 'update_option',
            'option' => $option,
            'value' => $value,
            'autoload' => $autoload,
        );
        $GLOBALS['opennow_test_options'][$option] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option)
    {
        $GLOBALS['opennow_test_option_calls'][] = array(
            'function' => 'delete_option',
            'option' => $option,
        );
        unset($GLOBALS['opennow_test_options'][$option]);
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        if (!isset($GLOBALS['opennow_test_hooks'][$hook])) {
            $GLOBALS['opennow_test_hooks'][$hook] = array();
        }

        $GLOBALS['opennow_test_hooks'][$hook][] = array(
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        );
        return true;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook, ...$args)
    {
        if (empty($GLOBALS['opennow_test_hooks'][$hook])) {
            return;
        }

        foreach ($GLOBALS['opennow_test_hooks'][$hook] as $registered) {
            call_user_func_array($registered['callback'], $args);
        }
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback)
    {
        $GLOBALS['opennow_test_activation_hooks'][] = array(
            'file' => $file,
            'callback' => $callback,
        );
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback)
    {
        $GLOBALS['opennow_test_deactivation_hooks'][] = array(
            'file' => $file,
            'callback' => $callback,
        );
    }
}

if (!function_exists('is_admin')) {
    function is_admin()
    {
        return (bool) $GLOBALS['opennow_test_is_admin'];
    }
}

if (!function_exists('register_setting')) {
    function register_setting($group, $option, $args = array())
    {
        $GLOBALS['opennow_test_registered_settings'][$option] = array(
            'group' => $group,
            'args' => $args,
        );
    }
}

if (!function_exists('add_settings_error')) {
    function add_settings_error($setting, $code, $message, $type = 'error')
    {
        $GLOBALS['opennow_test_settings_errors'][] = array(
            'setting' => $setting,
            'code' => $code,
            'message' => $message,
            'type' => $type,
        );
    }
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url($url, $component = -1)
    {
        return parse_url($url, $component);
    }
}

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Autoloader.php';
\OpenNow\Autoloader::register(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src');

/**
 * Reset mutable WordPress test doubles between tests.
 *
 * @return void
 */
function opennow_reset_wp_stubs()
{
    $GLOBALS['opennow_test_options'] = array();
    $GLOBALS['opennow_test_option_calls'] = array();
    $GLOBALS['opennow_test_hooks'] = array();
    $GLOBALS['opennow_test_activation_hooks'] = array();
    $GLOBALS['opennow_test_deactivation_hooks'] = array();
    $GLOBALS['opennow_test_registered_settings'] = array();
    $GLOBALS['opennow_test_settings_errors'] = array();
    $GLOBALS['opennow_test_is_admin'] = false;
}
