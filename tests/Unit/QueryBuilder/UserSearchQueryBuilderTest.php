<?php

use PHPUnit\Framework\TestCase;

final class UserSearchQueryBuilderTest extends TestCase
{
    public function testNoFiltersProducesBaseQueryWithNoParams(): void
    {
        $result = UserSearchQueryBuilder::build('', 'all');
        $this->assertStringNotContainsString('LIKE', $result['query']);
        $this->assertStringNotContainsString('AND u.role', $result['query']);
        $this->assertSame([], $result['params']);
        $this->assertStringContainsString('ORDER BY u.created_at DESC', $result['query']);
    }

    public function testSearchAddsLikeClauseWithTwoParams(): void
    {
        $result = UserSearchQueryBuilder::build('john', 'all');
        $this->assertStringContainsString('AND (u.name LIKE ? OR u.email LIKE ?)', $result['query']);
        $this->assertSame(['%john%', '%john%'], $result['params']);
    }

    public function testFilterRoleAddsRoleClause(): void
    {
        $result = UserSearchQueryBuilder::build('', 'agency');
        $this->assertStringContainsString('AND u.role = ?', $result['query']);
        $this->assertSame(['agency'], $result['params']);
    }

    public function testSearchTermIsTrimmedBeforeUse(): void
    {
        $result = UserSearchQueryBuilder::build('  john  ', 'all');
        $this->assertSame(['%john%', '%john%'], $result['params']);
    }

    public function testCombinesSearchAndRoleFilter(): void
    {
        $result = UserSearchQueryBuilder::build('john', 'traveler');
        $this->assertSame(['%john%', '%john%', 'traveler'], $result['params']);
    }

    public function testIncludesBookingCountSubquery(): void
    {
        $result = UserSearchQueryBuilder::build('', 'all');
        $this->assertStringContainsString('booking_count', $result['query']);
    }
}