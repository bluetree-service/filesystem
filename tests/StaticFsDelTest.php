<?php

namespace BlueFilesystemTest;

use PHPUnit\Framework\TestCase;
use BlueFilesystem\StaticObjects\{
    Fs,
    FsInterface
};
use BlueEvent\Event\Base\EventDispatcher;

class StaticFsDelTest extends TestCase
{
    public const TEST_DIR = __DIR__ . '/playground/';

    public function setUp(): void
    {
        shell_exec('chmod 0777 -R ' . self::TEST_DIR . ' > /dev/null 2>&1');
        shell_exec('rm -r ' . self::TEST_DIR . 'del > /dev/null 2>&1');
    }

    public function testDeleteWhenPathDontExists(): void
    {
        $result = Fs::delete(__DIR__ . '/playground-fake');
        $this->assertEmpty($result);
        $this->assertFalse(Fs::validateComplexOutput($result));
    }

    public function testDeleteSuccess(): void
    {
        shell_exec('cp -r ' . __DIR__ . '/test-dirs/del ' . __DIR__ . '/playground');

        $this->assertFileExists(self::TEST_DIR . 'del/2/2-1/2-1-1/file');
        $this->assertFileExists(self::TEST_DIR . 'del/1/1-1/1-1-1/file2');
        $this->assertFileExists(self::TEST_DIR . 'del/1/1-1/1-1-1');

        $result = Fs::delete(__DIR__ . '/playground/del');

        $this->assertEquals(
            [
                self::TEST_DIR . 'del/2/2-1/2-1-1/file' => true,
                self::TEST_DIR . 'del/2/2-1/2-1-2/file' => true,
                self::TEST_DIR . 'del/2/2-1/file' => true,
                self::TEST_DIR . 'del/file' => true,
                self::TEST_DIR . 'del/1/1-1/1-1-1/file' => true,
                self::TEST_DIR . 'del/1/1-1/1-1-1/file2' => true,
                self::TEST_DIR . 'del/2/2-1/2-1-1' => true,
                self::TEST_DIR . 'del/2/2-1/2-1-2' => true,
                self::TEST_DIR . 'del/2/2-1' => true,
                self::TEST_DIR . 'del/2' => true,
                self::TEST_DIR . 'del/1/1-1/1-1-1' => true,
                self::TEST_DIR . 'del/1/1-1' => true,
                self::TEST_DIR . 'del/1' => true,
                self::TEST_DIR . 'del' => true,
            ],
            $result
        );

        $this->assertTrue(Fs::validateComplexOutput($result));
        $this->assertFileNotExists(self::TEST_DIR . 'del/2/2-1/2-1-1/file');
        $this->assertFileNotExists(self::TEST_DIR . 'del/1/1-1/1-1-1/file2');
        $this->assertFileNotExists(self::TEST_DIR . 'del/1/1-1/1-1-1');
    }

