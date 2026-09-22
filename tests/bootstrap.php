<?php

if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . DIRECTORY_SEPARATOR);
}

$GLOBALS['opennow_test_options'] = array();
$GLOBALS['opennow_test_option_calls'] = array();
$GLOBALS['opennow_test_hooks'] = array();
$GLOBALS['opennow_test_shortcodes'] = array();
$GLOBALS['opennow_test_registered_blocks'] = array();
$GLOBALS['opennow_test_activation_hooks'] = array();
$GLOBALS['opennow_test_deactivation_hooks'] = array();
$GLOBALS['opennow_test_registered_settings'] = array();
$GLOBALS['opennow_test_settings_errors'] = array();
$GLOBALS['opennow_test_filters'] = array();
$GLOBALS['opennow_test_admin_pages'] = array();
$GLOBALS['opennow_test_settings_sections'] = array();
$GLOBALS['opennow_test_settings_fields'] = array();
$GLOBALS['opennow_test_enqueued_scripts'] = array();
$GLOBALS['opennow_test_enqueued_styles'] = array();
$GLOBALS['opennow_test_current_user_can'] = true;
$GLOBALS['opennow_test_is_admin'] = false;
$GLOBALS['opennow_test_current_screen'] = null;
$GLOBALS['opennow_test_nonce_checks'] = array();
$GLOBALS['opennow_test_redirects'] = array();

if (!class_exists('OpenNow_Test_Screen')) {
    /**
     * Minimal WP_Screen test double.
     */
    class OpenNow_Test_Screen
    {
        /**
         * @var string
         */
        public $id;

        /**
         * @var array<int, array<string, mixed>>
         */
        public $help_tabs = array();

        /**
         * @param string $id
         */
        public function __construct($id = '')
        {
            $this->id = $id;
        }

        /**
         * @param array<string, mixed> $help_tab
         * @return void
         */
        public function add_help_tab($help_tab)
        {
            $this->help_tabs[] = $help_tab;
        }
    }
}

if (!class_exists('OpenNow_Test_Redirect_Exception')) {
    /**
     * Stops execution after a redirect during unit tests.
     */
    class OpenNow_Test_Redirect_Exception extends RuntimeException
    {
    }
}

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

