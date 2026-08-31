<?php

use PHPUnit\Framework\TestCase;

final class AgencyProfileApiHandlerTest extends TestCase
{
    /**
     * A PDO mock whose prepare() always returns a stub PDOStatement that
     * happily executes and returns empty/zeroed results. Good enough for
     * the "agency not found" and "agency_id missing" branches, and as a
     * base for tests that only care about a couple of specific queries.
     */
    private function pdoStub(): PDO
    {
        $stmt = $this->createStub(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(false);
        $stmt->method('fetchAll')->willReturn([]);
        $stmt->method('fetchColumn')->willReturn(0);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        return $pdo;
    }

    public function testHandlePostReturns422WhenAgencyIdMissing(): void
    {
        $handler = new AgencyProfileApiHandler($this->pdoStub());

        $result = $handler->handlePost([]);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
        $this->assertSame('agency_id is required.', $result['body']['error']);
    }

    public function testHandlePostReturns404WhenAgencyNotFound(): void
    {
        // pdoStub's fetch() returns false -> getAgency() returns null
        $handler = new AgencyProfileApiHandler($this->pdoStub());

        $result = $handler->handlePost(['agency_id' => 99]);

        $this->assertSame(404, $result['status']);
        $this->assertSame('Agency not found.', $result['body']['error']);
    }

    public function testHandlePostReturns422WhenValidatorRejectsInput(): void
    {
        $stmt = $this->createStub(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn(['id' => 5, 'company_name' => 'Acme Tours']);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $handler = new AgencyProfileApiHandler($pdo);

        $result = $handler->handlePost([
            'agency_id' => 5,
            'website' => 'not-a-valid-url',
        ]);

        $this->assertSame(422, $result['status']);
        $this->assertFalse($result['body']['success']);
        $this->assertStringContainsString('valid URL', $result['body']['error']);
    }

    public function testHandlePostUpdatesProfileAndReturns200ForValidInput(): void
    {
        $fetchStmt = $this->createStub(PDOStatement::class);
        $fetchStmt->method('execute')->willReturn(true);
        $fetchStmt->method('fetch')->willReturn(['id' => 5, 'company_name' => 'Acme Tours']);

        $updateStmt = $this->createMock(PDOStatement::class);
        $updateStmt->expects($this->once())->method('execute');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($fetchStmt, $updateStmt);

        $handler = new AgencyProfileApiHandler($pdo);

        $result = $handler->handlePost([
            'agency_id' => 5,
            'description' => 'A great agency.',
            'website' => 'https://example.com',
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame('Profile updated.', $result['body']['message']);
    }

    public function testHandleGetReturns422WhenAgencyIdMissing(): void
    {
        $handler = new AgencyProfileApiHandler($this->pdoStub());

        $result = $handler->handleGet([]);

        $this->assertSame(422, $result['status']);
        $this->assertSame('agency_id is required.', $result['body']['error']);
    }

    public function testHandleGetReturns404WhenAgencyNotFound(): void
    {
        $handler = new AgencyProfileApiHandler($this->pdoStub());

        $result = $handler->handleGet(['agency_id' => 42]);

        $this->assertSame(404, $result['status']);
        $this->assertSame('Agency not found.', $result['body']['error']);
    }

    public function testHandleGetReturnsFullProfileWhenAgencyExists(): void
    {
        $agencyRow = ['id' => 5, 'company_name' => 'Acme Tours'];

        // Same stub statement is reused for every prepare() call the
        // handler makes (agency lookup, booking stats, revenue, review
        // summary), so its fetch() row needs keys covering all of them.
        $fetchRow = $agencyRow + ['avg_rating' => 4.5, 'review_count' => 3];

        $stmt = $this->createStub(PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetch')->willReturn($fetchRow);
        $stmt->method('fetchAll')->willReturn([]);
        $stmt->method('fetchColumn')->willReturn(1500.0);

        $pdo = $this->createStub(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $handler = new AgencyProfileApiHandler($pdo);

        $result = $handler->handleGet(['agency_id' => 5]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame(5, $result['body']['agency']['id']);
        $this->assertSame('Acme Tours', $result['body']['agency']['company_name']);
        $this->assertSame(0, $result['body']['package_count']);
        $this->assertArrayHasKey('booking_stats', $result['body']);
        $this->assertArrayHasKey('rating', $result['body']);
    }
}