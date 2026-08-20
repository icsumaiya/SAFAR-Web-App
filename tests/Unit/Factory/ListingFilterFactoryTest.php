<?php

use PHPUnit\Framework\TestCase;

final class ListingFilterFactoryTest extends TestCase
{
    public function testAlwaysAppliesPriceMaxFilter(): void
    {
        $context = ListingFilterFactory::build('all', '', 5000);
        [$where, $params] = $context->buildQuery();
        $this->assertStringContainsString('p.price <=', $where);
        $this->assertSame([5000.0], $params);
    }

    public function testTypeTourAddsTypeFilter(): void
    {
        $context = ListingFilterFactory::build('tour', '', 5000);
        [$where, $params] = $context->buildQuery();
        $this->assertStringContainsString('p.type = ?', $where);
        $this->assertContains('tour', $params);
    }

    public function testTypeHotelAddsTypeFilter(): void
    {
        $context = ListingFilterFactory::build('hotel', '', 5000);
        [$where, $params] = $context->buildQuery();
        $this->assertStringContainsString('p.type = ?', $where);
        $this->assertContains('hotel', $params);
    }

    public function testTypeAllDoesNotAddTypeFilter(): void
    {
        $context = ListingFilterFactory::build('all', '', 5000);
        [$where, $params] = $context->buildQuery();
        $this->assertStringNotContainsString('p.type = ?', $where);
    }

    public function testUnknownTypeValueDoesNotAddTypeFilter(): void
    {
        $context = ListingFilterFactory::build('cruise', '', 5000);
        [$where] = $context->buildQuery();
        $this->assertStringNotContainsString('p.type = ?', $where);
    }

    public function testNonEmptyLocationAddsLocationFilter(): void
    {
        $context = ListingFilterFactory::build('all', 'Sylhet', 5000);
        [$where, $params] = $context->buildQuery();
        $this->assertStringContainsString('p.location LIKE ?', $where);
        $this->assertContains('%Sylhet%', $params);
    }

    public function testEmptyLocationDoesNotAddLocationFilter(): void
    {
        $context = ListingFilterFactory::build('all', '', 5000);
        [$where] = $context->buildQuery();
        $this->assertStringNotContainsString('p.location', $where);
    }

    public function testMaxPriceIsCastToFloat(): void
    {
        $context = ListingFilterFactory::build('all', '', '2500');
        [, $params] = $context->buildQuery();
        $this->assertSame(2500.0, $params[0]);
        $this->assertIsFloat($params[0]);
    }

    public function testAllFiltersCanBeCombined(): void
    {
        $context = ListingFilterFactory::build('hotel', 'Cox', '3000');
        [$where, $params] = $context->buildQuery();
        $this->assertStringContainsString('p.price <=', $where);
        $this->assertStringContainsString('p.type = ?', $where);
        $this->assertStringContainsString('p.location LIKE ?', $where);
        $this->assertSame([3000.0, 'hotel', '%Cox%'], $params);
    }
}