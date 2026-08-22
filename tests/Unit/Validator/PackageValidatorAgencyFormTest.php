<?php

use PHPUnit\Framework\TestCase;

final class PackageValidatorAgencyFormTest extends TestCase
{
    private array $validData;

    protected function setUp(): void
    {
        $this->validData = [
            'title' => 'City Hotel Package',
            'location' => 'Dhaka',
            'price' => '2500',
            'description' => 'A comfortable stay in the city center.',
        ];
    }

    public function testReturnsEmptyStringForValidData(): void
    {
        $this->assertSame('', PackageValidator::validateAgencyForm($this->validData));
    }

    public function testMissingTitleFails(): void
    {
        $data = $this->validData;
        $data['title'] = '';

        $this->assertSame('Please fill in all required fields.', PackageValidator::validateAgencyForm($data));
    }

    public function testMissingLocationFails(): void
    {
        $data = $this->validData;
        unset($data['location']);

        $this->assertSame('Please fill in all required fields.', PackageValidator::validateAgencyForm($data));
    }

    public function testMissingPriceFails(): void
    {
        $data = $this->validData;
        $data['price'] = '';

        $this->assertSame('Please fill in all required fields.', PackageValidator::validateAgencyForm($data));
    }

    public function testMissingDescriptionFails(): void
    {
        $data = $this->validData;
        $data['description'] = '   ';

        $this->assertSame('Please fill in all required fields.', PackageValidator::validateAgencyForm($data));
    }

    public function testDoesNotEnforceNumericPrice(): void
    {
        $data = $this->validData;
        $data['price'] = 'not-a-number';

        $this->assertSame('', PackageValidator::validateAgencyForm($data));
    }

    public function testDoesNotRequireAgencyId(): void
    {
        $data = $this->validData;
        unset($data['agency_id']);

        $this->assertSame('', PackageValidator::validateAgencyForm($data));
    }
}