if (!function_exists('esc_url')) {
    function esc_url($url, $protocols = null, $_context = 'display')
    {
        if (!is_string($url)) {
            return '';
        }

        $url = trim($url);
        if ('' === $url || false !== strpos($url, "\0")
            || 1 === preg_match('/[\x00-\x1F\x7F-\x9F]/', $url)
        ) {
            return '';
        }

        if (null !== $protocols && 1 === preg_match('/\A([a-z][a-z0-9+.-]*):/i', $url, $matches)) {
            $protocol = strtolower($matches[1]);
            $allowed = array();
            foreach ((array) $protocols as $allowed_protocol) {
                if (is_string($allowed_protocol)) {
                    $allowed[] = strtolower($allowed_protocol);
                }
            }

            if (!in_array($protocol, $allowed, true)) {
                return '';
            }
        }

        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_js')) {
    function esc_js($text)
    {
        return addslashes((string) $text);
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return is_array($value) ? array_map('wp_unslash', $value) : stripslashes((string) $value);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        return strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $key));
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

if (!function_exists('add_shortcode')) {
    function add_shortcode($tag, $callback)
    {
        $GLOBALS['opennow_test_shortcodes'][$tag] = $callback;
    }
}

if (!function_exists('register_block_type')) {
    function register_block_type($block_type, $args = array())
    {
        $GLOBALS['opennow_test_registered_blocks'][] = array(
            'block_type' => $block_type,
            'args' => $args,
        );

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
    function submit_button($text = null, $type = 'primary large', $name = 'submit', $wrap = true, $other_attributes = null)
    {
        $attributes = '';
        foreach ((array) $other_attributes as $attribute => $value) {
            $attributes .= ' ' . esc_attr($attribute) . '="' . esc_attr($value) . '"';
        }

        $button = '<button type="submit" name="' . esc_attr($name) . '" class="button button-'
            . esc_attr($type) . '"' . $attributes . '>'
            . esc_html(null === $text ? 'Save Changes' : $text) . '</button>';

        echo $wrap ? '<p class="submit">' . $button . '</p>' : $button;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability)
    {
        return (bool) $GLOBALS['opennow_test_current_user_can'];
    }
}

if (!function_exists('get_current_screen')) {
    function get_current_screen()
    {
        return $GLOBALS['opennow_test_current_screen'];
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '')
    {
        throw new RuntimeException((string) $message);
    }
}

if (!function_exists('check_admin_referer')) {
    function check_admin_referer($action = -1, $query_arg = '_wpnonce')
    {
        $GLOBALS['opennow_test_nonce_checks'][] = array(
            'action' => $action,
            'query_arg' => $query_arg,
        );

        return isset($_POST[$query_arg]) && 'test-nonce' === $_POST[$query_arg];
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '')
    {
        return 'https://example.test/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $url = '')
    {
        $separator = false === strpos($url, '?') ? '?' : '&';
        return $url . $separator . http_build_query($args, '', '&', PHP_QUERY_RFC3986);
    }
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($location, $status = 302, $x_redirect_by = 'WordPress')
    {
        $GLOBALS['opennow_test_redirects'][] = array(
            'location' => $location,
            'status' => $status,
            'x_redirect_by' => $x_redirect_by,
        );

        throw new OpenNow_Test_Redirect_Exception($location);
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

if (!function_exists('wp_style_engine_get_styles')) {
    function wp_style_engine_get_styles($block_styles, $options = array())
    {
        $colors = isset($block_styles['color']) && is_array($block_styles['color'])
            ? $block_styles['color']
            : array();
        $classes = array();
        $styles = array();
        foreach (array('text' => 'color', 'background' => 'background-color') as $type => $property) {
            if (!isset($colors[$type]) || !is_string($colors[$type]) || '' === $colors[$type]) {
                continue;
            }

            $generic_class = 'text' === $type ? 'has-text-color' : 'has-background';
            $classes[] = $generic_class;
            if (0 === strpos($colors[$type], 'var:preset|color|')) {
                $slug = substr($colors[$type], strlen('var:preset|color|'));
                $classes[] = 'text' === $type
                    ? 'has-' . $slug . '-color'
                    : 'has-' . $slug . '-background-color';
            } else {
                $styles[] = $property . ':' . $colors[$type];
            }
        }

        return array(
            'classnames' => implode(' ', $classes),
            'css' => implode(';', $styles),
        );
    }
}

if (!function_exists('safecss_filter_attr')) {
    function safecss_filter_attr($css)
    {
        if (!is_string($css) || false !== stripos($css, 'expression') || false !== stripos($css, 'url(')) {
            return '';
        }

        return $css;
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

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all')
    {
        $GLOBALS['opennow_test_enqueued_styles'][$handle] = array(
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'media' => $media,
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
    $GLOBALS['opennow_test_shortcodes'] = array();
    $GLOBALS['opennow_test_registered_blocks'] = array();
    $GLOBALS['opennow_test_activation_hooks'] = array();
    $GLOBALS['opennow_test_deactivation_hooks'] = array();
    $GLOBALS['opennow_test_registered_settings'] = array();
    $GLOBALS['opennow_test_settings_errors'] = array();
    $GLOBALS['opennow_test_filters'] = array();
    $GLOBALS['opennow_test_admin_pages'] = array();
    $GLOBALS['opennow_test_settings_sections'] = array();
    $GLOBALS['opennow_test_settings_fields'] = array();
    $GLOBALS['opennow_test_enqueued_scripts'] = array();
    $GLOBALS['opennow_test_enqueued_styles'] = array();
    $GLOBALS['opennow_test_current_user_can'] = true;
    $GLOBALS['opennow_test_is_admin'] = false;
    $GLOBALS['opennow_test_current_screen'] = null;
    $GLOBALS['opennow_test_nonce_checks'] = array();
    $GLOBALS['opennow_test_redirects'] = array();
    $_GET = array();
    $_POST = array();
    $GLOBALS['wp_locale'] = new OpenNow_Test_Locale();
}
