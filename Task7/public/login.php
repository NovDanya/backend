<?php

session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/utils.php';

// Защитные заголовки
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $error = 'Недействительный CSRF-токен.';
    } else {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("SELECT id, login, password_hash, application_id FROM users WHERE login = ?");
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['login'] = $user['login'];
                header('Location: index.php');
                exit();
            } else {
                $error = 'Неверный логин или пароль';
            }
        } catch (PDOException $e) {
            error_log("Database error in login.php: " . $e->getMessage());
            $error = 'Произошла ошибка при входе.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <link rel="stylesheet" href="style.css">
    <title>Вход</title>
</head>
<body>
    <h1>Вход</h1>
    <?php if (isset($error)): ?>
        <div class="error-text"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form action="login.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
        <label for="login">Логин:</label>
        <input type="text" id="login" name="login" required>
        <label for="password">Пароль:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Войти</button>
    </form>
    <p><a href="index.php">Вернуться к форме</a></p>
</body>
</html>
