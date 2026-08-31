<?php

use PHPUnit\Framework\TestCase;

final class CouponSearchQueryBuilderTest extends TestCase
{
    public function testNoFiltersProducesBaseQueryWithNoParams(): void
    {
        $result = CouponSearchQueryBuilder::build('', 'all', 1);

        $this->assertStringNotContainsString('LIKE', $result['query']);
        $this->assertStringNotContainsString('is_active', $result['query']);
        $this->assertSame([], $result['params']);
        $this->assertStringContainsString('ORDER BY created_at DESC', $result['query']);
    }

    public function testSearchAddsLikeClauseOnCode(): void
    {
        $result = CouponSearchQueryBuilder::build('SUMMER', 'all', 1);

        $this->assertStringContainsString('AND code LIKE ?', $result['query']);
        $this->assertSame(['%SUMMER%'], $result['params']);
    }

    public function testActiveFilterAddsClause(): void
    {
        $result = CouponSearchQueryBuilder::build('', 'active', 1);

        $this->assertStringContainsString('AND is_active = 1', $result['query']);
        $this->assertSame([], $result['params']);
    }

    public function testInactiveFilterAddsClause(): void
    {
        $result = CouponSearchQueryBuilder::build('', 'inactive', 1);

        $this->assertStringContainsString('AND is_active = 0', $result['query']);
    }

    public function testUnknownStatusFilterIsIgnored(): void
    {
        $result = CouponSearchQueryBuilder::build('', 'bogus', 1);

        $this->assertStringNotContainsString('is_active', $result['query']);
    }

    public function testSearchTermIsTrimmedBeforeUse(): void
    {
        $result = CouponSearchQueryBuilder::build('  SAVE10  ', 'all', 1);

        $this->assertSame(['%SAVE10%'], $result['params']);
    }

    public function testCombinesSearchAndStatusFilter(): void
    {
        $result = CouponSearchQueryBuilder::build('EID', 'active', 1);

        $this->assertSame(['%EID%'], $result['params']);
        $this->assertStringContainsString('AND code LIKE ?', $result['query']);
        $this->assertStringContainsString('AND is_active = 1', $result['query']);
    }

    public function testPageOneStartsAtOffsetZero(): void
    {
        $result = CouponSearchQueryBuilder::build('', 'all', 1);

        $this->assertStringContainsString('LIMIT 10 OFFSET 0', $result['query']);
        $this->assertSame(1, $result['page']);
    }

    public function testPageThreeOffsetsByTwoPages(): void
    {
        $result = CouponSearchQueryBuilder::build('', 'all', 3);

        $this->assertStringContainsString('LIMIT 10 OFFSET 20', $result['query']);
        $this->assertSame(3, $result['page']);
    }

    public function testPageBelowOneIsClampedToOne(): void
    {
        $result = CouponSearchQueryBuilder::build('', 'all', 0);

        $this->assertStringContainsString('OFFSET 0', $result['query']);
        $this->assertSame(1, $result['page']);
    }

    public function testCountQueryHasNoLimit(): void
    {
        $result = CouponSearchQueryBuilder::build('EID', 'active', 2);

        $this->assertStringStartsWith('SELECT COUNT(*)', $result['countQuery']);
        $this->assertStringNotContainsString('LIMIT', $result['countQuery']);
        $this->assertStringContainsString('AND code LIKE ?', $result['countQuery']);
        $this->assertStringContainsString('AND is_active = 1', $result['countQuery']);
    }

    public function testTotalPagesRoundsUp(): void
    {
        $this->assertSame(1, CouponSearchQueryBuilder::totalPages(0));
        $this->assertSame(1, CouponSearchQueryBuilder::totalPages(10));
        $this->assertSame(2, CouponSearchQueryBuilder::totalPages(11));
        $this->assertSame(3, CouponSearchQueryBuilder::totalPages(21));
    }
}