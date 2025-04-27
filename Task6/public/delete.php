<?php

session_start();
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$applicationId = $_GET['id'] ?? null;
if ($applicationId) {
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
        die('Ошибка: ' . $e->getMessage());
    }
}

header('Location: admin.php');
exit();
