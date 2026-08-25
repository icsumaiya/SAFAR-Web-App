<?php

use PHPUnit\Framework\TestCase;

final class BookingSearchQueryBuilderTest extends TestCase
{
    public function testNoFiltersProducesBaseQueryWithNoParams(): void
    {
        $result = BookingSearchQueryBuilder::build('', 'all', 1);

        $this->assertStringNotContainsString('LIKE', $result['query']);
        $this->assertStringNotContainsString('AND b.status', $result['query']);
        $this->assertSame([], $result['params']);
        $this->assertStringContainsString('ORDER BY b.booking_date DESC', $result['query']);
    }

    public function testSearchAddsLikeClauseWithThreeParams(): void
    {
        $result = BookingSearchQueryBuilder::build('sumaiya', 'all', 1);

        $this->assertStringContainsString(
            'AND (u.name LIKE ? OR p.title LIKE ? OR a.company_name LIKE ?)',
            $result['query']
        );
        $this->assertSame(['%sumaiya%', '%sumaiya%', '%sumaiya%'], $result['params']);
    }

    public function testValidStatusFilterAddsStatusClause(): void
    {
        $result = BookingSearchQueryBuilder::build('', 'approved', 1);

        $this->assertStringContainsString('AND b.status = ?', $result['query']);
        $this->assertSame(['approved'], $result['params']);
    }

    public function testInvalidStatusFilterIsIgnored(): void
    {
        $result = BookingSearchQueryBuilder::build('', 'bogus-status', 1);

        $this->assertStringNotContainsString('AND b.status', $result['query']);
        $this->assertSame([], $result['params']);
    }

    public function testSearchTermIsTrimmedBeforeUse(): void
    {
        $result = BookingSearchQueryBuilder::build('  cox  ', 'all', 1);

        $this->assertSame(['%cox%', '%cox%', '%cox%'], $result['params']);
    }

    public function testCombinesSearchAndStatusFilter(): void
    {
        $result = BookingSearchQueryBuilder::build('tours', 'pending', 1);

        $this->assertSame(['%tours%', '%tours%', '%tours%', 'pending'], $result['params']);
    }

    public function testPageOneStartsAtOffsetZero(): void
    {
        $result = BookingSearchQueryBuilder::build('', 'all', 1);

        $this->assertStringContainsString('LIMIT 10 OFFSET 0', $result['query']);
        $this->assertSame(1, $result['page']);
    }

    public function testPageThreeOffsetsByTwoPages(): void
    {
        $result = BookingSearchQueryBuilder::build('', 'all', 3);

        $this->assertStringContainsString('LIMIT 10 OFFSET 20', $result['query']);
        $this->assertSame(3, $result['page']);
    }

    public function testPageBelowOneIsClampedToOne(): void
    {
        $result = BookingSearchQueryBuilder::build('', 'all', 0);

        $this->assertStringContainsString('OFFSET 0', $result['query']);
        $this->assertSame(1, $result['page']);
    }

    public function testCountQueryHasNoLimitOrSelectColumns(): void
    {
        $result = BookingSearchQueryBuilder::build('dhaka', 'approved', 2);

        $this->assertStringStartsWith('SELECT COUNT(*)', $result['countQuery']);
        $this->assertStringNotContainsString('LIMIT', $result['countQuery']);
        $this->assertStringContainsString('AND (u.name LIKE ? OR p.title LIKE ? OR a.company_name LIKE ?)', $result['countQuery']);
        $this->assertStringContainsString('AND b.status = ?', $result['countQuery']);
    }

    /**
     * @dataProvider validStatusesProvider
     */
    public function testEachValidStatusIsAccepted(string $status): void
    {
        $result = BookingSearchQueryBuilder::build('', $status, 1);

        $this->assertStringContainsString('AND b.status = ?', $result['query']);
    }

    public static function validStatusesProvider(): array
    {
        return [
            ['pending'],
            ['approved'],
            ['rejected'],
        ];
    }

    public function testTotalPagesRoundsUp(): void
    {
        $this->assertSame(1, BookingSearchQueryBuilder::totalPages(0));
        $this->assertSame(1, BookingSearchQueryBuilder::totalPages(10));
        $this->assertSame(2, BookingSearchQueryBuilder::totalPages(11));
        $this->assertSame(3, BookingSearchQueryBuilder::totalPages(21));
    }
}