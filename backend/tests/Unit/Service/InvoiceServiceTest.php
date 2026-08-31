<?php

use PHPUnit\Framework\TestCase;

final class InvoiceServiceTest extends TestCase
{
    public function testGetInvoiceDataReturnsNullWhenBookingNotFound(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturn($stmt);

        $this->assertNull((new InvoiceService($pdo))->getInvoiceData(999));
    }

    public function testGetInvoiceDataWithoutPaymentUsesBasePriceAsFinal(): void
    {
        $bookingRow = [
            'booking_id' => 1, 'traveler_id' => 5, 'booking_status' => 'approved',
            'booking_date' => '2026-08-01', 'check_in' => null, 'check_out' => null, 'guests' => 1,
            'traveler_name' => 'Alice', 'traveler_email' => 'alice@x.com',
            'package_title' => 'Sylhet Tour', 'package_location' => 'Sylhet',
            'package_price' => '200.00', 'package_type' => 'tour',
            'agency_name' => 'Sylhet Tours', 'agency_phone' => '0170000000',
        ];

        $bookingStmt = $this->createMock(PDOStatement::class);
        $bookingStmt->method('fetch')->willReturn($bookingRow);

        $paymentStmt = $this->createMock(PDOStatement::class);
        $paymentStmt->method('fetch')->willReturn(false);

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($bookingStmt, $paymentStmt);

        $invoice = (new InvoiceService($pdo))->getInvoiceData(1);

        $this->assertSame('BKG-000001', $invoice['reference']);
        $this->assertSame(200.0, $invoice['base_amount']);
        $this->assertSame(0.0, $invoice['discount_amount']);
        $this->assertSame(200.0, $invoice['final_amount']);
        $this->assertSame('not_recorded', $invoice['payment_status']);
        $this->assertNull($invoice['coupon_code']);
    }

    public function testGetInvoiceDataWithPaymentAndCouponIncludesDiscount(): void
    {
        $bookingRow = [
            'booking_id' => 1, 'traveler_id' => 5, 'booking_status' => 'approved',
            'booking_date' => '2026-08-01', 'check_in' => null, 'check_out' => null, 'guests' => 2,
            'traveler_name' => 'Alice', 'traveler_email' => 'alice@x.com',
            'package_title' => 'Sylhet Tour', 'package_location' => 'Sylhet',
            'package_price' => '200.00', 'package_type' => 'tour',
            'agency_name' => 'Sylhet Tours', 'agency_phone' => '0170000000',
        ];

        $paymentRow = [
            'amount' => '180.00', 'method' => 'bkash', 'status' => 'successful',
            'transaction_id' => 'TXN1', 'discount_amount' => '20.00', 'coupon_id' => 3,
            'created_at' => '2026-08-02',
        ];

        $bookingStmt = $this->createMock(PDOStatement::class);
        $bookingStmt->method('fetch')->willReturn($bookingRow);

        $paymentStmt = $this->createMock(PDOStatement::class);
        $paymentStmt->method('fetch')->willReturn($paymentRow);

        $couponStmt = $this->createMock(PDOStatement::class);
        $couponStmt->method('fetchColumn')->willReturn('SAVE20');

        $pdo = $this->createMock(PDO::class);
        $pdo->method('prepare')->willReturnOnConsecutiveCalls($bookingStmt, $paymentStmt, $couponStmt);

        $invoice = (new InvoiceService($pdo))->getInvoiceData(1);

        $this->assertSame(200.0, $invoice['base_amount']);
        $this->assertSame(20.0, $invoice['discount_amount']);
        $this->assertSame(180.0, $invoice['final_amount']);
        $this->assertSame('SAVE20', $invoice['coupon_code']);
        $this->assertSame('successful', $invoice['payment_status']);
        $this->assertSame('TXN1', $invoice['transaction_id']);
    }
}