<?php
session_start(); // Для CSRF-токенов
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/utils.php';

// Уникальный realm с временной меткой для принудительного запроса авторизации
$realm = 'Admin access ' . time();

// Защитные заголовки
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

// HTTP-авторизация
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare("SELECT login, password_hash FROM admins WHERE login = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER'] ?? '']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (empty($_SERVER['PHP_AUTH_USER']) ||
        empty($_SERVER['PHP_AUTH_PW']) ||
        !$admin ||
        !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Admin access"');
        print('<h1>401 Требуется авторизация</h1>');
        exit();
    }
} catch (PDOException $e) {
    error_log("Auth error in admin.php: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    print('<h1>500 Ошибка сервера</h1>');
    exit();
}

// Получение заявок с языками
try {
    $stmt = $pdo->query("
        SELECT a.*, GROUP_CONCAT(pl.name SEPARATOR ', ') as languages
        FROM applications a
        LEFT JOIN application_languages al ON a.id = al.application_id
        LEFT JOIN programming_languages pl ON al.language_id = pl.id
        GROUP BY a.id
    ");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получение статистики по языкам
    $stmt = $pdo->query("
        SELECT pl.name, COUNT(al.application_id) as count
        FROM programming_languages pl
        LEFT JOIN application_languages al ON pl.id = al.language_id
        GROUP BY pl.id
        ORDER BY pl.name
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error in admin.php: " . $e->getMessage());
    die('Произошла ошибка при загрузке данных.');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <title>Панель администратора</title>
</head>
<body>
    <h1>Панель администратора</h1>
    <p>Вы вошли как: <?= htmlspecialchars($_SERVER['PHP_AUTH_USER']) ?></p>

    <h2>Список заявок</h2>
    <div class="table-container">
        <table border="1">
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Телефон</th>
                <th>Email</th>
                <th>Дата рождения</th>
                <th>Пол</th>
                <th>Языки</th>
                <th>Биография</th>
                <th>Контракт</th>
                <th>Действия</th>
            </tr>
            <?php if (empty($applications)): ?>
                <tr>
                    <td colspan="10">Заявки отсутствуют</td>
                </tr>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td><?= htmlspecialchars($app['id']) ?></td>
                        <td><?= htmlspecialchars($app['name']) ?></td>
                        <td><?= htmlspecialchars($app['phone']) ?></td>
                        <td><?= htmlspecialchars($app['email']) ?></td>
                        <td><?= htmlspecialchars($app['birthdate']) ?></td>
                        <td><?= htmlspecialchars($app['gender'] === 'male' ? 'Мужской' : 'Женский') ?></td>
                        <td><?= htmlspecialchars($app['languages'] ?? 'Не выбрано') ?></td>
                        <td><?= htmlspecialchars(substr($app['bio'], 0, 50)) ?>...</td>
                        <td><?= $app['contract'] ? 'Да' : 'Нет' ?></td>
                        <td>
                            <a href="edit.php?id=<?= htmlspecialchars($app['id']) ?>">Редактировать</a> |
                            <a href="delete.php?id=<?= htmlspecialchars($app['id']) ?>&csrf_token=<?= htmlspecialchars(generateCsrfToken()) ?>" onclick="return confirm('Вы уверены?')">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>

    <h2>Статистика по языкам программирования</h2>
    <div class="table-container">
        <table border="1">
            <tr>
                <th>Язык</th>
                <th>Количество пользователей</th>
            </tr>
            <?php if (empty($stats)): ?>
                <tr>
                    <td colspan="2">Статистика отсутствует</td>
                </tr>
            <?php else: ?>
                <?php foreach ($stats as $stat): ?>
                    <tr>
                        <td><?= htmlspecialchars($stat['name']) ?></td>
                        <td><?= htmlspecialchars($stat['count']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</body>
</html>
