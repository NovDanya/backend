<?php

$dsn = 'mysql:host=localhost;dbname=u68821;charset=utf8';
$username = 'u68821';
$password = '8699290';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

$allowedLanguages = range(1, 12);
$errors = [];
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение и валидация данных
    $data['fio'] = trim($_POST['fio'] ?? '');
    $data['phone'] = trim($_POST['phone'] ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['birthdate'] = $_POST['birthdate'] ?? '';
    $data['gender'] = $_POST['gender'] ?? '';
    $data['languages'] = $_POST['languages'] ?? [];
    $data['bio'] = trim($_POST['bio'] ?? '');
    $data['contract'] = $_POST['contract'] ?? '';

    // Регулярные выражения
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
    } else {
        // Очистка ошибок и сохранение данных на 1 год
        setcookie('form_errors', '', time() - 3600, '/');
        setcookie('form_data', '', time() - 3600, '/');
        foreach ($data as $key => $value) {
            $cookieValue = is_array($value) ? json_encode($value) : $value;
            setcookie("saved_$key", $cookieValue, time() + 365 * 24 * 60 * 60, '/');
        }

        // Сохраняем в БД
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO applications (name, phone, email, birthdate, gender, bio, contract)
                VALUES (:name, :phone, :email, :birthdate, :gender, :bio, 1)");
            $stmt->execute([
                ':name' => $data['fio'],
                ':phone' => $data['phone'],
                ':email' => $data['email'],
                ':birthdate' => $data['birthdate'],
                ':gender' => $data['gender'],
                ':bio' => $data['bio']
            ]);
            $applicationId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id)
                VALUES (:application_id, :language_id)");
            foreach ($data['languages'] as $langId) {
                $stmt->execute([
                    ':application_id' => $applicationId,
                    ':language_id' => $langId
                ]);
            }

            $pdo->commit();
            setcookie('success_message', 'Данные успешно отправлены!', time() + 5, '/');
            header('Location: index.php');
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("Ошибка при сохранении: " . $e->getMessage());
        }
    }
} else {
    header('Location: index.php');
    exit();
}
