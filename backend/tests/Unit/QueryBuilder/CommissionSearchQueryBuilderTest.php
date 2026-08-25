<?php

use PHPUnit\Framework\TestCase;

final class CommissionSearchQueryBuilderTest extends TestCase
{
    public function testNoFiltersProducesBaseQueryWithNoParams(): void
    {
        $result = CommissionSearchQueryBuilder::build('', 0, 1);

        $this->assertStringNotContainsString('LIKE', $result['query']);
        $this->assertStringNotContainsString('AND c.agency_id', $result['query']);
        $this->assertSame([], $result['params']);
    }

    public function testSearchAddsLikeClause(): void
    {
        $result = CommissionSearchQueryBuilder::build('Sylhet Tours', 0, 1);

        $this->assertStringContainsString('AND a.company_name LIKE ?', $result['query']);
        $this->assertSame(['%Sylhet Tours%'], $result['params']);
    }

    public function testAgencyFilterAddsClause(): void
    {
        $result = CommissionSearchQueryBuilder::build('', 6, 1);

        $this->assertStringContainsString('AND c.agency_id = ?', $result['query']);
        $this->assertSame([6], $result['params']);
    }

    public function testCombinesSearchAndAgencyFilter(): void
    {
        $result = CommissionSearchQueryBuilder::build('Tours', 6, 1);

        $this->assertSame(['%Tours%', 6], $result['params']);
    }

    public function testPageThreeOffsetsByTwoPages(): void
    {
        $result = CommissionSearchQueryBuilder::build('', 0, 3);
        $this->assertStringContainsString('LIMIT 10 OFFSET 20', $result['query']);
    }

    public function testCountQueryHasNoLimit(): void
    {
        $result = CommissionSearchQueryBuilder::build('', 0, 1);

        $this->assertStringStartsWith('SELECT COUNT(*)', $result['countQuery']);
        $this->assertStringNotContainsString('LIMIT', $result['countQuery']);
    }

    public function testTotalPagesRoundsUp(): void
    {
        $this->assertSame(1, CommissionSearchQueryBuilder::totalPages(0));
        $this->assertSame(2, CommissionSearchQueryBuilder::totalPages(11));
    }
}