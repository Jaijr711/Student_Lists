<?php
$host = '127.0.0.1';
$dbname = 'iscc_system';
$username = 'root';
$password = '';

$db_type = 'mysql';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");
} catch(PDOException $e) {
    $db_type = 'sqlite';
    try {
        $pdo = new PDO('sqlite:iscc_system.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("PRAGMA foreign_keys = ON;");
    } catch(PDOException $e2) {
        die("Connection failed: " . $e2->getMessage());
    }
}
?>