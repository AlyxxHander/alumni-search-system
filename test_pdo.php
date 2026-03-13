<?php
$host = '127.0.0.1';
$db   = 'alumni_search_system';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
try {
     $pdo = new PDO($dsn, $user, $pass);
     $stmt = $pdo->query("SELECT * FROM tracking_results ORDER BY id DESC LIMIT 1");
     $row = $stmt->fetch(PDO::FETCH_ASSOC);
     if ($row) {
         $out = "ID: " . $row['id'] . "\n";
         $out .= "Query: " . $row['query_digunakan'] . "\n";
         $out .= "Skor: " . $row['skor_probabilitas'] . "\n";
         $out .= "Result: " . $row['raw_search_response'] . "\n";
         $out .= "Gemini: " . $row['raw_gemini_response'] . "\n";
         file_put_contents('d:/Coding Stuff/S6/Rekayasa Kebutuhan/alumni-search-system/pdo_out.txt', $out);
     } else {
         file_put_contents('d:/Coding Stuff/S6/Rekayasa Kebutuhan/alumni-search-system/pdo_out.txt', 'No rows');
     }
} catch (\PDOException $e) {
     file_put_contents('d:/Coding Stuff/S6/Rekayasa Kebutuhan/alumni-search-system/pdo_out.txt', $e->getMessage());
}
