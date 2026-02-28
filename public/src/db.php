<?php
// src/db.php

// Подключение к PostgreSQL через параметры из окружения
$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'tsj_db';
$user = getenv('DB_USER') ?: 'tsj_user';
$pass = getenv('DB_PASS') ?: 'secret_password';
$port = getenv('DB_PORT') ?: '5432';

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // В рабочем окружении логируйте ошибку вместо вывода
    die("Ошибка подключения к БД (PostgreSQL): " . $e->getMessage());
}
