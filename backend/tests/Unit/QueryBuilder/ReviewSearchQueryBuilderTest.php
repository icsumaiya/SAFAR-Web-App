<?php

use PHPUnit\Framework\TestCase;

final class ReviewSearchQueryBuilderTest extends TestCase
{
    public function testNoFiltersProducesBaseQueryWithNoParams(): void
    {
        $result = ReviewSearchQueryBuilder::build('', 'all', 1);

        $this->assertStringNotContainsString('LIKE', $result['query']);
        $this->assertStringNotContainsString('AND r.status', $result['query']);
        $this->assertSame([], $result['params']);
        $this->assertStringContainsString('ORDER BY r.created_at DESC', $result['query']);
    }

    public function testSearchAddsLikeClauseWithTwoParams(): void
    {
        $result = ReviewSearchQueryBuilder::build('sumaiya', 'all', 1);

        $this->assertStringContainsString(
            'AND (u.name LIKE ? OR p.title LIKE ?)',
            $result['query']
        );
        $this->assertSame(['%sumaiya%', '%sumaiya%'], $result['params']);
    }

    public function testValidStatusFilterAddsStatusClause(): void
    {
        $result = ReviewSearchQueryBuilder::build('', 'hidden', 1);

        $this->assertStringContainsString('AND r.status = ?', $result['query']);
        $this->assertSame(['hidden'], $result['params']);
    }

    public function testInvalidStatusFilterIsIgnored(): void
    {
        $result = ReviewSearchQueryBuilder::build('', 'bogus-status', 1);

        $this->assertStringNotContainsString('AND r.status', $result['query']);
        $this->assertSame([], $result['params']);
    }

    public function testSearchTermIsTrimmedBeforeUse(): void
    {
        $result = ReviewSearchQueryBuilder::build('  cox  ', 'all', 1);

        $this->assertSame(['%cox%', '%cox%'], $result['params']);
    }

    public function testCombinesSearchAndStatusFilter(): void
    {
        $result = ReviewSearchQueryBuilder::build('alice', 'visible', 1);

        $this->assertSame(['%alice%', '%alice%', 'visible'], $result['params']);
    }

    public function testPageOneStartsAtOffsetZero(): void
    {
        $result = ReviewSearchQueryBuilder::build('', 'all', 1);

        $this->assertStringContainsString('LIMIT 10 OFFSET 0', $result['query']);
        $this->assertSame(1, $result['page']);
    }

    public function testPageThreeOffsetsByTwoPages(): void
    {
        $result = ReviewSearchQueryBuilder::build('', 'all', 3);

        $this->assertStringContainsString('LIMIT 10 OFFSET 20', $result['query']);
        $this->assertSame(3, $result['page']);
    }

    public function testPageBelowOneIsClampedToOne(): void
    {
        $result = ReviewSearchQueryBuilder::build('', 'all', 0);

        $this->assertStringContainsString('OFFSET 0', $result['query']);
        $this->assertSame(1, $result['page']);
    }

    public function testCountQueryHasNoLimitOrSelectColumns(): void
    {
        $result = ReviewSearchQueryBuilder::build('dhaka', 'hidden', 2);

        $this->assertStringStartsWith('SELECT COUNT(*)', $result['countQuery']);
        $this->assertStringNotContainsString('LIMIT', $result['countQuery']);
        $this->assertStringContainsString('AND (u.name LIKE ? OR p.title LIKE ?)', $result['countQuery']);
        $this->assertStringContainsString('AND r.status = ?', $result['countQuery']);
    }

    /**
     * @dataProvider validStatusesProvider
     */
    public function testEachValidStatusIsAccepted(string $status): void
    {
        $result = ReviewSearchQueryBuilder::build('', $status, 1);

        $this->assertStringContainsString('AND r.status = ?', $result['query']);
    }

    public static function validStatusesProvider(): array
    {
        return [['visible'], ['hidden']];
    }

    public function testTotalPagesRoundsUp(): void
    {
        $this->assertSame(1, ReviewSearchQueryBuilder::totalPages(0));
        $this->assertSame(1, ReviewSearchQueryBuilder::totalPages(10));
        $this->assertSame(2, ReviewSearchQueryBuilder::totalPages(11));
        $this->assertSame(3, ReviewSearchQueryBuilder::totalPages(21));
    }
}