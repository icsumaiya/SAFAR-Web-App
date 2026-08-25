<?php

use PHPUnit\Framework\TestCase;

final class BookingManagementHelperTest extends TestCase
{
    public function testApproveActionResolvesToApproved(): void
    {
        $this->assertSame('approved', BookingManagementHelper::resolveStatus('approve'));
    }

    public function testRejectActionResolvesToRejected(): void
    {
        $this->assertSame('rejected', BookingManagementHelper::resolveStatus('reject'));
    }

    public function testAnyOtherValueResolvesToRejected(): void
    {
        $this->assertSame('rejected', BookingManagementHelper::resolveStatus('bogus'));
    }

    public function testRedirectUrlWithoutStatusFilter(): void
    {
        $this->assertSame('bookings.php?msg=updated', BookingManagementHelper::buildRedirectUrl(null));
    }

    public function testRedirectUrlWithEmptyStatusFilterOmitsStatus(): void
    {
        $this->assertSame('bookings.php?msg=updated', BookingManagementHelper::buildRedirectUrl(''));
    }

    public function testRedirectUrlWithStatusFilterAppendsIt(): void
    {
        $this->assertSame(
            'bookings.php?msg=updated&status=pending',
            BookingManagementHelper::buildRedirectUrl('pending')
        );
    }

    public function testRedirectUrlUrlEncodesStatusFilter(): void
    {
        $this->assertSame(
            'bookings.php?msg=updated&status=needs+review',
            BookingManagementHelper::buildRedirectUrl('needs review')
        );
    }

    public function testAllFilterProducesBaseQueryWithNoParams(): void
    {
        $result = BookingManagementHelper::buildListQuery('all');

        $this->assertStringNotContainsString('AND b.status', $result['query']);
        $this->assertSame([], $result['params']);
        $this->assertStringContainsString('ORDER BY b.booking_date DESC', $result['query']);
    }

    public function testSpecificStatusFilterAddsStatusClause(): void
    {
        $result = BookingManagementHelper::buildListQuery('pending');

        $this->assertStringContainsString('AND b.status = ?', $result['query']);
        $this->assertSame(['pending'], $result['params']);
    }

    public function testQueryJoinsExpectedTables(): void
    {
        $result = BookingManagementHelper::buildListQuery('all');

        $this->assertStringContainsString('JOIN users u', $result['query']);
        $this->assertStringContainsString('JOIN packages p', $result['query']);
        $this->assertStringContainsString('JOIN agencies a', $result['query']);
    }
}