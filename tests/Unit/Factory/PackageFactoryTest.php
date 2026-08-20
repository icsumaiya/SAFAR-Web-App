<?php

use PHPUnit\Framework\TestCase;

final class PackageFactoryTest extends TestCase
{
    private array $validData;

    protected function setUp(): void
    {
        $this->validData = [
            'title' => '  Cox\'s Bazar Beach Tour  ',
            'location' => '  Cox\'s Bazar  ',
            'price' => '1500.50',
            'description' => '  A relaxing beach getaway.  ',
            'image_url' => '  https://example.com/img.jpg  ',
        ];
    }

    public function testCreatesTourPackageWithTrimmedValues(): void
    {
        $package = PackageFactory::createPackage('tour', $this->validData);

        $this->assertInstanceOf(Package::class, $package);
        $this->assertSame('tour', $package->type);
        $this->assertSame("Cox's Bazar Beach Tour", $package->title);
        $this->assertSame("Cox's Bazar", $package->location);
        $this->assertSame(1500.50, $package->price);
        $this->assertSame('A relaxing beach getaway.', $package->description);
        $this->assertSame('https://example.com/img.jpg', $package->image_url);
    }

    public function testCreatesHotelPackage(): void
    {
        $package = PackageFactory::createPackage('hotel', $this->validData);

        $this->assertSame('hotel', $package->type);
    }

    public function testThrowsExceptionForUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown package type: cruise');

        PackageFactory::createPackage('cruise', $this->validData);
    }

    public function testThrowsExceptionForEmptyType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PackageFactory::createPackage('', $this->validData);
    }

    public function testMissingImageUrlDefaultsToEmptyString(): void
    {
        $data = $this->validData;
        unset($data['image_url']);

        $package = PackageFactory::createPackage('tour', $data);

        $this->assertSame('', $package->image_url);
    }

    public function testPriceCastsNonNumericStringToZero(): void
    {
        $data = $this->validData;
        $data['price'] = 'not-a-number';

        $package = PackageFactory::createPackage('tour', $data);

        $this->assertSame(0.0, $package->price);
    }

    public function testWhitespaceOnlyTitleIsTrimmedToEmptyString(): void
    {
        $data = $this->validData;
        $data['title'] = '    ';

        $package = PackageFactory::createPackage('tour', $data);

        $this->assertSame('', $package->title);
    }
}