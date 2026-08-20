<?php
// FilterContext.php

class FilterContext {
    private array $strategies = [];

    public function addStrategy(FilterStrategy $strategy): void {
        $this->strategies[] = $strategy;
    }

    public function buildQuery(): array {
        $where = "1=1";
        $params = [];
        foreach ($this->strategies as $strategy) {
            $where = $strategy->apply($where, $params);
        }
        return [$where, $params];
    }
}
?>