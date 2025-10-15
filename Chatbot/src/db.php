<?php
$dsn = "pgsql:host=localhost;port=5432;dbname=chatbot;";
$user = "postgres";
$pass = "password";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}