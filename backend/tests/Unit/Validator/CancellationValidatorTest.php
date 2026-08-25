<?php

use PHPUnit\Framework\TestCase;

final class CancellationValidatorTest extends TestCase
{
    public function testValidRequestPasses(): void
    {
        $error = CancellationValidator::validateRequest([
            'booking_id' => 5,
            'reason' => 'Change of travel plans.',
        ]);

        $this->assertSame('', $error);
    }

    public function testMissingBookingIdFails(): void
    {
        $error = CancellationValidator::validateRequest([
            'booking_id' => 0,
            'reason' => 'Some reason.',
        ]);

        $this->assertSame('A booking must be specified.', $error);
    }

    public function testEmptyReasonFails(): void
    {
        $error = CancellationValidator::validateRequest([
            'booking_id' => 5,
            'reason' => '   ',
        ]);

        $this->assertSame('Please provide a reason for cancellation.', $error);
    }

    public function testTooLongReasonFails(): void
    {
        $error = CancellationValidator::validateRequest([
            'booking_id' => 5,
            'reason' => str_repeat('a', 1001),
        ]);

        $this->assertSame('Reason is too long (max 1000 characters).', $error);
    }

    public function testExactlyMaxLengthReasonPasses(): void
    {
        $error = CancellationValidator::validateRequest([
            'booking_id' => 5,
            'reason' => str_repeat('a', 1000),
        ]);

        $this->assertSame('', $error);
    }

    /**
     * @dataProvider validRefundStatusesProvider
     */
    public function testEachValidRefundStatusIsAccepted(string $status): void
    {
        $this->assertSame('', CancellationValidator::validateRefundStatus($status));
    }

    public static function validRefundStatusesProvider(): array
    {
        return [
            ['not_applicable'],
            ['pending'],
            ['processing'],
            ['refunded'],
            ['rejected'],
        ];
    }

    public function testInvalidRefundStatusFails(): void
    {
        $error = CancellationValidator::validateRefundStatus('bogus');
        $this->assertSame('Please select a valid refund status.', $error);
    }
}