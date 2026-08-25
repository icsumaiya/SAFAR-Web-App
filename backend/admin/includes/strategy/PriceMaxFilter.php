<?php
// PriceMaxFilter.php

class PriceMaxFilter implements FilterStrategy {
    private float $maxPrice;

    public function __construct(float $maxPrice) {
        $this->maxPrice = $maxPrice;
    }

    public function apply(string $whereClause, array &$params): string {
        $params[] = $this->maxPrice;
        return $whereClause . " AND p.price <= ?";
    }
}
?>