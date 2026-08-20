<?php
class Database {
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct($host, $dbname, $user, $pass) {
        $this->connection = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8",
            $user,
            $pass
        );
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function getInstance($host = 'localhost', $dbname = 'safar_db', $user = 'root', $pass = ''): Database {
        if (self::$instance === null) {
            self::$instance = new Database($host, $dbname, $user, $pass);
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }
}
?>