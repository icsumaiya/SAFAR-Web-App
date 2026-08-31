<?php

use PHPUnit\Framework\TestCase;

final class PaymentGatewayServiceTest extends TestCase
{
    public function testBuildTranIdIncludesBookingId(): void
    {
        $tranId = PaymentGatewayService::buildTranId(42);
        $this->assertStringStartsWith('SAFAR-42-', $tranId);
    }

    public function testBuildTranIdIsUniqueEachCall(): void
    {
        $a = PaymentGatewayService::buildTranId(1);
        $b = PaymentGatewayService::buildTranId(1);
        $this->assertNotSame($a, $b);
    }

    public function testBuildSessionPayloadMapsAllFields(): void
    {
        $gateway = new PaymentGatewayService(['store_id' => 'test_store', 'store_password' => 'test_pass']);

        $payload = $gateway->buildSessionPayload([
            'amount' => 150,
            'tran_id' => 'SAFAR-1-abc',
            'success_url' => 'http://x/success',
            'fail_url' => 'http://x/fail',
            'cancel_url' => 'http://x/cancel',
            'customer_name' => 'Alice',
            'customer_email' => 'alice@x.com',
            'product_name' => 'Sylhet Tour',
        ]);

        $this->assertSame('test_store', $payload['store_id']);
        $this->assertSame('150.00', $payload['total_amount']);
        $this->assertSame('BDT', $payload['currency']);
        $this->assertSame('SAFAR-1-abc', $payload['tran_id']);
        $this->assertSame('Alice', $payload['cus_name']);
        $this->assertSame('Sylhet Tour', $payload['product_name']);
    }

    public function testIsValidationSuccessfulTrueWhenStatusValidAndAmountMatches(): void
    {
        $response = ['status' => 'VALID', 'amount' => '150.00'];
        $this->assertTrue(PaymentGatewayService::isValidationSuccessful($response, 150.0));
    }

    public function testIsValidationSuccessfulTrueWhenStatusValidated(): void
    {
        $response = ['status' => 'VALIDATED', 'amount' => '150.00'];
        $this->assertTrue(PaymentGatewayService::isValidationSuccessful($response, 150.0));
    }

    public function testIsValidationSuccessfulFalseWhenStatusInvalid(): void
    {
        $response = ['status' => 'FAILED', 'amount' => '150.00'];
        $this->assertFalse(PaymentGatewayService::isValidationSuccessful($response, 150.0));
    }

    public function testIsValidationSuccessfulFalseWhenAmountMismatch(): void
    {
        $response = ['status' => 'VALID', 'amount' => '100.00'];
        $this->assertFalse(PaymentGatewayService::isValidationSuccessful($response, 150.0));
    }

    public function testIsValidationSuccessfulFalseWhenAmountMissing(): void
    {
        $response = ['status' => 'VALID'];
        $this->assertFalse(PaymentGatewayService::isValidationSuccessful($response, 150.0));
    }
}