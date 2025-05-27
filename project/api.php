<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Подключение к базе данных
$dsn = 'mysql:host=localhost;dbname=u68821;charset=utf8';
$username = 'u68821';
$password = '8699290';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    error_log($e->getMessage(), 3, 'errors.log');
    exit;
}

// Проверка авторизации
function isAuthenticated($pdo, $token) {
    if (!$token) return false;
    list($login, $password) = explode(':', $token);
    $stmt = $pdo->prepare('SELECT id, password FROM drupal_users WHERE login = ?');
    $stmt->execute([$login]);
    $user = $stmt->fetch();
    return $user && password_verify($password, $user['password']);
}

// Генерация случайного логина и пароля (8 символов, буквы a-z, цифры 0-9)
function generateCredentials() {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $login = '';
    $password = '';
    for ($i = 0; $i < 8; $i++) {
        $login .= $chars[rand(0, strlen($chars) - 1)];
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return ['login' => $login, 'password' => $password];
}

// Проверка уникальности логина
function isLoginUnique($pdo, $login) {
    $stmt = $pdo->prepare('SELECT id FROM drupal_users WHERE login = ?');
    $stmt->execute([$login]);
    return !$stmt->fetch();
}

// Генерация URL профиля
function generateProfileUrl($userId) {
    return 'http://your-site.com/profile/' . $userId; // Замените на ваш домен
}

switch ($method) {
    case 'POST':
        // Создание пользователя
        if (!empty($input)) {
            $fio = $input['fio'] ?? '';
            $phone = $input['phone'] ?? '';
            $email = $input['email'] ?? '';
            $comment = $input['comment'] ?? '';
            $agree = $input['agree'] ?? false;
        } else {
            $fio = $_POST['fio'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $email = $_POST['email'] ?? '';
            $comment = $_POST['comment'] ?? '';
            $agree = isset($_POST['agree']);
        }

        $errors = [];
        if (empty($fio)) $errors[] = 'ФИО обязательно';
        if (!preg_match('/^\+?\d{10,15}$/', $phone)) $errors[] = 'Некорректный телефон';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';
        if (!$agree) $errors[] = 'Необходимо согласие на обработку данных';

        if (!empty($errors)) {
            http_response_code(400);
            $response = ['errors' => $errors];
            if (empty($input)) {
                $_SESSION['form_response'] = $response;
                header('Location: result.php');
                exit;
            }
            echo json_encode($response);
            exit;
        }

        $stmt = $pdo->prepare('SELECT id FROM drupal_users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(400);
            $response = ['errors' => ['Email уже зарегистрирован']];
            if (empty($input)) {
                $_SESSION['form_response'] = $response;
                header('Location: result.php');
                exit;
            }
            echo json_encode($response);
            exit;
        }

        // Генерация уникального логина
        do {
            $credentials = generateCredentials();
            $login = $credentials['login'];
        } while (!isLoginUnique($pdo, $login));
        $password = password_hash($credentials['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO drupal_users (login, password, fio, phone, email, comment) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$login, $password, $fio, $phone, $email, $comment]);

        $userId = $pdo->lastInsertId();

        $profileUrl = generateProfileUrl($userId);
        $stmt = $pdo->prepare('INSERT INTO drupal_profiles (user_id, profile_url) VALUES (?, ?)');
        $stmt->execute([$userId, $profileUrl]);

        $response = [
            'message' => 'Пользователь создан',
            'login' => $login,
            'password' => $credentials['password'],
            'profile_url' => $profileUrl
        ];
        if (empty($input)) {
            $_SESSION['form_response'] = $response;
            header('Location: result.php');
            exit;
        }
        echo json_encode($response);
        break;

    case 'PUT':
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!isAuthenticated($pdo, $token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Не авторизован']);
            exit;
        }

        $fio = $input['fio'] ?? '';
        $phone = $input['phone'] ?? '';
        $email = $input['email'] ?? '';
        $comment = $input['comment'] ?? '';

        $errors = [];
        if (empty($fio)) $errors[] = 'ФИО обязательно';
        if (!preg_match('/^\+?\d{10,15}$/', $phone)) $errors[] = 'Некорректный телефон';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Некорректный email';

        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            exit;
        }

        list($login) = explode(':', $token);
        $stmt = $pdo->prepare('SELECT id FROM drupal_users WHERE email = ? AND login != ?');
        $stmt->execute([$email, $login]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['errors' => ['Email уже зарегистрирован']]);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE drupal_users SET fio = ?, phone = ?, email = ?, comment = ? WHERE login = ?');
        $stmt->execute([$fio, $phone, $email, $comment, $login]);

        echo json_encode(['message' => 'Данные обновлены']);
        break;

    case 'GET':
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!isAuthenticated($pdo, $token)) {
            http_response_code(401);
            echo json_encode(['error' => 'Не авторизован']);
            exit;
        }

        list($login) = explode(':', $token);
        $stmt = $pdo->prepare('SELECT fio, phone, email, comment FROM drupal_users WHERE login = ?');
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Пользователь не найден']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается']);
        break;
}
?>
