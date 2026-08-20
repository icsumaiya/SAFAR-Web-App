<?php
// LocationFilter.php

class LocationFilter implements FilterStrategy {
    private string $location;

    public function __construct(string $location) {
        $this->location = $location;
    }

    public function apply(string $whereClause, array &$params): string {
        $params[] = "%{$this->location}%";
        return $whereClause . " AND p.location LIKE ?";
    }
}
?>