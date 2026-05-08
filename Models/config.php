<?php
class Database {
    private $host = "localhost";
    private $port = "3307";
    private $db_name = "nutrismart";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username, 
                $this->password,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    define('SENDINBLUE_API_KEY', $env['SENDINBLUE_API_KEY'] ?? '');
    define('SENDER_EMAIL', $env['SENDER_EMAIL'] ?? '');
} else {
    define('SENDINBLUE_API_KEY', '');
    define('SENDER_EMAIL', '');
}
?>
