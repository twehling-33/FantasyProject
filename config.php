<?php
$host = "sql302.thsite.top";  //
$db   = "thsi_40395189_PlayerStats";
$user = "thsi_40395189";
$pass = "RphibGT1";

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
