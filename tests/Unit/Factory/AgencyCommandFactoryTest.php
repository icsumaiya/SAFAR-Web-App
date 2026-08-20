<?php

use PHPUnit\Framework\TestCase;

final class AgencyCommandFactoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        // Mocking/stubbing: isolate this test from a real database connection.
        $this->pdo = $this->createMock(PDO::class);
    }

    public function testVerifyActionReturnsApproveAgencyCommand(): void
    {
        $command = AgencyCommandFactory::build('verify', $this->pdo, 5);
        $this->assertInstanceOf(ApproveAgencyCommand::class, $command);
    }

    public function testRejectActionReturnsRejectAgencyCommand(): void
    {
        $command = AgencyCommandFactory::build('reject', $this->pdo, 5);
        $this->assertInstanceOf(RejectAgencyCommand::class, $command);
    }

    public function testUnverifyActionReturnsUnverifyAgencyCommand(): void
    {
        $command = AgencyCommandFactory::build('unverify', $this->pdo, 5);
        $this->assertInstanceOf(UnverifyAgencyCommand::class, $command);
    }

    public function testUnknownActionReturnsNull(): void
    {
        $command = AgencyCommandFactory::build('delete', $this->pdo, 5);
        $this->assertNull($command);
    }

    public function testEmptyActionReturnsNull(): void
    {
        $command = AgencyCommandFactory::build('', $this->pdo, 5);
        $this->assertNull($command);
    }

    public function testAllReturnedCommandsImplementCommandInterface(): void
    {
        foreach (['verify', 'reject', 'unverify'] as $action) {
            $command = AgencyCommandFactory::build($action, $this->pdo, 1);
            $this->assertInstanceOf(Command::class, $command);
        }
    }
}