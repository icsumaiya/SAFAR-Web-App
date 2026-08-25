<?php

use PHPUnit\Framework\TestCase;

final class AgencySearchQueryBuilderTest extends TestCase
{
    public function testNoFiltersProducesBaseQueryWithNoParams(): void
    {
        $result = AgencySearchQueryBuilder::build('', 'all');

        $this->assertStringNotContainsString('LIKE', $result['query']);
        $this->assertStringNotContainsString('AND a.status', $result['query']);
        $this->assertSame([], $result['params']);
        $this->assertStringContainsString('ORDER BY a.id DESC', $result['query']);
    }

    public function testSearchAddsLikeClauseWithThreeParams(): void
    {
        $result = AgencySearchQueryBuilder::build('sumaiya', 'all');

        $this->assertStringContainsString(
            'AND (a.company_name LIKE ? OR u.name LIKE ? OR u.email LIKE ?)',
            $result['query']
        );
        $this->assertSame(['%sumaiya%', '%sumaiya%', '%sumaiya%'], $result['params']);
    }

    public function testValidStatusFilterAddsStatusClause(): void
    {
        $result = AgencySearchQueryBuilder::build('', 'suspended');

        $this->assertStringContainsString('AND a.status = ?', $result['query']);
        $this->assertSame(['suspended'], $result['params']);
    }

    public function testInvalidStatusFilterIsIgnored(): void
    {
        $result = AgencySearchQueryBuilder::build('', 'bogus-status');

        $this->assertStringNotContainsString('AND a.status', $result['query']);
        $this->assertSame([], $result['params']);
    }

    public function testSearchTermIsTrimmedBeforeUse(): void
    {
        $result = AgencySearchQueryBuilder::build('  cox  ', 'all');

        $this->assertSame(['%cox%', '%cox%', '%cox%'], $result['params']);
    }

    public function testCombinesSearchAndStatusFilter(): void
    {
        $result = AgencySearchQueryBuilder::build('tours', 'verified');

        $this->assertSame(['%tours%', '%tours%', '%tours%', 'verified'], $result['params']);
    }

    /**
     * @dataProvider validStatusesProvider
     */
    public function testEachValidStatusIsAccepted(string $status): void
    {
        $result = AgencySearchQueryBuilder::build('', $status);

        $this->assertStringContainsString('AND a.status = ?', $result['query']);
    }

    public static function validStatusesProvider(): array
    {
        return [
            ['pending'],
            ['verified'],
            ['rejected'],
            ['suspended'],
        ];
    }
}