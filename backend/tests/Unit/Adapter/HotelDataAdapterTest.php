<?php

use PHPUnit\Framework\TestCase;

final class HotelDataAdapterTest extends TestCase
{
    private array $fullRow;

    protected function setUp(): void
    {
        $this->fullRow = [
            'hotel_name' => 'Sea Pearl Beach Resort',
            'city' => 'Cox\'s Bazar',
            'nightly_rate' => '3200.75',
            'details' => 'Beachfront resort with pool.',
            'image_url' => 'https://example.com/hotel.jpg',
        ];
    }

    public function testAdaptsFullRowIntoHotelPackage(): void
    {
        $package = HotelDataAdapter::adapt($this->fullRow);

        $this->assertInstanceOf(Package::class, $package);
        $this->assertSame('hotel', $package->type);
        $this->assertSame('Sea Pearl Beach Resort', $package->title);
        $this->assertSame("Cox's Bazar", $package->location);
        $this->assertSame(3200.75, $package->price);
        $this->assertSame('Beachfront resort with pool.', $package->description);
        $this->assertSame('https://example.com/hotel.jpg', $package->image_url);
    }

    public function testMissingDetailsDefaultsToEmptyString(): void
    {
        $row = $this->fullRow;
        unset($row['details']);

        $package = HotelDataAdapter::adapt($row);

        $this->assertSame('', $package->description);
    }

    public function testMissingImageUrlDefaultsToEmptyString(): void
    {
        $row = $this->fullRow;
        unset($row['image_url']);

        $package = HotelDataAdapter::adapt($row);

        $this->assertSame('', $package->image_url);
    }

    public function testNightlyRateIsCastToFloat(): void
    {
        $row = $this->fullRow;
        $row['nightly_rate'] = '999';

        $package = HotelDataAdapter::adapt($row);

        $this->assertIsFloat($package->price);
        $this->assertSame(999.0, $package->price);
    }

    public function testNonNumericNightlyRateCastsToZero(): void
    {
        $row = $this->fullRow;
        $row['nightly_rate'] = 'invalid';

        $package = HotelDataAdapter::adapt($row);

        $this->assertSame(0.0, $package->price);
    }

    public function testMissingHotelNameThrowsError(): void
    {
        $row = $this->fullRow;
        unset($row['hotel_name']);

        $this->expectException(\Throwable::class);

        HotelDataAdapter::adapt($row);
    }

    public function testMissingCityThrowsError(): void
    {
        $row = $this->fullRow;
        unset($row['city']);

        $this->expectException(\Throwable::class);

        HotelDataAdapter::adapt($row);
    }
}