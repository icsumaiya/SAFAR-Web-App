<?php

use PHPUnit\Framework\TestCase;

final class PackageValidatorTest extends TestCase
{
    private array $validData;

    protected function setUp(): void
    {
        $this->validData = [
            'title' => 'Cox\'s Bazar Beach Tour',
            'location' => 'Cox\'s Bazar',
            'price' => '4500',
            'description' => '3 days, 2 nights beach tour package.',
            'agency_id' => '2',
        ];
    }

    public function testReturnsEmptyStringForValidData(): void
    {
        $this->assertSame('', PackageValidator::validate($this->validData));
    }

    public function testMissingTitleFails(): void
    {
        $data = $this->validData;
        $data['title'] = '';

        $this->assertSame('Please fill in all required fields.', PackageValidator::validate($data));
    }

    public function testMissingLocationFails(): void
    {
        $data = $this->validData;
        unset($data['location']);

        $this->assertSame('Please fill in all required fields.', PackageValidator::validate($data));
    }

    public function testEmptyPriceStringFails(): void
    {
        $data = $this->validData;
        $data['price'] = '';

        $this->assertSame('Please fill in all required fields.', PackageValidator::validate($data));
    }

    public function testMissingDescriptionFails(): void
    {
        $data = $this->validData;
        $data['description'] = '   ';

        $this->assertSame('Please fill in all required fields.', PackageValidator::validate($data));
    }

    public function testMissingAgencyIdFails(): void
    {
        $data = $this->validData;
        $data['agency_id'] = '';

        $this->assertSame('Please fill in all required fields.', PackageValidator::validate($data));
    }

    public function testNonNumericPriceFails(): void
    {
        $data = $this->validData;
        $data['price'] = 'abc';

        $this->assertSame('Price must be a valid positive number.', PackageValidator::validate($data));
    }

    public function testNegativePriceFails(): void
    {
        $data = $this->validData;
        $data['price'] = '-10';

        $this->assertSame('Price must be a valid positive number.', PackageValidator::validate($data));
    }

    public function testZeroPriceIsAllowed(): void
    {
        $data = $this->validData;
        $data['price'] = '0';

        $this->assertSame('', PackageValidator::validate($data));
    }

    public function testTitleIsTrimmedBeforeCheck(): void
    {
        $data = $this->validData;
        $data['title'] = '   ';

        $this->assertSame('Please fill in all required fields.', PackageValidator::validate($data));
    }
}