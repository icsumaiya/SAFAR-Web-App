<?php

use PHPUnit\Framework\TestCase;

final class CommissionApiHandlerTest extends TestCase
{
    public function testHandlePostReturns422ForInvalidPercentage(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CommissionService::class);
        $service->expects($this->never())->method('updatePercentage');

        $handler = new CommissionApiHandler($pdo, $service);

        $result = $handler->handlePost(['commission_percentage' => 'not-a-number']);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
    }

    public function testHandlePostReturns422WhenOutOfRange(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CommissionService::class);

        $handler = new CommissionApiHandler($pdo, $service);

        $result = $handler->handlePost(['commission_percentage' => 150]);

        $this->assertSame(422, $result['status']);
    }

    public function testHandlePostUpdatesPercentageAndReturns200(): void
    {
        $pdo = $this->createMock(PDO::class);
        $service = $this->createMock(CommissionService::class);
        $service->expects($this->once())->method('updatePercentage')->with(12.5);

        $handler = new CommissionApiHandler($pdo, $service);

        $result = $handler->handlePost(['commission_percentage' => '12.5']);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame('Commission percentage updated.', $result['body']['message']);
    }

    public function testHandleGetSyncsListsAndAttachesStats(): void
    {
        $rows = [['id' => 1, 'gross_amount' => '100.00']];

        $listStmt = $this->createStub(PDOStatement::class);
        $listStmt->method('execute')->willReturn(true);
        $listStmt->method('fetchAll')->willReturn($rows);

        $countStmt = $this->createStub(PDOStatement::class);
        $countStmt->method('execute')->willReturn(true);
        $countStmt->method('fetchColumn')->willReturn(3);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($listStmt, $countStmt);

        $summary = ['total_sales' => 500.0, 'total_commission' => 50.0, 'total_agency_earnings' => 450.0];
        $byAgency = [['company_name' => 'Sylhet Tours', 'gross' => 300.0, 'commission' => 30.0, 'earning' => 270.0]];

        $service = $this->createMock(CommissionService::class);
        $service->expects($this->once())->method('syncCommissions')->willReturn(2);
        $service->expects($this->once())->method('getSummary')->willReturn($summary);
        $service->expects($this->once())->method('getByAgency')->willReturn($byAgency);
        $service->expects($this->once())->method('getPercentage')->willReturn(10.0);

        $handler = new CommissionApiHandler($pdo, $service);

        $result = $handler->handleGet(['search' => '', 'agency_id' => 0, 'page' => 1]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame(2, $result['body']['synced']);
        $this->assertSame($rows, $result['body']['data']);
        $this->assertSame(3, $result['body']['pagination']['total']);
        $this->assertSame($summary, $result['body']['summary']);
        $this->assertSame($byAgency, $result['body']['by_agency']);
        $this->assertSame(10.0, $result['body']['commission_percentage']);
    }
}