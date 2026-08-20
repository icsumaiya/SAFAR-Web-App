<?php

use PHPUnit\Framework\TestCase;

final class PackageSearchQueryBuilderTest extends TestCase
{
    public function testNoFiltersProducesBaseQueryWithNoParams(): void
    {
        $result = PackageSearchQueryBuilder::build('', 'all', 'all');

        $this->assertStringNotContainsString('LIKE', $result['query']);
        $this->assertStringNotContainsString('AND p.type', $result['query']);
        $this->assertStringNotContainsString('AND p.agency_id', $result['query']);
        $this->assertSame([], $result['params']);
        $this->assertStringContainsString('ORDER BY p.created_at DESC', $result['query']);
    }

    public function testSearchAddsLikeClauseWithTwoParams(): void
    {
        $result = PackageSearchQueryBuilder::build('beach', 'all', 'all');

        $this->assertStringContainsString('AND (p.title LIKE ? OR p.location LIKE ?)', $result['query']);
        $this->assertSame(['%beach%', '%beach%'], $result['params']);
    }

    public function testFilterTypeAddsTypeClause(): void
    {
        $result = PackageSearchQueryBuilder::build('', 'hotel', 'all');

        $this->assertStringContainsString('AND p.type = ?', $result['query']);
        $this->assertSame(['hotel'], $result['params']);
    }

    public function testFilterAgencyAddsAgencyClause(): void
    {
        $result = PackageSearchQueryBuilder::build('', 'all', '3');

        $this->assertStringContainsString('AND p.agency_id = ?', $result['query']);
        $this->assertSame(['3'], $result['params']);
    }

    public function testAllFiltersCombinedInOrder(): void
    {
        $result = PackageSearchQueryBuilder::build('beach', 'tour', '3');

        $this->assertSame(['%beach%', '%beach%', 'tour', '3'], $result['params']);
        $this->assertStringContainsString(
            'AND (p.title LIKE ? OR p.location LIKE ?) AND p.type = ? AND p.agency_id = ? ORDER BY',
            $result['query']
        );
    }

    public function testSearchTermIsTrimmedBeforeUse(): void
    {
        $result = PackageSearchQueryBuilder::build('  beach  ', 'all', 'all');

        $this->assertSame(['%beach%', '%beach%'], $result['params']);
    }
}