<?php

use PHPUnit\Framework\TestCase;

final class EmailServiceTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/safar_email_test_' . uniqid() . '.log';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            unlink($this->logFile);
        }
    }

    public function testSendWithNullConfigWritesToLogAndReturnsTrue(): void
    {
        $service = new EmailService(null, $this->logFile);

        $result = $service->send('alice@example.com', 'Alice', 'Test Subject', 'Test body');

        $this->assertTrue($result);
        $this->assertFileExists($this->logFile);

        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('alice@example.com', $contents);
        $this->assertStringContainsString('Test Subject', $contents);
        $this->assertStringContainsString('DEV MODE', $contents);
    }

    public function testSendWithEmptySmtpHostFallsBackToLog(): void
    {
        $service = new EmailService(['smtp_host' => ''], $this->logFile);

        $result = $service->send('bob@example.com', 'Bob', 'Hello', 'Body text');

        $this->assertTrue($result);
        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('bob@example.com', $contents);
    }

    public function testMultipleSendsAppendToSameLogFile(): void
    {
        $service = new EmailService(null, $this->logFile);

        $service->send('a@x.com', 'A', 'First', 'Body 1');
        $service->send('b@x.com', 'B', 'Second', 'Body 2');

        $contents = file_get_contents($this->logFile);
        $this->assertStringContainsString('a@x.com', $contents);
        $this->assertStringContainsString('b@x.com', $contents);
    }
}