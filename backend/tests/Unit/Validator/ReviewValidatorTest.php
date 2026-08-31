<?php

use PHPUnit\Framework\TestCase;

final class ReviewValidatorTest extends TestCase
{
    private function validData(): array
    {
        return [
            'booking_id' => 5,
            'rating' => '4',
            'comment' => 'Great trip, would book again!',
        ];
    }

    public function testValidDataPasses(): void
    {
        $this->assertSame('', ReviewValidator::validateSubmission($this->validData()));
    }

    public function testMissingBookingIdFails(): void
    {
        $data = $this->validData();
        unset($data['booking_id']);

        $this->assertSame('A booking must be specified.', ReviewValidator::validateSubmission($data));
    }

    public function testZeroBookingIdFails(): void
    {
        $data = $this->validData();
        $data['booking_id'] = 0;

        $this->assertSame('A booking must be specified.', ReviewValidator::validateSubmission($data));
    }

    public function testEmptyRatingFails(): void
    {
        $data = $this->validData();
        $data['rating'] = '';

        $this->assertSame('Rating must be a whole number between 1 and 5.', ReviewValidator::validateSubmission($data));
    }

    public function testNonNumericRatingFails(): void
    {
        $data = $this->validData();
        $data['rating'] = 'five';

        $this->assertSame('Rating must be a whole number between 1 and 5.', ReviewValidator::validateSubmission($data));
    }

    public function testDecimalRatingFails(): void
    {
        $data = $this->validData();
        $data['rating'] = '4.5';

        $this->assertSame('Rating must be a whole number between 1 and 5.', ReviewValidator::validateSubmission($data));
    }

    public function testZeroRatingFails(): void
    {
        $data = $this->validData();
        $data['rating'] = '0';

        $this->assertSame('Rating must be between 1 and 5.', ReviewValidator::validateSubmission($data));
    }

    public function testRatingAboveFiveFails(): void
    {
        $data = $this->validData();
        $data['rating'] = '6';

        $this->assertSame('Rating must be between 1 and 5.', ReviewValidator::validateSubmission($data));
    }

    /**
     * @dataProvider validRatingsProvider
     */
    public function testEachValidRatingIsAccepted(string $rating): void
    {
        $data = $this->validData();
        $data['rating'] = $rating;

        $this->assertSame('', ReviewValidator::validateSubmission($data));
    }

    public static function validRatingsProvider(): array
    {
        return [['1'], ['2'], ['3'], ['4'], ['5']];
    }

    public function testEmptyCommentIsAllowed(): void
    {
        $data = $this->validData();
        $data['comment'] = '';

        $this->assertSame('', ReviewValidator::validateSubmission($data));
    }

    public function testTooLongCommentFails(): void
    {
        $data = $this->validData();
        $data['comment'] = str_repeat('a', 1001);

        $this->assertSame('Comment is too long (max 1000 characters).', ReviewValidator::validateSubmission($data));
    }

    public function testExactlyMaxLengthCommentPasses(): void
    {
        $data = $this->validData();
        $data['comment'] = str_repeat('a', 1000);

        $this->assertSame('', ReviewValidator::validateSubmission($data));
    }

    // ---- validateModerationStatus ----

    public function testValidModerationStatusesPassVisible(): void
    {
        $this->assertSame('', ReviewValidator::validateModerationStatus('visible'));
    }

    public function testValidModerationStatusesPassHidden(): void
    {
        $this->assertSame('', ReviewValidator::validateModerationStatus('hidden'));
    }

    public function testInvalidModerationStatusFails(): void
    {
        $this->assertSame('Please select a valid review status.', ReviewValidator::validateModerationStatus('deleted'));
    }
}