    public function testDeleteError(): void
    {
        shell_exec('cp -r ' . __DIR__ . '/test-dirs/del ' . __DIR__ . '/playground');

        $this->assertFileExists(self::TEST_DIR . 'del/2/2-1/2-1-1/file');
        $this->assertFileExists(self::TEST_DIR . 'del/1/1-1/1-1-1/file2');
        $this->assertFileExists(self::TEST_DIR . 'del/1/1-1/1-1-1');

        chmod(self::TEST_DIR . 'del/2/2-1/2-1-1', 0555);
        chmod(self::TEST_DIR . 'del/1/1-1/1-1-1', 0555);

        $result = Fs::delete(__DIR__ . '/playground/del');

        $this->assertEquals(
            [
                self::TEST_DIR . 'del/2/2-1/2-1-1/file' => 'unlink(' . self::TEST_DIR . 'del/2/2-1/2-1-1/file): Permission denied',
                self::TEST_DIR . 'del/2/2-1/2-1-2/file' => true,
                self::TEST_DIR . 'del/2/2-1/file' => true,
                self::TEST_DIR . 'del/file' => true,
                self::TEST_DIR . 'del/1/1-1/1-1-1/file' => 'unlink(' . self::TEST_DIR . 'del/1/1-1/1-1-1/file): Permission denied',
                self::TEST_DIR . 'del/1/1-1/1-1-1/file2' => 'unlink(' . self::TEST_DIR . 'del/1/1-1/1-1-1/file2): Permission denied',
                self::TEST_DIR . 'del/2/2-1/2-1-1' => 'rmdir(' . self::TEST_DIR . 'del/2/2-1/2-1-1): Directory not empty',
                self::TEST_DIR . 'del/2/2-1/2-1-2' => true,
                self::TEST_DIR . 'del/2/2-1' => 'rmdir(' . self::TEST_DIR . 'del/2/2-1): Directory not empty',
                self::TEST_DIR . 'del/2' => 'rmdir(' . self::TEST_DIR . 'del/2): Directory not empty',
                self::TEST_DIR . 'del/1/1-1/1-1-1' => 'rmdir(' .self::TEST_DIR . 'del/1/1-1/1-1-1): Directory not empty',
                self::TEST_DIR . 'del/1/1-1' => 'rmdir(' . self::TEST_DIR . 'del/1/1-1): Directory not empty',
                self::TEST_DIR . 'del/1' => 'rmdir(' . self::TEST_DIR . 'del/1): Directory not empty',
                self::TEST_DIR . 'del' => 'rmdir(' . self::TEST_DIR . 'del): Directory not empty',
            ],
            $result
        );

        $this->assertFalse(Fs::validateComplexOutput($result));
        $this->assertFileExists(self::TEST_DIR . 'del/2/2-1/2-1-1/file');
        $this->assertFileExists(self::TEST_DIR . 'del/1/1-1/1-1-1/file2');
        $this->assertFileExists(self::TEST_DIR . 'del/1/1-1/1-1-1');
        $this->assertFileNotExists(self::TEST_DIR . 'del/2/2-1/file');
        $this->assertFileNotExists(self::TEST_DIR . 'del/file');
    }

