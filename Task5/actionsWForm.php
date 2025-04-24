<?php
session_start();

try {
    $dsn = 'mysql:host=localhost;dbname=u68821;charset=utf8';
    $username = 'u68821';
    $password = '8699290';
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

$allowedLanguages = range(1, 12);
$errors = [];
$data = [];

$data['fio'] = trim($_POST['fio'] ?? '');
$data['phone'] = trim($_POST['phone'] ?? '');
$data['email'] = trim($_POST['email'] ?? '');
$data['birthdate'] = $_POST['birthdate'] ?? '';
$data['gender'] = $_POST['gender'] ?? '';
$data['languages'] = $_POST['languages'] ?? [];
$data['bio'] = trim($_POST['bio'] ?? '');
$data['contract'] = $_POST['contract'] ?? '';

if (empty($data['fio']) || !preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $data['fio'])) {
    $errors['fio'] = 'ФИО должно содержать только буквы, пробелы и дефис';
}

if (empty($data['phone']) || !preg_match('/^\+?\d{10,15}$/', $data['phone'])) {
    $errors['phone'] = 'Телефон должен содержать от 10 до 15 цифр, возможно с + в начале';
}

if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Неверный формат E-mail';
}

$birthdateObj = DateTime::createFromFormat('Y-m-d', $data['birthdate']);
if (!$birthdateObj) {
    $errors['birthdate'] = 'Формат даты должен быть ГГГГ-ММ-ДД';
} else {
    $age = (new DateTime())->diff($birthdateObj)->y;
    if ($age < 1 || $age > 120) {
        $errors['birthdate'] = 'Возраст должен быть от 1 до 120 лет';
    }
}

if (!in_array($data['gender'], ['male', 'female'])) {
    $errors['gender'] = 'Пол должен быть выбран';
}

foreach ($data['languages'] as $lang) {
    if (!in_array((int)$lang, $allowedLanguages)) {
        $errors['languages'] = 'Выбран недопустимый язык программирования';
        break;
    }
}

if (empty($data['bio']) || strlen($data['bio']) > 5000) {
    $errors['bio'] = 'Биография обязательна, не более 5000 символов';
}

if ($data['contract'] !== 'yes') {
    $errors['contract'] = 'Нужно согласиться с контрактом';
}

if (!empty($errors)) {
    setcookie('form_errors', json_encode($errors), 0, '/');
    setcookie('form_data', json_encode($data), 0, '/');
    header('Location: index.php');
    exit();
}

// Генерация логина и пароля для новых пользователей
function generateLogin($pdo, $email) {
    $base = substr($email, 0, strpos($email, '@'));
    $login = $base;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetchColumn() == 0) {
            return $login;
        }
        $login = $base . $counter;
        $counter++;
    }
}

function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

try {
    $pdo->beginTransaction();

    if (isset($_SESSION['user_id'])) {
        // Обновление данных авторизованного пользователя
        $stmt = $pdo->prepare("UPDATE applications SET name = :name, phone = :phone, email = :email, birthdate = :birthdate, gender = :gender, bio = :bio, contract = 1 WHERE id = (SELECT application_id FROM users WHERE id = :user_id)");
        $stmt->execute([
            ':name' => $data['fio'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':birthdate' => $data['birthdate'],
            ':gender' => $data['gender'],
            ':bio' => $data['bio'],
            ':user_id' => $_SESSION['user_id']
        ]);

        $stmt = $pdo->prepare("SELECT application_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $applicationId = $stmt->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (:application_id, :language_id)");
        foreach ($data['languages'] as $langId) {
            $stmt->execute([
                ':application_id' => $applicationId,
                ':language_id' => $langId
            ]);
        }
    } else {
        // Сохранение новой заявки
        $stmt = $pdo->prepare("INSERT INTO applications (name, phone, email, birthdate, gender, bio, contract) VALUES (:name, :phone, :email, :birthdate, :gender, :bio, 1)");
        $stmt->execute([
            ':name' => $data['fio'],
            ':phone' => $data['phone'],
            ':email' => $data['email'],
            ':birthdate' => $data['birthdate'],
            ':gender' => $data['gender'],
            ':bio' => $data['bio']
        ]);
        $applicationId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (:application_id, :language_id)");
        foreach ($data['languages'] as $langId) {
            $stmt->execute([
                ':application_id' => $applicationId,
                ':language_id' => $langId
            ]);
        }

        // Генерация и сохранение логина/пароля
        $login = generateLogin($pdo, $data['email']);
        $password = generatePassword();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (application_id, login, password_hash) VALUES (:application_id, :login, :password_hash)");
        $stmt->execute([
            ':application_id' => $applicationId,
            ':login' => $login,
            ':password_hash' => $passwordHash
        ]);

        $_SESSION['new_user'] = ['login' => $login, 'password' => $password];

        // Сохранение данных в cookies для неавторизованных
        foreach ($data as $key => $value) {
            $cookieValue = is_array($value) ? json_encode($value) : $value;
            setcookie("saved_$key", $cookieValue, time() + 365 * 24 * 60 * 60, '/');
        }
    }

    $pdo->commit();
    setcookie('form_errors', '', time() - 3600, '/');
    setcookie('form_data', '', time() - 3600, '/');
    header('Location: index.php?success=1');
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Ошибка при сохранении: " . $e->getMessage());
}
