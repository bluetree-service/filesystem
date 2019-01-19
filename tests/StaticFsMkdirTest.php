<?php

namespace BlueFilesystemTest;

use PHPUnit\Framework\TestCase;
use BlueFilesystem\StaticObjects\{
    Fs,
    FsInterface
};
use BlueEvent\Event\Base\EventDispatcher;

class StaticFsMkdirTest extends TestCase
{
    public function setUp(): void
    {
        \shell_exec('chmod 0777 -R ' . StaticFsDelTest::TEST_DIR . ' > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'new_dir > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'new_dir2 > /dev/null 2>&1');
    }

    public function testDirectoryRecursive(): void
    {
        $out1 = Fs::mkdir(__DIR__ . '/playground/new_dir/subdir1/subdir2/');

        $this->assertNotEmpty($out1);

        $this->assertEquals(
            [
                __DIR__ . '/playground/new_dir/' => true,
                __DIR__ . '/playground/new_dir/subdir1/' => true,
                __DIR__ . '/playground/new_dir/subdir1/subdir2/' => true,
            ],
            $out1
        );
    }

    public function testCreateDirectory(): void
    {
        $out1 = Fs::mkdir(__DIR__ . '/playground/new_dir');
        $out2 = Fs::mkdir(__DIR__ . '/playground/new_dir2/');

        $this->assertNotEmpty($out1);
        $this->assertNotEmpty($out2);

        $this->assertEquals([__DIR__ . '/playground/new_dir/' => true], $out1);
        $this->assertEquals([__DIR__ . '/playground/new_dir2/' => true], $out2);
    }

    public function testCreateDirWithError(): void
    {
        \shell_exec('chmod -w -R ' . StaticFsDelTest::TEST_DIR . ' > /dev/null 2>&1');

        $out1 = Fs::mkdir(__DIR__ . '/playground/new_dir/subdir1/subdir2/');

        $this->assertNotEmpty($out1);
        $this->assertEquals([__DIR__ . '/playground/new_dir/' => 'mkdir(): Permission denied'], $out1);
    }

    public function testCreateDirWithIncorrectChars(): void
    {
        $out1 = Fs::mkdir(__DIR__ . '/playground/new_dir:?');

        $this->assertEmpty($out1);
    }

    public function testMkdirWithEvents(): void
    {
        $exceptionsExecutions = 0;
        $afterExecutions = 0;
        $beforeExecutions = 0;

        $eventDispatcher = new EventDispatcher;
        $eventDispatcher->setEventConfiguration([
            FsInterface::CREATE_PATH_EXCEPTION => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$exceptionsExecutions) {
                        $exceptionsExecutions++;
                    },
                ],
            ],
            FsInterface::CREATE_PATH_AFTER => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$afterExecutions) {
                        $afterExecutions++;
                    },
                ],
            ],
            FsInterface::CREATE_PATH_BEFORE => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$beforeExecutions) {
                        $beforeExecutions++;
                    },
                ],
            ],
        ]);

        Fs::configureEventHandler($eventDispatcher);

        $this->testCreateDirectory();
        $this->tearDown();
        $this->testCreateDirWithError();

        $this->assertEquals(1, $exceptionsExecutions);
        $this->assertEquals(2, $afterExecutions);
        $this->assertEquals(3, $beforeExecutions);
    }

    public function tearDown(): void
    {
        $this->setUp();
    }
}
