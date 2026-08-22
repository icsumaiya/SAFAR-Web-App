<?php

use PHPUnit\Framework\TestCase;

final class AgencyCommandsTest extends TestCase
{
    public function testApproveAgencyCommandPreparesAndExecutesCorrectSql(): void
    {
        $agencyId = 42;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([$agencyId]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("status = 'verified'"))
            ->willReturn($stmt);

        $command = new ApproveAgencyCommand($pdo, $agencyId);
        $command->execute();
    }

    public function testRejectAgencyCommandPreparesAndExecutesCorrectSql(): void
    {
        $agencyId = 7;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([$agencyId]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("status = 'rejected'"))
            ->willReturn($stmt);

        $command = new RejectAgencyCommand($pdo, $agencyId);
        $command->execute();
    }

    public function testUnverifyAgencyCommandPreparesAndExecutesCorrectSql(): void
    {
        $agencyId = 15;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([$agencyId]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("status = 'pending'"))
            ->willReturn($stmt);

        $command = new UnverifyAgencyCommand($pdo, $agencyId);
        $command->execute();
    }

    public function testSuspendAgencyCommandPreparesAndExecutesCorrectSql(): void
    {
        $agencyId = 21;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([$agencyId]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("status = 'suspended'"))
            ->willReturn($stmt);

        $command = new SuspendAgencyCommand($pdo, $agencyId);
        $command->execute();
    }

    public function testActivateAgencyCommandPreparesAndExecutesCorrectSql(): void
    {
        $agencyId = 33;

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([$agencyId]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("status = 'verified'"))
            ->willReturn($stmt);

        $command = new ActivateAgencyCommand($pdo, $agencyId);
        $command->execute();
    }

    public function testAllCommandsImplementCommandInterface(): void
    {
        $pdo = $this->createMock(PDO::class);

        $this->assertInstanceOf(Command::class, new ApproveAgencyCommand($pdo, 1));
        $this->assertInstanceOf(Command::class, new RejectAgencyCommand($pdo, 1));
        $this->assertInstanceOf(Command::class, new UnverifyAgencyCommand($pdo, 1));
        $this->assertInstanceOf(Command::class, new SuspendAgencyCommand($pdo, 1));
        $this->assertInstanceOf(Command::class, new ActivateAgencyCommand($pdo, 1));
    }

    public function testExecutePropagatesExceptionWhenPdoThrows(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute')->willThrowException(new PDOException('DB connection lost'));

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $command = new ApproveAgencyCommand($pdo, 1);

        $this->expectException(PDOException::class);
        $command->execute();
    }
}