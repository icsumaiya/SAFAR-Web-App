<?php

use PHPUnit\Framework\TestCase;

final class FilterContextTest extends TestCase
{
    public function testBuildQueryWithNoStrategiesReturnsBaseClause(): void
    {
        $context = new FilterContext();

        [$where, $params] = $context->buildQuery();

        $this->assertSame('1=1', $where);
        $this->assertSame([], $params);
    }

    public function testBuildQueryWithSingleLocationFilter(): void
    {
        $context = new FilterContext();
        $context->addStrategy(new LocationFilter('Sylhet'));

        [$where, $params] = $context->buildQuery();

        $this->assertSame('1=1 AND p.location LIKE ?', $where);
        $this->assertSame(['%Sylhet%'], $params);
    }

    public function testBuildQueryWithSingleTypeFilter(): void
    {
        $context = new FilterContext();
        $context->addStrategy(new TypeFilter('hotel'));

        [$where, $params] = $context->buildQuery();

        $this->assertSame('1=1 AND p.type = ?', $where);
        $this->assertSame(['hotel'], $params);
    }

    public function testBuildQueryWithSinglePriceMaxFilter(): void
    {
        $context = new FilterContext();
        $context->addStrategy(new PriceMaxFilter(5000.0));

        [$where, $params] = $context->buildQuery();

        $this->assertSame('1=1 AND p.price <= ?', $where);
        $this->assertSame([5000.0], $params);
    }

    public function testBuildQueryCombinesMultipleStrategiesInOrder(): void
    {
        $context = new FilterContext();
        $context->addStrategy(new LocationFilter('Cox\'s Bazar'));
        $context->addStrategy(new TypeFilter('tour'));
        $context->addStrategy(new PriceMaxFilter(10000.0));

        [$where, $params] = $context->buildQuery();

        $this->assertSame(
            '1=1 AND p.location LIKE ? AND p.type = ? AND p.price <= ?',
            $where
        );
        $this->assertSame(["%Cox's Bazar%", 'tour', 10000.0], $params);
    }

    public function testLocationFilterWithEmptyStringStillAppliesWildcard(): void
    {
        $context = new FilterContext();
        $context->addStrategy(new LocationFilter(''));

        [$where, $params] = $context->buildQuery();

        $this->assertSame('1=1 AND p.location LIKE ?', $where);
        $this->assertSame(['%%'], $params);
    }

    public function testPriceMaxFilterAcceptsZero(): void
    {
        $context = new FilterContext();
        $context->addStrategy(new PriceMaxFilter(0.0));

        [$where, $params] = $context->buildQuery();

        $this->assertSame([0.0], $params);
    }

    public function testPriceMaxFilterAcceptsNegativeValue(): void
    {
        $context = new FilterContext();
        $context->addStrategy(new PriceMaxFilter(-100.0));

        [$where, $params] = $context->buildQuery();

        $this->assertSame([-100.0], $params);
    }
}