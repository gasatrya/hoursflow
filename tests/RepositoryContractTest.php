<?php
namespace HoursFlow\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class RepositoryContractTest extends TestCase
{
    public function testRuntimePhpFilesHaveDirectAccessGuards(): void
    {
        foreach ($this->runtimePhpFiles() as $path) {
            $contents = file_get_contents($path);
            $this->assertIsString($contents);
            $this->assertMatchesRegularExpression(
                "/defined\\s*\\(\\s*['\"]ABSPATH['\"]\\s*\\)\\s*\\|\\|\\s*exit\\s*;/",
                $contents,
                $path . ' must guard direct access.'
            );
        }
    }

    public function testRuntimeNamesUseThePluginNamespaceAndUniqueWordPressHandles(): void
    {
        $class_names = array();
        $shortcode_tags = array();
        $asset_handles = array();

        foreach ($this->runtimePhpFiles() as $path) {
            $contents = file_get_contents($path);
            $this->assertIsString($contents);
            if (!in_array(basename($path), array('hoursflow.php', 'uninstall.php'), true)) {
                $this->assertStringContainsString('namespace HoursFlow', $contents, $path);
            }

            foreach ($this->classNames($contents) as $class_name) {
                $this->assertArrayNotHasKey(
                    $class_name,
                    $class_names,
                    'Runtime class names must be unique.'
                );
                $class_names[$class_name] = $path;
            }

            if (1 === preg_match_all(
                "/add_shortcode\\s*\\(\\s*['\"]([^'\"]+)['\"]/",
                $contents,
                $matches
            )) {
                foreach ($matches[1] as $tag) {
                    $this->assertStringStartsWith('hoursflow_', $tag);
                    $this->assertNotContains($tag, $shortcode_tags);
                    $shortcode_tags[] = $tag;
                }
            }

            if (preg_match_all(
                "/wp_enqueue_(?:script|style)\\s*\\(\\s*['\"]([^'\"]+)['\"]/",
                $contents,
                $matches
            )) {
                foreach ($matches[1] as $handle) {
                    $this->assertStringStartsWith('hoursflow-', $handle);
                    $this->assertNotContains($handle, $asset_handles);
                    $asset_handles[] = $handle;
                }
            }
        }

        $this->assertSame(array('hoursflow_cta'), $shortcode_tags);
        $this->assertSame(
            array('hoursflow-admin-settings', 'hoursflow-admin-settings-style', 'hoursflow-cta'),
            $asset_handles
        );
    }

    public function testRuntimeHasNoExternalRequestsTrackingOrRemoteAssets(): void
    {
        $contents = '';
        foreach ($this->runtimeFiles() as $path) {
            $file_contents = file_get_contents($path);
            $this->assertIsString($file_contents);
            $contents .= "\n" . $file_contents;
        }

        $this->assertDoesNotMatchRegularExpression(
            '/\\b(?:wp_safe_remote_|wp_remote_|curl_(?:exec|init)|fsockopen|stream_socket_client)\\s*\\(/i',
            $contents
        );
        $this->assertDoesNotMatchRegularExpression(
            '/file_get_contents\\s*\\(\\s*[\'\"]https?:/i',
            $contents
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\\b(?:fetch|XMLHttpRequest|sendBeacon|gtag|analytics|telemetry)\\b/i',
            $contents
        );
        $styles_and_scripts = '';
        foreach (array(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets'
                . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'settings.js',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets'
                . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'settings.css',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets'
                . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'cta.css',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
                . 'blocks' . DIRECTORY_SEPARATOR . 'cta' . DIRECTORY_SEPARATOR . 'index.js',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
                . 'blocks' . DIRECTORY_SEPARATOR . 'cta' . DIRECTORY_SEPARATOR . 'editor.scss',
        ) as $path) {
            $file_contents = file_get_contents($path);
            $this->assertIsString($file_contents);
            $styles_and_scripts .= "\\n" . $file_contents;
        }
        $this->assertDoesNotMatchRegularExpression('/@import|url\\s*\\(/i', $styles_and_scripts);
    }

