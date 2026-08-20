<?php
// TypeFilter.php

class TypeFilter implements FilterStrategy {
    private string $type;

    public function __construct(string $type) {
        $this->type = $type;
    }

    public function apply(string $whereClause, array &$params): string {
        $params[] = $this->type;
        return $whereClause . " AND p.type = ?";
    }
}
?>