<?php

namespace BlueFilesystemTest;

use PHPUnit\Framework\TestCase;
use BlueFilesystem\StaticObjects\{
    Fs,
    FsInterface
};
use BlueEvent\Event\Base\EventDispatcher;

class StaticFsMoveTest extends TestCase
{
    public function setUp(): void
    {
        \shell_exec('chmod -R 0777 ' . StaticFsDelTest::TEST_DIR . ' > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'test > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'file > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'file2 > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . '1 > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . '2 > /dev/null 2>&1');
    }

    public function testMoveFile(): void
    {
        $data = Fs::copy(__DIR__ . '/test-dirs/del/file', StaticFsDelTest::TEST_DIR . 'file');
        $this->assertTrue(Fs::validateComplexOutput($data));

        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'file2');

        $out = Fs::move(StaticFsDelTest::TEST_DIR . 'file', StaticFsDelTest::TEST_DIR . 'file2');

        $this->assertTrue(Fs::validateComplexOutput($out));
        $this->assertEquals(
            [
                'delete:' . StaticFsDelTest::TEST_DIR . 'file' => true,
                'copy:' . StaticFsDelTest::TEST_DIR . 'file:' . StaticFsDelTest::TEST_DIR . 'file2' => true,
            ],
            $out
        );
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'file2');
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'file');
    }

    public function testMoveDir(): void
    {
        $data = Fs::copy(__DIR__ . '/test-dirs/del/1', StaticFsDelTest::TEST_DIR . '1');
        $this->assertTrue(Fs::validateComplexOutput($data));

        $this->assertDirectoryNotExists(StaticFsDelTest::TEST_DIR . '2');

        $out = Fs::move(StaticFsDelTest::TEST_DIR . '1', StaticFsDelTest::TEST_DIR . '2');

        $this->assertTrue(Fs::validateComplexOutput($out));
        $this->assertEquals(
            [
                'delete:' . StaticFsDelTest::TEST_DIR . '1/1-1/1-1-1/file2' => true,
                'delete:' . StaticFsDelTest::TEST_DIR . '1/1-1/1-1-1/file' => true,
                'delete:' . StaticFsDelTest::TEST_DIR . '1/1-1/1-1-1' => true,
                'delete:' . StaticFsDelTest::TEST_DIR . '1/1-1' => true,
                'delete:' . StaticFsDelTest::TEST_DIR . '1' => true,
                'mkdir:' . StaticFsDelTest::TEST_DIR . '2' => true,
                'mkdir:' . StaticFsDelTest::TEST_DIR . '2/1-1' => true,
                'mkdir:' . StaticFsDelTest::TEST_DIR . '2/1-1/1-1-1' => true,
                'copy:' . StaticFsDelTest::TEST_DIR . '1/1-1/1-1-1/file2:' . StaticFsDelTest::TEST_DIR . '2/1-1/1-1-1/file2' => true,
                'copy:' . StaticFsDelTest::TEST_DIR . '1/1-1/1-1-1/file:' . StaticFsDelTest::TEST_DIR . '2/1-1/1-1-1/file' => true,
            ],
            $out
        );
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . '2');
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . '1');
    }

    public function testMoveWithException(): void
    {
        $data = Fs::mkdir(StaticFsDelTest::TEST_DIR . 'test');
        $this->assertTrue(Fs::validateComplexOutput($data));
        $data = Fs::copy(__DIR__ . '/test-dirs/del/file', StaticFsDelTest::TEST_DIR . 'test/file');
        $this->assertTrue(Fs::validateComplexOutput($data));

        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'test/file2');

        \shell_exec('chmod 0555 ' . StaticFsDelTest::TEST_DIR . 'test > /dev/null 2>&1');

        $out = Fs::move(StaticFsDelTest::TEST_DIR . 'test/file', StaticFsDelTest::TEST_DIR . 'test/file2');

        $this->assertEmpty($out);
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'test/file');
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'test/file2');
    }

    public function testMoveWithEvents(): void
    {
        $exceptionsExecutions = 0;
        $afterExecutions = 0;
        $beforeExecutions = 0;

        $eventDispatcher = new EventDispatcher;
        $eventDispatcher->setEventConfiguration([
            FsInterface::MOVE_FILE_OR_DIR_EXCEPTION => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$exceptionsExecutions) {
                        $exceptionsExecutions++;
                    },
                ],
            ],
            FsInterface::MOVE_FILE_OR_DIR_AFTER => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$afterExecutions) {
                        $afterExecutions++;
                    },
                ],
            ],
            FsInterface::MOVE_FILE_OR_DIR_BEFORE => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$beforeExecutions) {
                        $beforeExecutions++;
                    },
                ],
            ],
        ]);

        Fs::configureEventHandler($eventDispatcher);

        $this->testMoveFile();
        $this->tearDown();
        $this->testMoveWithException();

        $this->assertEquals(1, $exceptionsExecutions);
        $this->assertEquals(1, $afterExecutions);
        $this->assertEquals(2, $beforeExecutions);
    }

    public function tearDown(): void
    {
        $this->setUp();
    }
}
