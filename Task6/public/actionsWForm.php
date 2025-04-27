<?php

session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/validate.php';
require_once __DIR__ . '/../includes/utils.php';

$fio = trim($_POST['fio'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$birthdate = trim($_POST['birthdate'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$languages = $_POST['languages'] ?? [];
$bio = trim($_POST['bio'] ?? '');
$contract = !empty($_POST['contract']);

$form_errors = validateFormData([
    'fio' => $fio,
    'phone' => $phone,
    'email' => $email,
    'birthdate' => $birthdate,
    'gender' => $gender,
    'languages' => $languages,
    'bio' => $bio,
    'contract' => $contract,
]);

if (!empty($form_errors)) {
    setcookie('form_data', json_encode([
        'fio' => $fio,
        'phone' => $phone,
        'email' => $email,
        'birthdate' => $birthdate,
        'gender' => $gender,
        'languages' => $languages,
        'bio' => $bio,
        'contract' => $contract,
    ]), time() + 3600, '/');

    setcookie('form_errors', json_encode($form_errors), time() + 3600, '/');
    header('Location: index.php');
    exit();
}

try {
    $pdo = getDbConnection();

    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT application_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $applicationId = $stmt->fetchColumn();

        $stmt = $pdo->prepare("UPDATE applications SET name = ?, phone = ?, email = ?, birthdate = ?, gender = ?, bio = ?, contract = ? WHERE id = ?");
        $stmt->execute([$fio, $phone, $email, $birthdate, $gender, $bio, $contract, $applicationId]);

        $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$applicationId]);

        foreach ($languages as $languageId) {
            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            $stmt->execute([$applicationId, $languageId]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO applications (name, phone, email, birthdate, gender, bio, contract) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$fio, $phone, $email, $birthdate, $gender, $bio, $contract]);

        $applicationId = $pdo->lastInsertId();

        foreach ($languages as $languageId) {
            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            $stmt->execute([$applicationId, $languageId]);
        }

        $login = generateRandomString();
        $password = generateRandomString();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
        $stmt->execute([$login, $passwordHash, $applicationId]);

        $_SESSION['new_user'] = [
            'login' => $login,
            'password' => $password,
        ];
    }

    header('Location: index.php?success=1');
    exit();
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}
