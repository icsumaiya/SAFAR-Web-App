<?php

use PHPUnit\Framework\TestCase;

final class AgencyApiHandlerTest extends TestCase
{
    public function testHandlePostReturns422WhenAgencyIdMissing(): void
    {
        $pdo = $this->createMock(PDO::class);

        $result = AgencyApiHandler::handlePost($pdo, ['action' => 'verify']);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
        $this->assertSame('agency_id and action are required.', $result['body']['error']);
    }

    public function testHandlePostReturns422WhenActionMissing(): void
    {
        $pdo = $this->createMock(PDO::class);

        $result = AgencyApiHandler::handlePost($pdo, ['agency_id' => 5]);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
    }

    public function testHandlePostReturns422ForUnknownAction(): void
    {
        $pdo = $this->createMock(PDO::class);

        $result = AgencyApiHandler::handlePost($pdo, ['agency_id' => 5, 'action' => 'bogus']);

        $this->assertSame(422, $result['status']);
        $this->assertSame('Unknown action.', $result['body']['error']);
    }

    public function testHandlePostExecutesCommandAndReturns200ForValidAction(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
             ->method('execute')
             ->with([5]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains("status = 'verified'"))
            ->willReturn($stmt);

        $result = AgencyApiHandler::handlePost($pdo, ['agency_id' => 5, 'action' => 'verify']);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame('Agency status updated.', $result['body']['message']);
    }

    public function testHandleGetReturnsAgencyListUsingSearchQueryBuilder(): void
    {
        $rows = [['id' => 1, 'company_name' => 'Sumaiya Travels']];

        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())->method('execute');
        $stmt->expects($this->once())->method('fetchAll')->willReturn($rows);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt);

        $result = AgencyApiHandler::handleGet($pdo, ['search' => 'sumaiya', 'filter_status' => 'all']);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame($rows, $result['body']['data']);
    }

    public function testHandleGetDefaultsFilterStatusToAllWhenMissing(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('execute');
        $stmt->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $result = AgencyApiHandler::handleGet($pdo, []);

        $this->assertSame(200, $result['status']);
        $this->assertSame([], $result['body']['data']);
    }
}