    public function testDeleteWithForce(): void
    {
        shell_exec('cp -r ' . __DIR__ . '/test-dirs/del ' . __DIR__ . '/playground');

        $this->assertFileExists(self::TEST_DIR . 'del/2/2-1/2-1-1/file');
        $this->assertFileExists(self::TEST_DIR . 'del/1/1-1/1-1-1/file2');
        $this->assertFileExists(self::TEST_DIR . 'del/1/1-1/1-1-1');

        chmod(self::TEST_DIR . 'del/2/2-1/2-1-1', 0555);
        chmod(self::TEST_DIR . 'del/1/1-1/1-1-1', 0555);

        $result = Fs::delete(__DIR__ . '/playground/del');

        $this->assertEquals(
            [
                self::TEST_DIR . 'del/2/2-1/2-1-1/file' => 'unlink(' . self::TEST_DIR . 'del/2/2-1/2-1-1/file): Permission denied',
                self::TEST_DIR . 'del/2/2-1/2-1-2/file' => true,
                self::TEST_DIR . 'del/2/2-1/file' => true,
                self::TEST_DIR . 'del/file' => true,
                self::TEST_DIR . 'del/1/1-1/1-1-1/file' => 'unlink(' . self::TEST_DIR . 'del/1/1-1/1-1-1/file): Permission denied',
                self::TEST_DIR . 'del/1/1-1/1-1-1/file2' => 'unlink(' . self::TEST_DIR . 'del/1/1-1/1-1-1/file2): Permission denied',
                self::TEST_DIR . 'del/2/2-1/2-1-1' => 'rmdir(' . self::TEST_DIR . 'del/2/2-1/2-1-1): Directory not empty',
                self::TEST_DIR . 'del/2/2-1/2-1-2' => true,
                self::TEST_DIR . 'del/2/2-1' => 'rmdir(' . self::TEST_DIR . 'del/2/2-1): Directory not empty',
                self::TEST_DIR . 'del/2' => 'rmdir(' . self::TEST_DIR . 'del/2): Directory not empty',
                self::TEST_DIR . 'del/1/1-1/1-1-1' => 'rmdir(' .self::TEST_DIR . 'del/1/1-1/1-1-1): Directory not empty',
                self::TEST_DIR . 'del/1/1-1' => 'rmdir(' . self::TEST_DIR . 'del/1/1-1): Directory not empty',
                self::TEST_DIR . 'del/1' => 'rmdir(' . self::TEST_DIR . 'del/1): Directory not empty',
                self::TEST_DIR . 'del' => 'rmdir(' . self::TEST_DIR . 'del): Directory not empty',
            ],
            $result
        );

        $this->assertFalse(Fs::validateComplexOutput($result));
        $this->assertFileExists(self::TEST_DIR . 'del/2/2-1/2-1-1/file');
        $this->assertFileExists(self::TEST_DIR . 'del/1/1-1/1-1-1/file2');
        $this->assertFileExists(self::TEST_DIR . 'del/1/1-1/1-1-1');
        $this->assertFileNotExists(self::TEST_DIR . 'del/2/2-1/file');
        $this->assertFileNotExists(self::TEST_DIR . 'del/file');

        $result = Fs::delete(__DIR__ . '/playground/del', true);

        $this->assertEquals(
            [
                self::TEST_DIR . 'del/1/1-1/1-1-1/file2' => true,
                self::TEST_DIR . 'del/1/1-1/1-1-1/file' => true,
                self::TEST_DIR . 'del/2/2-1/2-1-1/file' => true,
                self::TEST_DIR . 'del/1/1-1/1-1-1' => true,
                self::TEST_DIR . 'del/1/1-1' => true,
                self::TEST_DIR . 'del/1' => true,
                self::TEST_DIR . 'del/2/2-1/2-1-1' => true,
                self::TEST_DIR . 'del/2/2-1' => true,
                self::TEST_DIR . 'del/2' => true,
                self::TEST_DIR . 'del' => true,
            ],
            $result
        );

        $this->assertTrue(Fs::validateComplexOutput($result));
        $this->assertFileNotExists(self::TEST_DIR . 'del/2/2-1/2-1-1/file');
        $this->assertFileNotExists(self::TEST_DIR . 'del/1/1-1/1-1-1/file2');
        $this->assertFileNotExists(self::TEST_DIR . 'del/1/1-1/1-1-1');
    }

    public function testDeleteEvents(): void
    {
        $exceptionsExecutions = 0;
        $afterExecutions = 0;
        $beforeExecutions = 0;
        $contentBeforeExecutions = 0;

        $eventDispatcher = new EventDispatcher;
        $eventDispatcher->setEventConfiguration([
            FsInterface::DELETE_PATH_CONTENT_EXCEPTION => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$exceptionsExecutions) {
                        $exceptionsExecutions++;
                    },
                ],
            ],
            FsInterface::DELETE_PATH_CONTENT_AFTER => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$afterExecutions) {
                        $afterExecutions++;
                    },
                ],
            ],
            FsInterface::DELETE_PATHS_BEFORE => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$beforeExecutions) {
                        $beforeExecutions++;
                    },
                ],
            ],
            FsInterface::DELETE_PATH_CONTENT_BEFORE => [
                'object' => 'BlueEvent\Event\BaseEvent',
                'listeners' => [
                    function () use (&$contentBeforeExecutions) {
                        $contentBeforeExecutions++;
                    },
                ],
            ],
        ]);

        Fs::configureEventHandler($eventDispatcher);

        $this->testDeleteWithForce();

        $this->assertEquals(10, $exceptionsExecutions);
        $this->assertEquals(2, $afterExecutions);
        $this->assertEquals(2, $beforeExecutions);
        $this->assertEquals(2, $contentBeforeExecutions);
    }

    public function tearDown(): void
    {
        Fs::removeEventHandler();
        $this->setUp();
    }
}
