<?php
namespace OpenNow\Tests;

use PHPUnit\Framework\TestCase;

final class BlockMetadataTest extends TestCase
{
    public function testCtaMetadataIsDynamicDiscoverableAndEditorOnly(): void
    {
        $source = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . 'blocks' . DIRECTORY_SEPARATOR . 'cta' . DIRECTORY_SEPARATOR . 'block.json';
        $metadata = $this->readMetadata($source);

        $this->assertSame('opennow/cta', $metadata['name']);
        $this->assertSame(3, $metadata['apiVersion']);
        $this->assertSame('opennow', $metadata['textdomain']);
        $this->assertNotSame('', $metadata['title']);
        $this->assertSame(
            'Show the right call to action based on the current business state, with optional per-state content and status visibility.',
            $metadata['description']
        );
        $this->assertNotEmpty($metadata['keywords']);
        foreach ($metadata['keywords'] as $keyword) {
            $this->assertIsString($keyword);
            $this->assertNotSame('', $keyword);
        }
        $this->assertSame('widgets', $metadata['category']);
        $this->assertSame('megaphone', $metadata['icon']);
        $this->assertSame('file:./index.js', $metadata['editorScript']);
        $this->assertSame('file:./index.css', $metadata['editorStyle']);
        $this->assertSame(
            array(
                'overrides' => array(
                    'type' => 'object',
                ),
            ),
            $metadata['attributes']
        );
        $this->assertArrayNotHasKey('script', $metadata);
        $this->assertArrayNotHasKey('style', $metadata);
        $this->assertArrayNotHasKey('viewScript', $metadata);
        $this->assertArrayNotHasKey('viewStyle', $metadata);
        $this->assertSame(
            array(
                'html' => false,
                'customClassName' => false,
            ),
            $metadata['supports']
        );

        $built_directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'build'
            . DIRECTORY_SEPARATOR . 'blocks' . DIRECTORY_SEPARATOR . 'cta';
        $built_metadata = $this->readMetadata($built_directory . DIRECTORY_SEPARATOR . 'block.json');
        $this->assertSame($metadata, $built_metadata);
        $this->assertFileExists($built_directory . DIRECTORY_SEPARATOR . 'index.js');
        $this->assertFileExists($built_directory . DIRECTORY_SEPARATOR . 'index.css');
        $this->assertFileExists($built_directory . DIRECTORY_SEPARATOR . 'index-rtl.css');
        $this->assertFileExists($built_directory . DIRECTORY_SEPARATOR . 'index.asset.php');

        $editor_css = file_get_contents($built_directory . DIRECTORY_SEPARATOR . 'index.css');
        $this->assertIsString($editor_css);
        $this->assertStringContainsString('.opennow-cta__link', $editor_css);
        $this->assertStringContainsString('min-height:44px', $editor_css);
        $this->assertStringContainsString(
            '.wp-block-opennow-cta .opennow-cta__link{pointer-events:none}',
            $editor_css
        );

        $asset = require $built_directory . DIRECTORY_SEPARATOR . 'index.asset.php';
        $this->assertIsArray($asset);
        $this->assertSame(
            array(
                'react-jsx-runtime',
                'wp-block-editor',
                'wp-blocks',
                'wp-components',
                'wp-i18n',
                'wp-server-side-render',
            ),
            $asset['dependencies']
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readMetadata(string $path): array
    {
        $contents = file_get_contents($path);
        $this->assertIsString($contents);
        $metadata = json_decode($contents, true);
        $this->assertIsArray($metadata);

        return $metadata;
    }
}
