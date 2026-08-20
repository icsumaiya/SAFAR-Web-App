<?php

use PHPUnit\Framework\TestCase;

require_once SAFAR_ROOT . '/includes/NavHelper.php';

final class NavActiveTest extends TestCase
{
    public function testReturnsActiveWhenCurrentPageMatchesAndNoType(): void
    {
        $result = nav_active('explore.php', '/SAFAR-Web-App/explore.php');

        $this->assertSame(' active', $result);
    }

    public function testReturnsEmptyWhenCurrentPageDoesNotMatch(): void
    {
        $result = nav_active('explore.php', '/SAFAR-Web-App/login.php');

        $this->assertSame('', $result);
    }

    public function testMatchIsBySuffixSoShorterPageNameStillMatchesLongerPath(): void
    {
        // nav_active() only checks whether $current_page ENDS WITH $page.
        // '/admin/index.php' does end with 'index.php', so this also matches.
        // This documents current (suffix-only) behavior of the helper.
        $this->assertSame(' active', nav_active('index.php', '/SAFAR-Web-App/admin/index.php'));

        // The more specific 'admin/index.php' page string matches too.
        $this->assertSame(' active', nav_active('admin/index.php', '/SAFAR-Web-App/admin/index.php'));
    }

    public function testReturnsActiveWhenTypeMatchesCurrentType(): void
    {
        $result = nav_active('explore.php', '/SAFAR-Web-App/explore.php', 'tour', 'tour');

        $this->assertSame(' active', $result);
    }

    public function testReturnsEmptyWhenTypeDoesNotMatchCurrentType(): void
    {
        $result = nav_active('explore.php', '/SAFAR-Web-App/explore.php', 'tour', 'hotel');

        $this->assertSame('', $result);
    }

    public function testReturnsEmptyWhenTypeExpectedButCurrentTypeIsEmpty(): void
    {
        $result = nav_active('explore.php', '/SAFAR-Web-App/explore.php', 'hotel', '');

        $this->assertSame('', $result);
    }

    public function testReturnsEmptyWhenNoTypeExpectedButCurrentTypeIsSet(): void
    {
        // page matches, no $type filter requested, but URL has a type -> should not be 'active'
        $result = nav_active('explore.php', '/SAFAR-Web-App/explore.php', null, 'tour');

        $this->assertSame('', $result);
    }

    public function testHelperDoesRawSuffixMatchRegardlessOfSeparators(): void
    {
        // header.php normalizes backslashes to forward slashes before calling
        // nav_active(); the helper itself only does a raw string suffix check,
        // so a path with backslashes still matches as long as it ends with $page.
        $result = nav_active('explore.php', 'C:\\xampp\\explore.php');

        $this->assertSame(' active', $result);
    }
}