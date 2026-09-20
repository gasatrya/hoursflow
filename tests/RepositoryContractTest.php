<?php
namespace OpenNow\Tests;

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
            if (!in_array(basename($path), array('opennow.php', 'uninstall.php'), true)) {
                $this->assertStringContainsString('namespace OpenNow', $contents, $path);
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
                    $this->assertStringStartsWith('opennow_', $tag);
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
                    $this->assertStringStartsWith('opennow-', $handle);
                    $this->assertNotContains($handle, $asset_handles);
                    $asset_handles[] = $handle;
                }
            }
        }

        $this->assertSame(array('opennow_cta'), $shortcode_tags);
        $this->assertSame(
            array('opennow-admin-settings', 'opennow-admin-settings-style', 'opennow-cta'),
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
            '/\\b(?:fetch|XMLHttpRequest|sendBeacon|gtag|ga|analytics|telemetry)\\b/i',
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
        $plugin_file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'opennow.php';
        $plugin = file_get_contents($plugin_file);
        $this->assertIsString($plugin);
        $this->assertStringContainsString('* Requires at least: 6.6', $plugin);
        $this->assertStringContainsString('* Requires PHP: 7.4', $plugin);
        $this->assertStringContainsString('* License: GPL-2.0-or-later', $plugin);
        $this->assertStringContainsString('* Text Domain: opennow', $plugin);
        $this->assertMatchesRegularExpression(
            "/define\\s*\\(\\s*['\"]OPENNOW_VERSION['\"]\\s*,\\s*['\"]0\\.1\\.0['\"]\\s*\\)/",
            $plugin
        );

        $readme = file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'readme.txt');
        $this->assertIsString($readme);
        $this->assertStringContainsString("Requires at least: 6.6\n", $readme);
        $this->assertStringContainsString("Requires PHP: 7.4\n", $readme);
        $this->assertStringContainsString("Tested up to: 7.0\n", $readme);
        $this->assertStringContainsString("Stable tag: 0.1.0\n", $readme);

        $composer = json_decode(
            (string) file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'composer.json'),
            true
        );
        $this->assertIsArray($composer);
        $this->assertSame('>=7.4', $composer['require']['php']);
        $this->assertSame('GPL-2.0-or-later', $composer['license']);

        $package = json_decode(
            (string) file_get_contents(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'package.json'),
            true
        );
        $this->assertIsArray($package);
        $this->assertSame('0.1.0', $package['version']);

        $metadata_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . 'blocks' . DIRECTORY_SEPARATOR . 'cta' . DIRECTORY_SEPARATOR . 'block.json';
        $metadata = json_decode((string) file_get_contents($metadata_path), true);
        $this->assertIsArray($metadata);
        $this->assertSame('opennow/cta', $metadata['name']);
        $this->assertSame('opennow', $metadata['textdomain']);
    }

    /**
     * @return array<int, string>
     */
    private function runtimePhpFiles(): array
    {
        $files = array(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'opennow.php',
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
