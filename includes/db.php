<?php
$host = 'localhost';
$dbname = 'safar_db';
$user = 'root'; // Change if using a different MySQL username
$pass = ''; // Change if using a MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
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
