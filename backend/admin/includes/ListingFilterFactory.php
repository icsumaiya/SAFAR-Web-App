<?php
// Builds a FilterContext (Strategy pattern) from raw request params.
// Pure logic (no DB call), extracted from api/filter_listings.php so the
// "which filters get applied" decision logic can be unit tested.

class ListingFilterFactory
{
    /**
     * @param string $type     $_GET['type'] ?? 'all'
     * @param string $location $_GET['location'] ?? ''
     * @param mixed  $maxPrice $_GET['price'] ?? 5000
     */
    public static function build(string $type, string $location, $maxPrice): FilterContext
    {
        $context = new FilterContext();
        $context->addStrategy(new PriceMaxFilter((float) $maxPrice));

        if ($type === 'tour' || $type === 'hotel') {
            $context->addStrategy(new TypeFilter($type));
        }

        if (!empty($location)) {
            $context->addStrategy(new LocationFilter($location));
        }

        return $context;
    }
}