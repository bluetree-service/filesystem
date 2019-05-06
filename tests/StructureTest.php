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

        $emptyDir = __DIR__ . '/test-dirs/del/3';

        if (!\file_exists($emptyDir)) {
            mkdir($emptyDir);
        }

        $structure = new Structure(__DIR__ . '/test-dirs/del/3', true);
        $list = $structure->returnPaths();

        $this->assertNotEmpty($list);
        $this->assertEmpty($list['dir']);
        $this->assertEmpty($list['file']);

        $list = $structure->getPaths();

        $this->assertNotEmpty($list);
        $this->assertEmpty($list['dir']);
        $this->assertEmpty($list['file']);

        rmdir($emptyDir);
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

    public function testProcessSplObjects(): void
    {
        $list = [];
        $skipThis = 'file2';

        $callback = function (\SplFileInfo $fileInfo, string $path, ...$params) use (&$list) {
            if ($fileInfo->getFilename() === $params[0]) {
                return;
            }

            $list[$path] = [
                $fileInfo->isDir(),
                $fileInfo->getFilename(),
            ];
        };

        $structure = new Structure(__DIR__ . '/test-dirs/del', true);
        $structure->processSplObjects($callback, true, $skipThis);

        $valid = [
            __DIR__ . '/test-dirs/del/file' => [
                false,
                'file',
            ],
            //skipped by callback
//            __DIR__ . '/test-dirs/del/1/1-1/1-1-1/file2' => [
//                false,
//                'file2',
//            ],
            __DIR__ . '/test-dirs/del/1/1-1/1-1-1/file' => [
                false,
                'file',
            ],
            __DIR__ . '/test-dirs/del/1/1-1/1-1-1' => [
                true,
                '1-1-1',
            ],
            __DIR__ . '/test-dirs/del/1/1-1' => [
                true,
                '1-1',
            ],
            __DIR__ . '/test-dirs/del/1' => [
                true,
                '1',
            ],
            __DIR__ . '/test-dirs/del/2/2-1/file' => [
                false,
                'file',
            ],
            __DIR__ . '/test-dirs/del/2/2-1/2-1-1/file' => [
                false,
                'file',
            ],
            __DIR__ . '/test-dirs/del/2/2-1/2-1-1' => [
                true,
                '2-1-1',
            ],
            __DIR__ . '/test-dirs/del/2/2-1/2-1-2/file' => [
                false,
                'file',
            ],
            __DIR__ . '/test-dirs/del/2/2-1/2-1-2' => [
                true,
                '2-1-2',
            ],
            __DIR__ . '/test-dirs/del/2/2-1' => [
                true,
                '2-1',
            ],
            __DIR__ . '/test-dirs/del/2' => [
                true,
                '2',
            ],
        ];

        $this->assertEquals($valid, $list);
    }
}
