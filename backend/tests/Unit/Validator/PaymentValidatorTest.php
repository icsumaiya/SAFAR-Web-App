<?php

use PHPUnit\Framework\TestCase;

final class PaymentValidatorTest extends TestCase
{
    private function validData(): array
    {
        return [
            'booking_id' => 5,
            'amount' => '150.00',
            'method' => 'bkash',
            'status' => 'successful',
        ];
    }

    public function testValidDataPasses(): void
    {
        $this->assertSame('', PaymentValidator::validate($this->validData()));
    }

    public function testMissingBookingIdFails(): void
    {
        $data = $this->validData();
        unset($data['booking_id']);

        $this->assertNotSame('', PaymentValidator::validate($data));
    }

    public function testZeroBookingIdFails(): void
    {
        $data = $this->validData();
        $data['booking_id'] = 0;

        $this->assertNotSame('', PaymentValidator::validate($data));
    }

    public function testEmptyAmountFails(): void
    {
        $data = $this->validData();
        $data['amount'] = '';

        $this->assertSame('Amount must be a valid positive number.', PaymentValidator::validate($data));
    }

    public function testNonNumericAmountFails(): void
    {
        $data = $this->validData();
        $data['amount'] = 'abc';

        $this->assertSame('Amount must be a valid positive number.', PaymentValidator::validate($data));
    }

    public function testZeroOrNegativeAmountFails(): void
    {
        $data = $this->validData();
        $data['amount'] = '0';
        $this->assertSame('Amount must be a valid positive number.', PaymentValidator::validate($data));

        $data['amount'] = '-10';
        $this->assertSame('Amount must be a valid positive number.', PaymentValidator::validate($data));
    }

    public function testInvalidMethodFails(): void
    {
        $data = $this->validData();
        $data['method'] = 'paypal';

        $this->assertSame('Please select a valid payment method.', PaymentValidator::validate($data));
    }

    public function testInvalidStatusFails(): void
    {
        $data = $this->validData();
        $data['status'] = 'refunded';

        $this->assertSame('Please select a valid payment status.', PaymentValidator::validate($data));
    }

    /**
     * @dataProvider validMethodsProvider
     */
    public function testEachValidMethodIsAccepted(string $method): void
    {
        $data = $this->validData();
        $data['method'] = $method;

        $this->assertSame('', PaymentValidator::validate($data));
    }

    public static function validMethodsProvider(): array
    {
        return [['cash'], ['bkash'], ['nagad'], ['bank_transfer'], ['card']];
    }

    /**
     * @dataProvider validStatusesProvider
     */
    public function testEachValidStatusIsAccepted(string $status): void
    {
        $data = $this->validData();
        $data['status'] = $status;

        $this->assertSame('', PaymentValidator::validate($data));
    }

    public static function validStatusesProvider(): array
    {
        return [['pending'], ['successful'], ['failed']];
    }
}