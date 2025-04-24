<?php
session_start();

// Валидация данных
function validateFormData($data) {
    $errors = [];

    // Валидация ФИО
    if (empty($data['fio']) || !preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $data['fio'])) {
        $errors['fio'] = 'ФИО должно содержать только буквы, пробелы и дефис';
    }

    // Валидация телефона
    if (empty($data['phone']) || !preg_match('/^\+?[0-9]{10,15}$/', $data['phone'])) {
        $errors['phone'] = 'Введите корректный номер телефона';
    }

    // Валидация email
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email';
    }

    // Валидация даты рождения
    if (empty($data['birthdate']) || !strtotime($data['birthdate'])) {
        $errors['birthdate'] = 'Введите корректную дату рождения';
    }

    // Валидация пола
    if (!in_array($data['gender'], ['male', 'female'])) {
        $errors['gender'] = 'Выберите пол';
    }

    // Валидация биографии
    if (empty($data['bio'])) {
        $errors['bio'] = 'Введите биографию';
    }

    // Валидация контракта
    if (empty($data['contract'])) {
        $errors['contract'] = 'Примите условия контракта';
    }

    return $errors;
}

// Генерация случайного логина и пароля
function generateRandomString($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Получение данных из POST
$fio = trim($_POST['fio'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$birthdate = trim($_POST['birthdate'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$languages = $_POST['languages'] ?? [];
$bio = trim($_POST['bio'] ?? '');
$contract = !empty($_POST['contract']);

// Валидация данных
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

// Если есть ошибки, сохраняем их в cookies и перенаправляем обратно на форму
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

// Соединение с базой данных
try {
    $dsn = 'mysql:host=localhost;dbname=u68821;charset=utf8';
    $username = 'u68821';
    $password = '8699290';
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Если пользователь авторизован, получаем его ID
    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];

        // Получение ID заявки пользователя
        $stmt = $pdo->prepare("SELECT application_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $applicationId = $stmt->fetchColumn();

        // Обновление заявки
        $stmt = $pdo->prepare("UPDATE applications SET name = ?, phone = ?, email = ?, birthdate = ?, gender = ?, bio = ?, contract = ? WHERE id = ?");
        $stmt->execute([$fio, $phone, $email, $birthdate, $gender, $bio, $contract, $applicationId]);

        // Очистка существующих языков
        $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?");
        $stmt->execute([$applicationId]);

        // Сохранение новых языков
        foreach ($languages as $languageId) {
            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            $stmt->execute([$applicationId, $languageId]);
        }
    } else {
        // Создание новой заявки
        $stmt = $pdo->prepare("INSERT INTO applications (name, phone, email, birthdate, gender, bio, contract) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$fio, $phone, $email, $birthdate, $gender, $bio, $contract]);

        // Получение ID созданной заявки
        $applicationId = $pdo->lastInsertId();

        // Сохранение языков программирования
        foreach ($languages as $languageId) {
            $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            $stmt->execute([$applicationId, $languageId]);
        }

        // Генерация логина и пароля
        $login = generateRandomString();
        $password = generateRandomString();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Сохранение пользователя в таблицу users
        $stmt = $pdo->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
        $stmt->execute([$login, $passwordHash, $applicationId]);

        // Сохранение сгенерированных логина и пароля в сессию для отображения
        $_SESSION['new_user'] = [
            'login' => $login,
            'password' => $password,
        ];
    }

    // Успешное сохранение
    header('Location: index.php?success=1');
    exit();
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}
