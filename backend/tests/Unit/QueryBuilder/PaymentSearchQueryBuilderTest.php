<?php

use PHPUnit\Framework\TestCase;

final class PaymentSearchQueryBuilderTest extends TestCase
{
    public function testNoFiltersProducesBaseQueryWithNoParams(): void
    {
        $result = PaymentSearchQueryBuilder::build('', 'all', 1);

        $this->assertStringNotContainsString('LIKE', $result['query']);
        $this->assertStringNotContainsString('AND pay.status', $result['query']);
        $this->assertSame([], $result['params']);
        $this->assertStringContainsString('ORDER BY pay.created_at DESC', $result['query']);
    }

    public function testSearchAddsLikeClauseWithTwoParams(): void
    {
        $result = PaymentSearchQueryBuilder::build('sumaiya', 'all', 1);

        $this->assertStringContainsString(
            'AND (u.name LIKE ? OR pay.transaction_id LIKE ?)',
            $result['query']
        );
        $this->assertSame(['%sumaiya%', '%sumaiya%'], $result['params']);
    }

    public function testValidStatusFilterAddsStatusClause(): void
    {
        $result = PaymentSearchQueryBuilder::build('', 'successful', 1);

        $this->assertStringContainsString('AND pay.status = ?', $result['query']);
        $this->assertSame(['successful'], $result['params']);
    }

    public function testInvalidStatusFilterIsIgnored(): void
    {
        $result = PaymentSearchQueryBuilder::build('', 'bogus-status', 1);

        $this->assertStringNotContainsString('AND pay.status', $result['query']);
        $this->assertSame([], $result['params']);
    }

    public function testSearchTermIsTrimmedBeforeUse(): void
    {
        $result = PaymentSearchQueryBuilder::build('  txn  ', 'all', 1);

        $this->assertSame(['%txn%', '%txn%'], $result['params']);
    }

    public function testCombinesSearchAndStatusFilter(): void
    {
        $result = PaymentSearchQueryBuilder::build('alice', 'pending', 1);

        $this->assertSame(['%alice%', '%alice%', 'pending'], $result['params']);
    }

    public function testPageOneStartsAtOffsetZero(): void
    {
        $result = PaymentSearchQueryBuilder::build('', 'all', 1);

        $this->assertStringContainsString('LIMIT 10 OFFSET 0', $result['query']);
        $this->assertSame(1, $result['page']);
    }

    public function testPageThreeOffsetsByTwoPages(): void
    {
        $result = PaymentSearchQueryBuilder::build('', 'all', 3);

        $this->assertStringContainsString('LIMIT 10 OFFSET 20', $result['query']);
        $this->assertSame(3, $result['page']);
    }

    public function testPageBelowOneIsClampedToOne(): void
    {
        $result = PaymentSearchQueryBuilder::build('', 'all', 0);

        $this->assertStringContainsString('OFFSET 0', $result['query']);
        $this->assertSame(1, $result['page']);
    }

    public function testCountQueryHasNoLimitOrSelectColumns(): void
    {
        $result = PaymentSearchQueryBuilder::build('dhaka', 'failed', 2);

        $this->assertStringStartsWith('SELECT COUNT(*)', $result['countQuery']);
        $this->assertStringNotContainsString('LIMIT', $result['countQuery']);
        $this->assertStringContainsString('AND (u.name LIKE ? OR pay.transaction_id LIKE ?)', $result['countQuery']);
        $this->assertStringContainsString('AND pay.status = ?', $result['countQuery']);
    }

    /**
     * @dataProvider validStatusesProvider
     */
    public function testEachValidStatusIsAccepted(string $status): void
    {
        $result = PaymentSearchQueryBuilder::build('', $status, 1);

        $this->assertStringContainsString('AND pay.status = ?', $result['query']);
    }

    public static function validStatusesProvider(): array
    {
        return [
            ['pending'],
            ['successful'],
            ['failed'],
        ];
    }

    public function testTotalPagesRoundsUp(): void
    {
        $this->assertSame(1, PaymentSearchQueryBuilder::totalPages(0));
        $this->assertSame(1, PaymentSearchQueryBuilder::totalPages(10));
        $this->assertSame(2, PaymentSearchQueryBuilder::totalPages(11));
        $this->assertSame(3, PaymentSearchQueryBuilder::totalPages(21));
    }
}