<?php

namespace BlueFilesystemTest;

use PHPUnit\Framework\TestCase;
use BlueFilesystem\StaticObjects\{
    Fs,
    FsInterface
};
use BlueEvent\Event\Base\EventDispatcher;

class StaticFsRenameTest extends TestCase
{
    public function setUp(): void
    {
        \shell_exec('    chmod -R 0777 ' . StaticFsDelTest::TEST_DIR . ' > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'test > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'file > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'file2 > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . '1 > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . '2 > /dev/null 2>&1');
    }

    public function testRenameFile(): void
    {
        $data = Fs::copy(__DIR__ . '/test-dirs/del/file', StaticFsDelTest::TEST_DIR . 'file');
        $this->assertTrue(Fs::validateComplexOutput($data));

        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'file2');

        $out = Fs::rename(StaticFsDelTest::TEST_DIR . 'file', StaticFsDelTest::TEST_DIR . 'file2');

        $this->assertTrue($out);
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'file2');
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'file');
    }

    public function testRenameDir(): void
    {
        $data = Fs::copy(__DIR__ . '/test-dirs/del/1', StaticFsDelTest::TEST_DIR . '1');
        $this->assertTrue(Fs::validateComplexOutput($data));

        $this->assertDirectoryNotExists(StaticFsDelTest::TEST_DIR . '2');

        $out = Fs::rename(StaticFsDelTest::TEST_DIR . '1', StaticFsDelTest::TEST_DIR . '2');

        $this->assertTrue($out);
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . '2');
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . '1');
    }

    public function testRenameWithException(): void
    {
        $data = Fs::mkdir(StaticFsDelTest::TEST_DIR . 'test');
        $this->assertTrue(Fs::validateComplexOutput($data));
        $data = Fs::copy(__DIR__ . '/test-dirs/del/file', StaticFsDelTest::TEST_DIR . 'test/file');
        $this->assertTrue(Fs::validateComplexOutput($data));

        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'test/file2');

        \shell_exec('chmod 0555 ' . StaticFsDelTest::TEST_DIR . 'test > /dev/null 2>&1');

        $out = Fs::rename(StaticFsDelTest::TEST_DIR . 'test/file', StaticFsDelTest::TEST_DIR . 'test/file2');

        $this->assertFalse($out);
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'test/file');
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'test/file2');
    }

    public function testRenameWithEvents(): void
    {
        $exceptionsExecutions = 0;
        $afterExecutions = 0;
        $beforeExecutions = 0;

        $eventDispatcher = new EventDispatcher;
        $eventDispatcher->setEventConfiguration([
            FsInterface::RENAME_FILE_OR_DIR_EXCEPTION => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$exceptionsExecutions) {
                        $exceptionsExecutions++;
                    },
                ],
            ],
            FsInterface::RENAME_FILE_OR_DIR_AFTER => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$afterExecutions) {
                        $afterExecutions++;
                    },
                ],
            ],
            FsInterface::RENAME_FILE_OR_DIR_BEFORE => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$beforeExecutions) {
                        $beforeExecutions++;
                    },
                ],
            ],
        ]);

        Fs::configureEventHandler($eventDispatcher);

        $this->testRenameFile();
        $this->tearDown();
        $this->testRenameWithException();

        $this->assertEquals(1, $exceptionsExecutions);
        $this->assertEquals(1, $afterExecutions);
        $this->assertEquals(2, $beforeExecutions);
    }

    public function tearDown(): void
    {
        $this->setUp();
    }
}
