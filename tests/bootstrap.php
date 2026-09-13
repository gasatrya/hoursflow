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
$GLOBALS['opennow_test_filters'] = array();
$GLOBALS['opennow_test_admin_pages'] = array();
$GLOBALS['opennow_test_settings_sections'] = array();
$GLOBALS['opennow_test_settings_fields'] = array();
$GLOBALS['opennow_test_enqueued_scripts'] = array();
$GLOBALS['opennow_test_current_user_can'] = true;
$GLOBALS['opennow_test_is_admin'] = false;

if (!class_exists('OpenNow_Test_Locale')) {
    /**
     * Minimal WP_Locale test double.
     */
    class OpenNow_Test_Locale
    {
        public function get_weekday($weekday_number)
        {
            $weekdays = array(
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
            );

            return isset($weekdays[$weekday_number]) ? $weekdays[$weekday_number] : '';
        }
    }
}

$GLOBALS['wp_locale'] = new OpenNow_Test_Locale();

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

if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default')
    {
        return esc_html(__($text, $domain));
    }
}

if (!function_exists('esc_attr__')) {
    function esc_attr__($text, $domain = 'default')
    {
        return esc_attr(__($text, $domain));
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

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $accepted_args = 1)
    {
        if (!isset($GLOBALS['opennow_test_filters'][$hook])) {
            $GLOBALS['opennow_test_filters'][$hook] = array();
        }

        $GLOBALS['opennow_test_filters'][$hook][] = array(
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        );
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook, $value, ...$args)
    {
        if (empty($GLOBALS['opennow_test_filters'][$hook])) {
            return $value;
        }

        foreach ($GLOBALS['opennow_test_filters'][$hook] as $registered) {
            $filter_args = array_merge(array($value), $args);
            $value = call_user_func_array(
                $registered['callback'],
                array_slice($filter_args, 0, $registered['accepted_args'])
            );
        }

        return $value;
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

if (!function_exists('add_options_page')) {
    function add_options_page($page_title, $menu_title, $capability, $menu_slug, $callback)
    {
        $hook = 'settings_page_' . $menu_slug;
        $GLOBALS['opennow_test_admin_pages'][$menu_slug] = array(
            'page_title' => $page_title,
            'menu_title' => $menu_title,
            'capability' => $capability,
            'callback' => $callback,
            'hook' => $hook,
        );

        return $hook;
    }
}

if (!function_exists('add_settings_section')) {
    function add_settings_section($id, $title, $callback, $page)
    {
        if (!isset($GLOBALS['opennow_test_settings_sections'][$page])) {
            $GLOBALS['opennow_test_settings_sections'][$page] = array();
        }

        $GLOBALS['opennow_test_settings_sections'][$page][$id] = array(
            'title' => $title,
            'callback' => $callback,
        );
    }
}

if (!function_exists('add_settings_field')) {
    function add_settings_field($id, $title, $callback, $page, $section = 'default', $args = array())
    {
        if (!isset($GLOBALS['opennow_test_settings_fields'][$page])) {
            $GLOBALS['opennow_test_settings_fields'][$page] = array();
        }
        if (!isset($GLOBALS['opennow_test_settings_fields'][$page][$section])) {
            $GLOBALS['opennow_test_settings_fields'][$page][$section] = array();
        }

        $GLOBALS['opennow_test_settings_fields'][$page][$section][$id] = array(
            'title' => $title,
            'callback' => $callback,
            'args' => $args,
        );
    }
}

if (!function_exists('settings_fields')) {
    function settings_fields($option_group)
    {
        echo '<input type="hidden" name="option_page" value="' . esc_attr($option_group) . '" />';
        echo '<input type="hidden" name="_wpnonce" value="test-nonce" />';
    }
}

if (!function_exists('do_settings_sections')) {
    function do_settings_sections($page)
    {
        if (empty($GLOBALS['opennow_test_settings_sections'][$page])) {
            return;
        }

        foreach ($GLOBALS['opennow_test_settings_sections'][$page] as $section_id => $section) {
            echo '<h2>' . esc_html($section['title']) . '</h2>';
            call_user_func($section['callback'], array('id' => $section_id));

            if (empty($GLOBALS['opennow_test_settings_fields'][$page][$section_id])) {
                continue;
            }

            foreach ($GLOBALS['opennow_test_settings_fields'][$page][$section_id] as $field) {
                call_user_func($field['callback'], $field['args']);
            }
        }
    }
}

if (!function_exists('get_settings_errors')) {
    function get_settings_errors($setting = '', $sanitize = false)
    {
        return array_values(array_filter(
            $GLOBALS['opennow_test_settings_errors'],
            static function ($error) use ($setting): bool {
                return '' === $setting || $setting === $error['setting'];
            }
        ));
    }
}

if (!function_exists('settings_errors')) {
    function settings_errors($setting = '', $sanitize = false, $hide_on_update = false)
    {
        foreach (get_settings_errors($setting, $sanitize) as $error) {
            echo '<div id="setting-error-' . esc_attr($error['code']) . '" class="notice notice-'
                . esc_attr($error['type']) . '"><p>'
                . esc_html($error['message'])
                . '</p></div>';
        }
    }
}

if (!function_exists('submit_button')) {
    function submit_button($text = null)
    {
        echo '<button type="submit">' . esc_html(null === $text ? 'Save Changes' : $text) . '</button>';
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability)
    {
        return (bool) $GLOBALS['opennow_test_current_user_can'];
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '')
    {
        throw new RuntimeException((string) $message);
    }
}

if (!function_exists('wp_timezone_choice')) {
    function wp_timezone_choice($selected_zone, $locale = null)
    {
        $selected_city = 'America/New_York' === $selected_zone ? ' selected="selected"' : '';
        $selected_utc = 'UTC' === $selected_zone ? ' selected="selected"' : '';

        return '<option value="">Select a city</option>'
            . '<optgroup label="America"><option value="America/New_York"'
            . $selected_city . '>New York</option></optgroup>'
            . '<optgroup label="UTC"><option value="UTC"'
            . $selected_utc . '>UTC</option></optgroup>'
            . '<optgroup label="Manual Offsets"><option value="UTC+1">UTC+1</option></optgroup>';
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '')
    {
        return 'https://example.test/wp-content/plugins/opennow/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $args = array())
    {
        $GLOBALS['opennow_test_enqueued_scripts'][$handle] = array(
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
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
    $GLOBALS['opennow_test_filters'] = array();
    $GLOBALS['opennow_test_admin_pages'] = array();
    $GLOBALS['opennow_test_settings_sections'] = array();
    $GLOBALS['opennow_test_settings_fields'] = array();
    $GLOBALS['opennow_test_enqueued_scripts'] = array();
    $GLOBALS['opennow_test_current_user_can'] = true;
    $GLOBALS['opennow_test_is_admin'] = false;
    $GLOBALS['wp_locale'] = new OpenNow_Test_Locale();
}
