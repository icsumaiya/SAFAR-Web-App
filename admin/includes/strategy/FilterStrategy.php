<?php
// FilterStrategy.php

interface FilterStrategy {
    public function apply(string $whereClause, array &$params): string;
}
?>