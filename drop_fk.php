<?php
$host = '127.0.0.1';
$db   = 'db_alumni_search_system';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
try {
     $pdo = new PDO($dsn, $user, $pass);
     $pdo->exec("ALTER TABLE tracking_results DROP FOREIGN KEY tracking_results_verified_by_foreign");
     echo "Constraint dropped successfully.\n";
} catch (\PDOException $e) {
     echo "Error: " . $e->getMessage() . "\n";
}
