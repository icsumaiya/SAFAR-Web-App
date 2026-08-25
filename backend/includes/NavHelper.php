<?php
// Pure helper used by header.php to decide whether a nav link should get
// the 'active' CSS class. Kept in its own file (no session/DB side effects)
// so it can be unit tested in isolation.

if (!function_exists('nav_active')) {
    function nav_active($page, $current_page, $type = null, $current_type = '') {
        // $page e path-er shesh ongsho diye match kora hocche, jemon 'admin/index.php'
        if (substr($current_page, -strlen($page)) !== $page) return '';
        if ($type !== null) {
            return ($current_type === $type) ? ' active' : '';
        }
        return ($current_type === '') ? ' active' : '';
    }
}