<?php

function getDbConnection() {
    $config = require __DIR__ . '/../config/db.php';
    try {
        return new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
    } catch (PDOException $e) {
        error_log("DB Connection Error: " . $e->getMessage());
        die('Ошибка подключения к базе данных.');
    }
}
