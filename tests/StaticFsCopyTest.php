<?php

namespace BlueFilesystemTest;

use PHPUnit\Framework\TestCase;
use BlueFilesystem\StaticObjects\{
    Fs,
    FsInterface
};
use BlueEvent\Event\Base\EventDispatcher;

class StaticFsCopyTest extends TestCase
{
    public function setUp(): void
    {
        if (!is_dir(StaticFsDelTest::TEST_DIR)) {
            mkdir(StaticFsDelTest::TEST_DIR, 0777, true);
        }

        \shell_exec('    chmod -R 0777 ' . StaticFsDelTest::TEST_DIR . ' > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'copy > /dev/null 2>&1');
    }

    public function testCopyDirSuccess(): void
    {
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/2/2-1/2-1-1/file');
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/1/1-1/1-1-1/file2');
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/1/1-1/1-1-1');

        $out = Fs::copy(StaticFsDelTest::TEST_EXAMPLES . '/del', StaticFsDelTest::TEST_DIR . 'copy');

        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'copy/2/2-1/2-1-1/file');
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'copy/1/1-1/1-1-1/file2');
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'copy/1/1-1/1-1-1');

        $dir1 = StaticFsDelTest::TEST_DIR . 'copy';
        $dir2 = StaticFsDelTest::TEST_EXAMPLES . '/del';

        $this->assertTrue(Fs::validateComplexOutput($out));
        $this->assertEquals(
            [
                'mkdir:' . $dir1  => true,
                'mkdir:' . $dir1 . '/2' => true,
                'mkdir:' . $dir1 . '/2/2-1' => true,
                'mkdir:' . $dir1 . '/2/2-1/2-1-2' => true,
                'mkdir:' . $dir1 . '/2/2-1/2-1-1' => true,
                'mkdir:' . $dir1 . '/1' => true,
                'mkdir:' . $dir1 . '/1/1-1' => true,
                'mkdir:' . $dir1 . '/1/1-1/1-1-1' => true,
                'copy:' . $dir2 . '/1/1-1/1-1-1/file:' . $dir1 . '/1/1-1/1-1-1/file' => true,
                'copy:' . $dir2 . '/1/1-1/1-1-1/file2:' . $dir1 . '/1/1-1/1-1-1/file2' => true,
                'copy:' . $dir2 . '/file:' . $dir1 . '/file' => true,
                'copy:' . $dir2 . '/2/2-1/2-1-1/file:' . $dir1 . '/2/2-1/2-1-1/file' => true,
                'copy:' . $dir2 . '/2/2-1/2-1-2/file:' . $dir1 . '/2/2-1/2-1-2/file' => true,
                'copy:' . $dir2 . '/2/2-1/file:' . $dir1 . '/2/2-1/file' => true,
            ],
            $out
        );
    }

    public function testCopyWithError(): void
    {
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/2/2-1/2-1-1/file');
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/1/1-1/1-1-1/file2');
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/1/1-1/1-1-1');

        \chmod(StaticFsDelTest::TEST_DIR, 0555);

        $out = Fs::copy(StaticFsDelTest::TEST_EXAMPLES . '/del', StaticFsDelTest::TEST_DIR . 'copy');

        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/2/2-1/2-1-1/file');
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/1/1-1/1-1-1/file2');
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/1/1-1/1-1-1');

        $dir1 = StaticFsDelTest::TEST_DIR . 'copy';
        $dir2 = StaticFsDelTest::TEST_EXAMPLES . '/del';

        $this->assertFalse(Fs::validateComplexOutput($out));
        $this->assertEquals(
            [
                'mkdir:' . $dir1  => 'mkdir(): Permission denied',
                'mkdir:' . $dir1 . '/2' => 'mkdir(): No such file or directory',
                'mkdir:' . $dir1 . '/2/2-1' => 'mkdir(): No such file or directory',
                'mkdir:' . $dir1 . '/2/2-1/2-1-2' => 'mkdir(): No such file or directory',
                'mkdir:' . $dir1 . '/2/2-1/2-1-1' => 'mkdir(): No such file or directory',
                'mkdir:' . $dir1 . '/1' => 'mkdir(): No such file or directory',
                'mkdir:' . $dir1 . '/1/1-1' => 'mkdir(): No such file or directory',
                'mkdir:' . $dir1 . '/1/1-1/1-1-1' => 'mkdir(): No such file or directory',
                'copy:' . $dir2 . '/1/1-1/1-1-1/file:' . $dir1 . '/1/1-1/1-1-1/file' => 'copy(' . $dir1 . '/1/1-1/1-1-1/file): Failed to open stream: No such file or directory',
                'copy:' . $dir2 . '/1/1-1/1-1-1/file2:' . $dir1 . '/1/1-1/1-1-1/file2' => 'copy(' . $dir1 . '/1/1-1/1-1-1/file2): Failed to open stream: No such file or directory',
                'copy:' . $dir2 . '/file:' . $dir1 . '/file' => 'copy(' . $dir1 . '/file): Failed to open stream: No such file or directory',
                'copy:' . $dir2 . '/2/2-1/2-1-1/file:' . $dir1 . '/2/2-1/2-1-1/file' => 'copy(' . $dir1 . '/2/2-1/2-1-1/file): Failed to open stream: No such file or directory',
                'copy:' . $dir2 . '/2/2-1/2-1-2/file:' . $dir1 . '/2/2-1/2-1-2/file' => 'copy(' . $dir1 . '/2/2-1/2-1-2/file): Failed to open stream: No such file or directory',
                'copy:' . $dir2 . '/2/2-1/file:' . $dir1 . '/2/2-1/file' => 'copy(' . $dir1 . '/2/2-1/file): Failed to open stream: No such file or directory',
                'copy:' . $dir2 . '/2/2-1/file:' . $dir1 . '/2/2-1/file' => 'copy(' . $dir1 . '/2/2-1/file): Failed to open stream: No such file or directory',
            ],
            $out
        );
    }

    public function testCopyDirWhenTargetExists(): void
    {
        \mkdir(StaticFsDelTest::TEST_DIR . 'copy');

        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/del/2/2-1/2-1-1/file');
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/del/1/1-1/1-1-1/file2');
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy/del/1/1-1/1-1-1');

        $out = Fs::copy(StaticFsDelTest::TEST_EXAMPLES . '/del', StaticFsDelTest::TEST_DIR . 'copy');

        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'copy/del/2/2-1/2-1-1/file');
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'copy/del/1/1-1/1-1-1/file2');
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'copy/del/1/1-1/1-1-1');

        $dir1 = StaticFsDelTest::TEST_DIR . 'copy/del';
        $dir2 = StaticFsDelTest::TEST_EXAMPLES . '/del';

        $this->assertTrue(Fs::validateComplexOutput($out));
        $this->assertEquals(
            [
                'mkdir:' . $dir1  => true,
                'mkdir:' . $dir1 . '/2' => true,
                'mkdir:' . $dir1 . '/2/2-1' => true,
                'mkdir:' . $dir1 . '/2/2-1/2-1-2' => true,
                'mkdir:' . $dir1 . '/2/2-1/2-1-1' => true,
                'mkdir:' . $dir1 . '/1' => true,
                'mkdir:' . $dir1 . '/1/1-1' => true,
                'mkdir:' . $dir1 . '/1/1-1/1-1-1' => true,
                'copy:' . $dir2 . '/1/1-1/1-1-1/file:' . $dir1 . '/1/1-1/1-1-1/file' => true,
                'copy:' . $dir2 . '/1/1-1/1-1-1/file2:' . $dir1 . '/1/1-1/1-1-1/file2' => true,
                'copy:' . $dir2 . '/file:' . $dir1 . '/file' => true,
                'copy:' . $dir2 . '/2/2-1/2-1-1/file:' . $dir1 . '/2/2-1/2-1-1/file' => true,
                'copy:' . $dir2 . '/2/2-1/2-1-2/file:' . $dir1 . '/2/2-1/2-1-2/file' => true,
                'copy:' . $dir2 . '/2/2-1/file:' . $dir1 . '/2/2-1/file' => true,
            ],
            $out
        );
    }

    public function testSingleFile(): void
    {
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy');

        $out = Fs::copy(StaticFsDelTest::TEST_EXAMPLES . '/del/file', StaticFsDelTest::TEST_DIR . 'copy');

        $this->assertTrue(Fs::validateComplexOutput($out));
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'copy');
        $this->assertEquals(
            [
                'copy:' . StaticFsDelTest::TEST_EXAMPLES . '/del/file:' . StaticFsDelTest::TEST_DIR . 'copy' => true
            ],
            $out
        );
    }

    public function testSingleFileWithError(): void
    {
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy');
        \shell_exec('    chmod -R 0555 ' . StaticFsDelTest::TEST_DIR . ' > /dev/null 2>&1');

        $out = Fs::copy(StaticFsDelTest::TEST_EXAMPLES . '/del/file', StaticFsDelTest::TEST_DIR . 'copy');

        $this->assertFalse(Fs::validateComplexOutput($out));
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy');
        $this->assertEquals(
            [
                'copy:' . StaticFsDelTest::TEST_EXAMPLES . '/del/file:' . StaticFsDelTest::TEST_DIR . 'copy'
                    => 'copy(' . StaticFsDelTest::TEST_DIR . 'copy' . '): Failed to open stream: Permission denied'
            ],
            $out
        );
    }

    public function testSingleFileExists(): void
    {
        $this->assertFileDoesNotExist(StaticFsDelTest::TEST_DIR . 'copy');
        \file_put_contents(StaticFsDelTest::TEST_DIR . 'copy', 'test');
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'copy');
        $this->assertEquals('test', \file_get_contents(StaticFsDelTest::TEST_DIR . 'copy'));

        $out = Fs::copy(StaticFsDelTest::TEST_EXAMPLES . '/del/file', StaticFsDelTest::TEST_DIR . 'copy');

        $this->assertTrue(Fs::validateComplexOutput($out));
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'copy');
        $this->assertEquals(
            [
                'copy:' . StaticFsDelTest::TEST_EXAMPLES . '/del/file:' . StaticFsDelTest::TEST_DIR . 'copy' => true
            ],
            $out
        );

        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'copy');
        $this->assertEquals('', \file_get_contents(StaticFsDelTest::TEST_DIR . 'copy'));
    }

    public function testCopyWhenSourceDontExists(): void
    {
        $out = Fs::copy('din-not-exists', StaticFsDelTest::TEST_DIR . 'copy');
        $this->assertEmpty($out);
        $this->assertFalse(Fs::validateComplexOutput($out));
    }

    public function testCopyWithEvents(): void
    {
        $exceptionsExecutions = 0;
        $afterExecutions = 0;
        $beforeExecutions = 0;
        $exceptionsContentExecutions = 0;
        $beforePathsExecutions = 0;

        $eventDispatcher = new EventDispatcher;
        $eventDispatcher->setEventConfiguration([
            FsInterface::COPY_CREATE_PATH_EXCEPTION => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$exceptionsExecutions) {
                        $exceptionsExecutions++;
                    },
                ],
            ],
            FsInterface::COPY_PATH_CONTENT_EXCEPTION => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$exceptionsContentExecutions) {
                        $exceptionsContentExecutions++;
                    },
                ],
            ],
            FsInterface::COPY_PATH_CONTENT_BEFORE => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$beforeExecutions) {
                        $beforeExecutions++;
                    },
                ],
            ],
            FsInterface::COPY_PATH_CONTENT_AFTER => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$afterExecutions) {
                        $afterExecutions++;
                    },
                ],
            ],
            FsInterface::COPY_PATHS_BEFORE => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$beforePathsExecutions) {
                        $beforePathsExecutions++;
                    },
                ],
            ],
        ]);

        Fs::configureEventHandler($eventDispatcher);

        $this->testCopyWithError();

        $this->assertEquals(9, $exceptionsExecutions);
        $this->assertEquals(1, $afterExecutions);
        $this->assertEquals(1, $beforeExecutions);
        $this->assertEquals(6, $exceptionsContentExecutions);
        $this->assertEquals(1, $beforePathsExecutions);
    }

    public function tearDown(): void
    {
        $this->setUp();
    }
}
