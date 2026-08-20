<?php
require_once __DIR__ . '/../PackageFactory.php';

class HotelDataAdapter {
    public static function adapt(array $rawHotelRow): Package {
        $package = new Package();
        $package->title = $rawHotelRow['hotel_name'];
        $package->location = $rawHotelRow['city'];
        $package->price = (float) $rawHotelRow['nightly_rate'];
        $package->description = $rawHotelRow['details'] ?? '';
        $package->image_url = $rawHotelRow['image_url'] ?? '';
        $package->type = 'hotel';
        return $package;
    }
}
?>