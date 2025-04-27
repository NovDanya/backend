<?php

session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/utils.php';

// Защитные заголовки
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$applicationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$token = filter_input(INPUT_GET, 'csrf_token', FILTER_SANITIZE_STRING);

if (!$applicationId || $applicationId <= 0 || !$token || !verifyCsrfToken($token)) {
    header('Location: admin.php');
    exit();
}

try {
    $pdo = getDbConnection();

    $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?");
    $stmt->execute([$applicationId]);

    $stmt = $pdo->prepare("DELETE FROM users WHERE application_id = ?");
    $stmt->execute([$applicationId]);

    $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->execute([$applicationId]);

    header('Location: admin.php');
    exit();
} catch (PDOException $e) {
    error_log("Database error in delete.php: " . $e->getMessage());
    die('Произошла ошибка при удалении данных.');
}
