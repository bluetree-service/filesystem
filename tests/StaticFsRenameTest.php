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
        \shell_exec('chmod 0777 -R ' . StaticFsDelTest::TEST_DIR . ' > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'file > /dev/null 2>&1');
        \shell_exec('rm -r ' . StaticFsDelTest::TEST_DIR . 'file2 > /dev/null 2>&1');
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

    public function testRenameDir()
    {
        
    }

    public function tearDown(): void
    {
        $this->setUp();
    }
}
