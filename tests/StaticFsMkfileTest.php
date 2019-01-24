<?php

namespace BlueFilesystemTest;

use PHPUnit\Framework\TestCase;
use BlueFilesystem\StaticObjects\{
    Fs,
    FsInterface
};
use BlueEvent\Event\Base\EventDispatcher;

class StaticFsMkfileTest extends TestCase
{
    public function setUp(): void
    {
        \shell_exec('chmod 0777 -R ' . StaticFsDelTest::TEST_DIR . ' > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'test_file > /dev/null 2>&1');
    }

    public function testCreateFile(): void
    {
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'test_file');

        $out = Fs::mkfile(StaticFsDelTest::TEST_DIR, 'test_file');

        $this->assertTrue($out);
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'test_file');
    }

    public function testCreateFileWithContent(): void
    {
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'test_file');

        $out = Fs::mkfile(StaticFsDelTest::TEST_DIR, 'test_file', 'test content');

        $this->assertTrue($out);
        $this->assertFileExists(StaticFsDelTest::TEST_DIR . 'test_file');
        $this->assertEquals('test content', \file_get_contents(StaticFsDelTest::TEST_DIR . 'test_file'));
    }

    public function testCreateFileWithError(): void
    {
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'test_dir/test_file');

        Fs::mkdir(StaticFsDelTest::TEST_DIR . 'test_dir');
        \shell_exec('chmod 0555 -R ' . StaticFsDelTest::TEST_DIR . ' > /dev/null 2>&1');

        $out = Fs::mkfile(StaticFsDelTest::TEST_DIR . 'test_dir', 'test_file');

        $this->assertFalse($out);
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'test_dir/test_file');
    }

    public function testCreateFileWithIncorrectChars(): void
    {
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'test_file?:/');

        $out = Fs::mkfile(StaticFsDelTest::TEST_DIR, 'test_file?:/');

        $this->assertFalse($out);
        $this->assertFileNotExists(StaticFsDelTest::TEST_DIR . 'test_file?:/');
    }

    public function testMkfileWithEvents(): void
    {
        $exceptionsExecutions = 0;
        $afterExecutions = 0;
        $beforeExecutions = 0;

        $eventDispatcher = new EventDispatcher;
        $eventDispatcher->setEventConfiguration([
            FsInterface::CREATE_FILE_EXCEPTION => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$exceptionsExecutions) {
                        $exceptionsExecutions++;
                    },
                ],
            ],
            FsInterface::CREATE_FILE_BEFORE => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$beforeExecutions) {
                        $beforeExecutions++;
                    },
                ],
            ],
            FsInterface::CREATE_FILE_AFTER => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$afterExecutions) {
                        $afterExecutions++;
                    },
                ],
            ],
        ]);

        Fs::configureEventHandler($eventDispatcher);

        $this->testCreateFile();
        $this->testCreateFileWithError();

        $this->assertEquals(1, $exceptionsExecutions);
        $this->assertEquals(1, $afterExecutions);
        $this->assertEquals(2, $beforeExecutions);
    }

    public function tearDown(): void
    {
        $this->setUp();
    }
}
