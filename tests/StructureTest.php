<?php

namespace BlueFilesystemTest;

use PHPUnit\Framework\TestCase;
use BlueFilesystem\StaticObjects\Structure;

class StructureTest extends TestCase
{
    public function testFileExists(): void
    {
        if (!is_dir(StaticFsDelTest::TEST_DIR)) {
            mkdir(StaticFsDelTest::TEST_DIR, 0777, true);
        }

        $this->assertTrue(Structure::exist(StaticFsDelTest::TEST_EXAMPLES));
        $this->assertTrue(Structure::exist(StaticFsDelTest::TEST_EXAMPLES . '/del/file'));
        $this->assertFalse(Structure::exist(StaticFsDelTest::BASE_DIR . '/playground-fake'));
    }

    public function testReadDirectory(): void
    {
        $key1 = StaticFsDelTest::TEST_EXAMPLES . '/del/file';
        $key2 = StaticFsDelTest::TEST_EXAMPLES . '/del/2';
        $key3 = StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1';

        $structure = new Structure(StaticFsDelTest::TEST_EXAMPLES . '/del');

        $dir = $structure->getReadDirectory();
        $this->assertNotEmpty($dir);
        $this->assertArrayHasKey($key1, $dir);
        $this->assertInstanceOf(\SplFileInfo::class, $dir[$key1]);

        $dir = $structure->readDirectory(StaticFsDelTest::TEST_EXAMPLES . '/del', true);
        $this->assertNotEmpty($dir);
        $this->assertArrayHasKey($key3, $dir[$key2]);
        $this->assertInstanceOf(\SplFileInfo::class, $dir[$key2][$key3][$key3 . '/file']);
    }

    public function testReadStructure(): void
    {
        $structure = new Structure(StaticFsDelTest::TEST_EXAMPLES . '/del', true);
        $list = $structure->returnPaths();

        $this->assertNotEmpty($list);
        $this->checkEntries($list);

        $structure->returnPaths(true);
        $list = $structure->getPaths();

        $this->assertNotEmpty($list);
        $this->checkEntries($list);

        $emptyDir = StaticFsDelTest::TEST_EXAMPLES . '/del/3';

        if (!\file_exists($emptyDir)) {
            mkdir($emptyDir);
        }

        $structure = new Structure(StaticFsDelTest::TEST_EXAMPLES . '/del/3', true);
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
            StaticFsDelTest::TEST_EXAMPLES . '/del/1/1-1/1-1-1',
            StaticFsDelTest::TEST_EXAMPLES . '/del/1/1-1',
            StaticFsDelTest::TEST_EXAMPLES . '/del/1',
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1/2-1-1',
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1/2-1-2',
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1',
            StaticFsDelTest::TEST_EXAMPLES . '/del/2',
        ];
        $files = [
            StaticFsDelTest::TEST_EXAMPLES . '/del/1/1-1/1-1-1/file',
            StaticFsDelTest::TEST_EXAMPLES . '/del/1/1-1/1-1-1/file2',
            StaticFsDelTest::TEST_EXAMPLES . '/del/file',
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1/2-1-1/file',
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1/2-1-2/file',
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1/file',
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
        $structure = new Structure(StaticFsDelTest::BASE_DIR . '/playground-fake');
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

        $structure = new Structure(StaticFsDelTest::TEST_EXAMPLES . '/del', true);
        $structure->processSplObjects($callback, true, $skipThis);

        $valid = [
            StaticFsDelTest::TEST_EXAMPLES . '/del/file' => [
                false,
                'file',
            ],
            //skipped by callback
//            __DIR__ . '/del/1/1-1/1-1-1/file2' => [
//                false,
//                'file2',
//            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/1/1-1/1-1-1/file' => [
                false,
                'file',
            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/1/1-1/1-1-1' => [
                true,
                '1-1-1',
            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/1/1-1' => [
                true,
                '1-1',
            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/1' => [
                true,
                '1',
            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1/file' => [
                false,
                'file',
            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1/2-1-1/file' => [
                false,
                'file',
            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1/2-1-1' => [
                true,
                '2-1-1',
            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1/2-1-2/file' => [
                false,
                'file',
            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1/2-1-2' => [
                true,
                '2-1-2',
            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/2/2-1' => [
                true,
                '2-1',
            ],
            StaticFsDelTest::TEST_EXAMPLES . '/del/2' => [
                true,
                '2',
            ],
        ];

        $this->assertEquals($valid, $list);
    }
}
