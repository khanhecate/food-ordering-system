<?php
$host = 'db';
$user = 'tokoonline';
$pass = 'tokoonline123';
$db   = 'tokoonline';

echo "<h1>Toko Online - PHP + MariaDB</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    echo "<p style='color:green'>✓ Connected to MariaDB: $version</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Connection failed: " . $e->getMessage() . "</p>";
}

phpinfo();
