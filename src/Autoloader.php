<?php
namespace OpenNow;

defined('ABSPATH') || exit;

/**
 * Small PSR-4 autoloader for the plugin namespace.
 */
final class Autoloader
{
    /**
     * Register the OpenNow namespace loader.
     *
     * @param string $directory Directory containing the namespace classes.
     * @return void
     */
    public static function register($directory)
    {
        $directory = rtrim($directory, '/\\');
        $prefix = __NAMESPACE__ . '\\';

        spl_autoload_register(
            static function ($class) use ($directory, $prefix) {
                if (0 !== strpos($class, $prefix)) {
                    return;
                }

                $relative_class = substr($class, strlen($prefix));
                if ('' === $relative_class) {
                    return;
                }

                $file = $directory . DIRECTORY_SEPARATOR
                    . str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';

                if (is_file($file)) {
                    require_once $file;
                }
            }
        );
    }
}
