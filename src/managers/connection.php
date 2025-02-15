<?php

final class Connection {
    private $config;
    private static $pdo = null;

    public function __construct() {
        $this->config = require ('config.php');
    }

    public static function connect(): PDO {
        if (self::$pdo === null) {
            try {
                $instance = new self();
                self::$pdo = new PDO(
                    "pgsql:host=" . $instance->config['host'].
                    ";port=" . $instance->config['port'].
                    ";dbname=" . $instance->config['db_name'],
                    $instance->config['db_user'],
                    $instance->config['db_pass']
                );
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            catch (PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}
?>
