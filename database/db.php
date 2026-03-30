<?php
require_once __DIR__ . '/../config/env.php';

// Load the .env file
$env = loadEnv(__DIR__ . '/../.env');


$dbname = 'ruler_89';
$hostname = 'localhost';

$DB_USER = $env['DB_USER'] ?? 'root';
$DB_PASSWORD = $env['DB_PASS']?? 'root';
try {
    $dbconn = new PDO(
        "mysql:host=$hostname;dbname=$dbname;charset=utf8mb4",
        $DB_USER,
        $DB_PASSWORD
    );
    
    $dbconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e){
    echo 'Connection failed: ' . $e->getMessage();
}
