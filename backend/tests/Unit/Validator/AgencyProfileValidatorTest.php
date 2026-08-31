<?php

use PHPUnit\Framework\TestCase;

final class AgencyProfileValidatorTest extends TestCase
{
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'description' => 'We organise tours across Sylhet.',
            'address' => 'Zindabazar, Sylhet',
            'website' => 'https://sylhet-tours.com',
            'facebook_url' => 'https://facebook.com/sylhettours',
            'instagram_url' => 'https://instagram.com/sylhettours',
        ], $overrides);
    }

    public function testValidDataPasses(): void
    {
        $this->assertSame('', AgencyProfileValidator::validate($this->validData()));
    }

    public function testAllFieldsOptionalWhenEmpty(): void
    {
        $this->assertSame('', AgencyProfileValidator::validate([]));
    }

    public function testDescriptionTooLongFails(): void
    {
        $result = AgencyProfileValidator::validate($this->validData([
            'description' => str_repeat('a', 2001),
        ]));

        $this->assertStringContainsString('Description', $result);
    }

    public function testDescriptionAtMaxLengthPasses(): void
    {
        $this->assertSame('', AgencyProfileValidator::validate($this->validData([
            'description' => str_repeat('a', 2000),
        ])));
    }

    public function testAddressTooLongFails(): void
    {
        $result = AgencyProfileValidator::validate($this->validData([
            'address' => str_repeat('a', 256),
        ]));

        $this->assertStringContainsString('Address', $result);
    }

    public function testInvalidWebsiteUrlFails(): void
    {
        $result = AgencyProfileValidator::validate($this->validData([
            'website' => 'not-a-url',
        ]));

        $this->assertStringContainsString('website', $result);
    }

    public function testUrlWithoutHttpSchemeFails(): void
    {
        $result = AgencyProfileValidator::validate($this->validData([
            'website' => 'www.sylhet-tours.com',
        ]));

        $this->assertStringContainsString('website', $result);
    }

    public function testInvalidFacebookUrlFails(): void
    {
        $result = AgencyProfileValidator::validate($this->validData([
            'facebook_url' => 'ftp://facebook.com/x',
        ]));

        $this->assertStringContainsString('facebook_url', $result);
    }

    public function testInvalidInstagramUrlFails(): void
    {
        $result = AgencyProfileValidator::validate($this->validData([
            'instagram_url' => 'just some text',
        ]));

        $this->assertStringContainsString('instagram_url', $result);
    }

    public function testHttpSchemeIsAccepted(): void
    {
        $this->assertSame('', AgencyProfileValidator::validate($this->validData([
            'website' => 'http://sylhet-tours.com',
        ])));
    }
}