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
        $this->assertNotSame('', $metadata['description']);
        $this->assertNotEmpty($metadata['keywords']);
        foreach ($metadata['keywords'] as $keyword) {
            $this->assertIsString($keyword);
            $this->assertNotSame('', $keyword);
        }
        $this->assertSame('widgets', $metadata['category']);
        $this->assertSame('megaphone', $metadata['icon']);
        $this->assertSame('file:./index.js', $metadata['editorScript']);
        $this->assertArrayNotHasKey('attributes', $metadata);
        $this->assertArrayNotHasKey('script', $metadata);
        $this->assertArrayNotHasKey('style', $metadata);
        $this->assertArrayNotHasKey('viewScript', $metadata);
        $this->assertArrayNotHasKey('viewStyle', $metadata);
        $this->assertArrayNotHasKey('editorStyle', $metadata);
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
        $this->assertFileExists($built_directory . DIRECTORY_SEPARATOR . 'index.asset.php');

        $asset = require $built_directory . DIRECTORY_SEPARATOR . 'index.asset.php';
        $this->assertIsArray($asset);
        foreach (array('wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n') as $dependency) {
            $this->assertContains($dependency, $asset['dependencies']);
        }
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
