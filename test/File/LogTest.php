<?php

namespace Atom\Test\File;

use Atom\Test\TestCase;
use Atom\File\Log;

class LogTest extends TestCase
{
    private static $tmpDir;
    private $logFile;

    public static function setUpBeforeClass(): void
    {
        // LOG_PATH is a constant — can only be defined once per process
        self::$tmpDir = sys_get_temp_dir() . '/atom_log_test/';
        if (!defined('LOG_PATH')) {
            define('LOG_PATH', self::$tmpDir);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->logFile = 'test.log';

        putenv('DEV_LOG=true');
        putenv('DEV_LOG_FILE=' . $this->logFile);

        // Ensure directory exists before Log tries mkdir
        if (!is_dir(self::$tmpDir)) {
            mkdir(self::$tmpDir, 0777, true);
        }

        // Clean log file for fresh test
        $file = self::$tmpDir . $this->logFile;
        if (file_exists($file)) {
            unlink($file);
        }
    }

    protected function tearDown(): void
    {
        putenv('DEV_LOG');
        putenv('DEV_LOG_FILE');
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up
        $dir = self::$tmpDir;
        if (is_dir($dir)) {
            array_map('unlink', glob($dir . '*'));
            rmdir($dir);
        }
    }

    // --- isUse() ---

    public function testIsUseReturnsTrueWhenEnabled()
    {
        putenv('DEV_LOG=true');
        $this->assertEquals('true', Log::isUse());
    }

    // --- logFile() ---

    public function testLogFileReturnsPath()
    {
        $result = Log::logFile();
        $this->assertStringContainsString($this->logFile, $result);
    }

    // --- error() ---

    public function testErrorWritesToFile()
    {
        Log::error('Test error message');
        $content = file_get_contents(self::$tmpDir . $this->logFile);

        $this->assertStringContainsString('ERROR', $content);
        $this->assertStringContainsString('Test error message', $content);
    }

    // --- info() ---

    public function testInfoWritesToFile()
    {
        Log::info('Test info message');
        $content = file_get_contents(self::$tmpDir . $this->logFile);

        $this->assertStringContainsString('INFO', $content);
        $this->assertStringContainsString('Test info message', $content);
    }

    // --- debug() ---

    public function testDebugWritesToFile()
    {
        Log::debug('Test debug message');
        $content = file_get_contents(self::$tmpDir . $this->logFile);

        $this->assertStringContainsString('DEBUG', $content);
        $this->assertStringContainsString('Test debug message', $content);
    }

    // --- Log format ---

    public function testLogContainsTimestamp()
    {
        Log::info('timestamp test');
        $content = file_get_contents(self::$tmpDir . $this->logFile);

        $this->assertMatchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $content);
    }

    // --- Multiple log entries ---

    public function testMultipleLogEntries()
    {
        Log::info('first');
        Log::error('second');
        Log::debug('third');

        $content = file_get_contents(self::$tmpDir . $this->logFile);

        $this->assertStringContainsString('first', $content);
        $this->assertStringContainsString('second', $content);
        $this->assertStringContainsString('third', $content);
    }
}
