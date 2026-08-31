<?php
// SSLCommerz integration. Session-creation and validation logic split
// into pure (testable) parts and thin cURL wrappers (integration-only,
// not unit tested — mocking a live HTTP call adds no real value here).

class PaymentGatewayService
{
    private const SANDBOX_INIT_URL = 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
    private const SANDBOX_VALIDATION_URL = 'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php';

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Deterministic-ish unique transaction id tied to the booking, so
     * duplicate initiate calls for the same booking are traceable.
     */
    public static function buildTranId(int $bookingId): string
    {
        return 'SAFAR-' . $bookingId . '-' . bin2hex(random_bytes(4));
    }

    /**
     * Builds the parameter array SSLCommerz expects for session
     * creation. Pure — no network call — so it's unit testable.
     */
    public function buildSessionPayload(array $data): array
    {
        return [
            'store_id' => $this->config['store_id'],
            'store_passwd' => $this->config['store_password'],
            'total_amount' => number_format((float) $data['amount'], 2, '.', ''),
            'currency' => 'BDT',
            'tran_id' => $data['tran_id'],
            'success_url' => $data['success_url'],
            'fail_url' => $data['fail_url'],
            'cancel_url' => $data['cancel_url'],
            'cus_name' => $data['customer_name'],
            'cus_email' => $data['customer_email'],
            'cus_add1' => 'N/A',
            'cus_city' => 'N/A',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $data['customer_phone'] ?? '01700000000',
            'shipping_method' => 'NO',
            'product_name' => $data['product_name'],
            'product_category' => 'Travel',
            'product_profile' => 'general',
        ];
    }

    public function callInitApi(array $payload): array
    {
        $url = self::SANDBOX_INIT_URL;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode((string) $response, true) ?? [];
    }

    public function callValidationApi(string $valId): array
    {
        $params = http_build_query([
            'val_id' => $valId,
            'store_id' => $this->config['store_id'],
            'store_passwd' => $this->config['store_password'],
            'format' => 'json',
        ]);

        $ch = curl_init(self::SANDBOX_VALIDATION_URL . '?' . $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode((string) $response, true) ?? [];
    }

    /**
     * Pure check on an already-fetched validation response — never
     * trust the browser redirect alone, only this server-side result.
     */
    public static function isValidationSuccessful(array $validationResponse, float $expectedAmount): bool
    {
        $status = $validationResponse['status'] ?? '';
        $amount = (float) ($validationResponse['amount'] ?? 0);

        if (!in_array($status, ['VALID', 'VALIDATED'], true)) {
            return false;
        }

        // Allow tiny floating point drift, otherwise amounts must match.
        return abs($amount - $expectedAmount) < 0.01;
    }
}