<?php

namespace BlueFilesystemTest;

use PHPUnit\Framework\TestCase;
use BlueFilesystem\StaticObjects\Structure;

class StructureTest extends TestCase
{
    public function testFileExists(): void
    {
        $this->assertTrue(Structure::exist(__DIR__ . '/test-dirs'));
        $this->assertTrue(Structure::exist(__DIR__ . '/test-dirs/del/file'));
        $this->assertFalse(Structure::exist(__DIR__ . '/playground-fake'));
    }

    public function testReadDirectory(): void
    {
        $key1 = __DIR__ . '/test-dirs/del/file';
        $key2 = __DIR__ . '/test-dirs/del/2';
        $key3 = __DIR__ . '/test-dirs/del/2/2-1';

        $dir = Structure::readDirectory(__DIR__ . '/test-dirs/del');
        $this->assertNotEmpty($dir);
        $this->assertArrayHasKey($key1, $dir);
        $this->assertInstanceOf(\SplFileInfo::class, $dir[$key1]);

        $dir = Structure::readDirectory(__DIR__ . '/test-dirs/del', true);
        $this->assertNotEmpty($dir);
        $this->assertArrayHasKey($key3, $dir[$key2]);
        $this->assertInstanceOf(\SplFileInfo::class, $dir[$key2][$key3][$key3 . '/file']);
    }

    /**
     * @todo add empty dir to test
     */
    public function testReadStructure(): void
    {
        $dir = Structure::readDirectory(__DIR__ . '/test-dirs/del', true);
        $list = Structure::returnPaths($dir);
        $this->assertEquals(
            [
                'file' => [
                    __DIR__ . '/test-dirs/del/1/1-1/1-1-1/file',
                    __DIR__ . '/test-dirs/del/1/1-1/1-1-1/file2',
                    __DIR__ . '/test-dirs/del/file',
                    __DIR__ . '/test-dirs/del/2/2-1/2-1-1/file',
                    __DIR__ . '/test-dirs/del/2/2-1/2-1-2/file',
                    __DIR__ . '/test-dirs/del/2/2-1/file',
                ],
                'dir'  => [
                    __DIR__ . '/test-dirs/del/1/1-1/1-1-1',
                    __DIR__ . '/test-dirs/del/1/1-1',
                    __DIR__ . '/test-dirs/del/1',
                    __DIR__ . '/test-dirs/del/2/2-1/2-1-1',
                    __DIR__ . '/test-dirs/del/2/2-1/2-1-2',
                    __DIR__ . '/test-dirs/del/2/2-1',
                    __DIR__ . '/test-dirs/del/2',
                ],
            ],
            $list
        );

        $list = Structure::returnPaths($dir, true);
        $this->assertEquals(
            [
                'file' => [
                    __DIR__ . '/test-dirs/del/2/2-1/2-1-1/file',
                    __DIR__ . '/test-dirs/del/2/2-1/2-1-2/file',
                    __DIR__ . '/test-dirs/del/2/2-1/file',
                    __DIR__ . '/test-dirs/del/file',
                    __DIR__ . '/test-dirs/del/1/1-1/1-1-1/file',
                    __DIR__ . '/test-dirs/del/1/1-1/1-1-1/file2',
                ],
                'dir'  => [
                    __DIR__ . '/test-dirs/del/2/2-1/2-1-1',
                    __DIR__ . '/test-dirs/del/2/2-1/2-1-2',
                    __DIR__ . '/test-dirs/del/2/2-1',
                    __DIR__ . '/test-dirs/del/2',
                    __DIR__ . '/test-dirs/del/1/1-1/1-1-1',
                    __DIR__ . '/test-dirs/del/1/1-1',
                    __DIR__ . '/test-dirs/del/1',
                ],
            ],
            $list
        );
    }

    public function testReadDirectoryForNotExisting(): void
    {
        $dir = Structure::readDirectory(__DIR__ . '/playground-fake');
        $this->assertEmpty($dir);

        $list = Structure::returnPaths($dir);
        $this->assertEmpty($list['dir']);
        $this->assertEmpty($list['file']);
    }
}
