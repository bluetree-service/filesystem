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

        $structure = new Structure(__DIR__ . '/test-dirs/del');

        $dir = $structure->getReadDirectory();
        $this->assertNotEmpty($dir);
        $this->assertArrayHasKey($key1, $dir);
        $this->assertInstanceOf(\SplFileInfo::class, $dir[$key1]);

        $dir = $structure->readDirectory(__DIR__ . '/test-dirs/del', true);
        $this->assertNotEmpty($dir);
        $this->assertArrayHasKey($key3, $dir[$key2]);
        $this->assertInstanceOf(\SplFileInfo::class, $dir[$key2][$key3][$key3 . '/file']);
    }

    /**
     * @todo add empty dir to test
     */
    public function testReadStructure(): void
    {
        $structure = new Structure(__DIR__ . '/test-dirs/del', true);
        $list = $structure->returnPaths();
        
        $this->assertNotEmpty($list);
        $this->checkEntries($list);

        $structure->returnPaths(true);
        $list = $structure->getPaths();

        $this->assertNotEmpty($list);
        $this->checkEntries($list);
    }

    /**
     * @param array $list
     */
    protected function checkEntries(array $list): void
    {
        $dirs = [
            __DIR__ . '/test-dirs/del/1/1-1/1-1-1',
            __DIR__ . '/test-dirs/del/1/1-1',
            __DIR__ . '/test-dirs/del/1',
            __DIR__ . '/test-dirs/del/2/2-1/2-1-1',
            __DIR__ . '/test-dirs/del/2/2-1/2-1-2',
            __DIR__ . '/test-dirs/del/2/2-1',
            __DIR__ . '/test-dirs/del/2',
        ];
        $files = [
            __DIR__ . '/test-dirs/del/1/1-1/1-1-1/file',
            __DIR__ . '/test-dirs/del/1/1-1/1-1-1/file2',
            __DIR__ . '/test-dirs/del/file',
            __DIR__ . '/test-dirs/del/2/2-1/2-1-1/file',
            __DIR__ . '/test-dirs/del/2/2-1/2-1-2/file',
            __DIR__ . '/test-dirs/del/2/2-1/file',
        ];

        foreach ($dirs as $dir) {
            $this->assertContains($dir, $list['dir']);
        }

        foreach ($files as $file) {
            $this->assertContains($file, $list['file']);
        }
    }

    public function testReadDirectoryForNotExisting(): void
    {
        $structure = new Structure(__DIR__ . '/playground-fake');
        $this->assertEmpty($structure->getReadDirectory());

        $list = $structure->getPaths();
        $this->assertEmpty($list['dir']);
        $this->assertEmpty($list['file']);
    }
}