    public function testPluginAndBlockMetadataStayConsistent(): void
    {
        $plugin_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'hoursflow.php';
        $plugin = file_get_contents($plugin_file);
        $this->assertIsString($plugin);
        $this->assertStringContainsString('* Plugin Name: HoursFlow — Business Hours CTA', $plugin);
        $this->assertStringContainsString('* Description: Show an open or closed call to action based on your weekly business hours.', $plugin);
        $this->assertStringContainsString('* Requires at least: 6.6', $plugin);
        $this->assertStringContainsString('* Requires PHP: 7.4', $plugin);
        $this->assertStringContainsString('* Author: Ga Satrya', $plugin);
        $this->assertStringContainsString('* Author URI: https://gasatrya.com/', $plugin);
        $this->assertStringContainsString('* Plugin URI: https://gasatrya.com/wp-plugins/hoursflow/', $plugin);
        $this->assertStringContainsString('* License: GPL-2.0-or-later', $plugin);
        $this->assertStringContainsString('* Text Domain: hoursflow', $plugin);
        $this->assertMatchesRegularExpression(
            "/define\\s*\\(\\s*['\"]HOURSFLOW_VERSION['\"]\\s*,\\s*['\"]0\\.1\\.0['\"]\\s*\\)/",
            $plugin
        );

        $readme = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'readme.txt');
        $this->assertIsString($readme);
        $this->assertStringContainsString("Requires at least: 6.6\n", $readme);
        $this->assertStringContainsString("Requires PHP: 7.4\n", $readme);
        $this->assertStringContainsString("Tested up to: 7.1\n", $readme);
        $this->assertStringContainsString("Stable tag: 0.1.0\n", $readme);
        $this->assertStringContainsString("Donate link: https://gasatrya.com/donate/\n", $readme);
        $this->assertStringNotContainsString('https://wordpress.org/support/plugin/hoursflow/', $readme);
        $this->assertStringContainsString("plugin page's Support tab once the plugin is published", $readme);
        $this->assertStringContainsString('https://github.com/gasatrya/hoursflow/issues', $readme);
        $this->assertStringContainsString('https://github.com/gasatrya/hoursflow', $readme);
        $this->assertStringContainsString('src/blocks/cta/index.js', $readme);
        $this->assertStringContainsString('build/blocks/cta/index.js', $readme);
        $this->assertStringContainsString('pnpm install --frozen-lockfile --ignore-scripts', $readme);
        $this->assertStringContainsString('npm run build:check', $readme);
        $this->assertStringContainsString('npm run package:check', $readme);
        $this->assertDoesNotMatchRegularExpression('/\\]\\((?!https?:|#)/', $readme);
        $this->assertSame(1, preg_match('/Donate link:[^\\n]+\\n\\n([^\\n]+)/', $readme, $short_description));
        $this->assertLessThanOrEqual(150, strlen(trim($short_description[1])));

        $package_tool = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'package.js');
        $this->assertIsString($package_tool);
        foreach (array(
            'src/blocks/cta/index.js',
            'src/blocks/cta/editor.scss',
            'src/blocks/cta/block.json',
        ) as $source_entry) {
            $this->assertStringContainsString("'" . $source_entry . "'", $package_tool);
        }
        $this->assertStringContainsString('ALLOWED_NON_PHP_SOURCE_ENTRIES', $package_tool);
        $this->assertStringContainsString('Production package contains an unapproved non-PHP source file:', $package_tool);
        $this->assertStringNotContainsString("'src/blocks/',", $package_tool);

        $ci = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.github' . DIRECTORY_SEPARATOR . 'workflows' . DIRECTORY_SEPARATOR . 'ci.yml');
        $this->assertIsString($ci);
        $this->assertStringContainsString("wordpress: '6.6.7'", $ci);
        $this->assertStringContainsString("php: '7.4'", $ci);
        $this->assertStringContainsString("wordpress: '7.1.1'", $ci);
        $this->assertStringContainsString("php: '8.5'", $ci);
        $this->assertStringContainsString('WP_TESTS_DIR: ${{ github.workspace }}/.wordpress-tests-${{ matrix.lane }}', $ci);
        $this->assertStringContainsString('WP_CORE_DIR: ${{ github.workspace }}/.wordpress-${{ matrix.lane }}', $ci);
        $this->assertStringContainsString('needs: [quality, wordpress]', $ci);

        $composer = json_decode(
            (string) file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'composer.json'),
            true
        );
        $this->assertIsArray($composer);
        $this->assertSame('hoursflow/hoursflow-business-hours-cta', $composer['name']);
        $this->assertSame('>=7.4', $composer['require']['php']);
        $this->assertSame('GPL-2.0-or-later', $composer['license']);

        $package = json_decode(
            (string) file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'package.json'),
            true
        );
        $this->assertIsArray($package);
        $this->assertSame('hoursflow-business-hours-cta', $package['name']);
        $this->assertSame('0.1.0', $package['version']);

        $metadata_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . 'blocks' . DIRECTORY_SEPARATOR . 'cta' . DIRECTORY_SEPARATOR . 'block.json';
        $metadata = json_decode((string) file_get_contents($metadata_path), true);
        $this->assertIsArray($metadata);
        $this->assertSame('hoursflow/cta', $metadata['name']);
        $this->assertSame('hoursflow', $metadata['textdomain']);
    }

    /**
     * @return array<int, string>
     */
    private function runtimePhpFiles(): array
    {
        $files = array(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'hoursflow.php',
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uninstall.php',
        );
        $source_directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source_directory));
        foreach ($iterator as $file) {
            if (!$file->isFile() || 'php' !== strtolower($file->getExtension())) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);
        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function runtimeFiles(): array
    {
        $files = $this->runtimePhpFiles();
        $files[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'settings.js';
        $files[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'assets'
            . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'cta.css';
        $files[] = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . 'blocks' . DIRECTORY_SEPARATOR . 'cta' . DIRECTORY_SEPARATOR . 'index.js';

        return $files;
    }

    /**
     * @param string $contents
     * @return array<int, string>
     */
    private function classNames($contents): array
    {
        $names = array();
        $tokens = token_get_all($contents);
        $token_count = count($tokens);

        for ($index = 0; $index < $token_count; $index++) {
            if (!is_array($tokens[$index]) || T_CLASS !== $tokens[$index][0]) {
                continue;
            }

            for ($next = $index + 1; $next < $token_count; $next++) {
                if (is_array($tokens[$next]) && T_WHITESPACE === $tokens[$next][0]) {
                    continue;
                }
                if (is_array($tokens[$next]) && T_STRING === $tokens[$next][0]) {
                    $names[] = $tokens[$next][1];
                }
                break;
            }
        }

        return $names;
    }
}
