<?php
require_once __DIR__ . '/Database.php';

$host = 'localhost';
$dbname = 'safar_db';
$user = 'root'; // Change if using a different MySQL username
$pass = ''; // Change if using a MySQL password

try {
    // Singleton: reuses the same PDO connection across every file in a request
    $pdo = Database::getInstance($host, $dbname, $user, $pass)->getConnection();
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$base_url = rtrim(str_replace(['/dashboard', '/admin', '/api', '/includes'], '', $script), '/');
if (!defined('BASE_URL')) {
    define('BASE_URL', $base_url);
}
?>
