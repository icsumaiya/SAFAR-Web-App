<?php
class Package {
    public string $title;
    public string $location;
    public float $price;
    public string $type;
    public string $description;
    public string $image_url;
}

class PackageFactory {
    public static function createPackage(string $type, array $data): Package {
        $package = new Package();
        $package->title = trim($data['title']);
        $package->location = trim($data['location']);
        $package->price = (float) $data['price'];
        $package->description = trim($data['description']);
        $package->image_url = trim($data['image_url'] ?? '');

        if ($type === 'tour') {
            $package->type = 'tour';
        } elseif ($type === 'hotel') {
            $package->type = 'hotel';
        } else {
            throw new InvalidArgumentException("Unknown package type: $type");
        }

        return $package;
    }
}
?>