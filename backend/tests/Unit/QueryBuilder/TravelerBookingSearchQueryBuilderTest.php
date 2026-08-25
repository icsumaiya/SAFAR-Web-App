<?php

use PHPUnit\Framework\TestCase;

final class TravelerBookingSearchQueryBuilderTest extends TestCase
{
    public function testNoSearchProducesBaseQueryWithOnlyUserIdParam(): void
    {
        $result = TravelerBookingSearchQueryBuilder::build(7, '');

        $this->assertStringNotContainsString('LIKE', $result['query']);
        $this->assertSame([7], $result['params']);
        $this->assertStringContainsString('WHERE b.traveler_id = ?', $result['query']);
        $this->assertStringContainsString('ORDER BY b.booking_date DESC', $result['query']);
    }

    public function testSearchAddsLikeClauseWithTwoParams(): void
    {
        $result = TravelerBookingSearchQueryBuilder::build(7, 'beach');

        $this->assertStringContainsString('AND (p.title LIKE ? OR p.location LIKE ?)', $result['query']);
        $this->assertSame([7, '%beach%', '%beach%'], $result['params']);
    }

    public function testUserIdIsAlwaysFirstParam(): void
    {
        $result = TravelerBookingSearchQueryBuilder::build(42, 'cox');

        $this->assertSame(42, $result['params'][0]);
    }

    public function testQueryJoinsExpectedTables(): void
    {
        $result = TravelerBookingSearchQueryBuilder::build(7, '');

        $this->assertStringContainsString('JOIN packages p', $result['query']);
        $this->assertStringContainsString('JOIN agencies a', $result['query']);
    }
}