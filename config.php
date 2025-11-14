<?php
$host = "YOUR_TINKERHOST_SQL_HOST";  // ex: sql302.byetcluster.com
$db   = "YOUR_DATABASE_NAME";
$user = "YOUR_DATABASE_USERNAME";
$pass = "YOUR_DATABASE_PASSWORD";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
