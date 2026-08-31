<?php
// Copy to jwt_config.php (gitignored) with your own random secret.
// Generate one with: php -r "echo bin2hex(random_bytes(32));"
// NEVER commit jwt_config.php or put the real secret here.

return [
    'secret' => 'replace-this-with-a-random-64-character-hex-string',
];