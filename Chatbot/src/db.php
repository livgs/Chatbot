<?php

function get_db_connection(): PDO {
    $host = '127.0.0.1';
    $port = '5432';
    $dbname = 'chatbot_database';
    $user = 'postgres';
    $pass = '';

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
