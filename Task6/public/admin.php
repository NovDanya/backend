<?php

session_start();
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

try {
    $pdo = getDbConnection();

    // Получение всех заявок
    $stmt = $pdo->query("SELECT a.*, GROUP_CONCAT(pl.name) as languages
                         FROM applications a
                         LEFT JOIN application_languages al ON a.id = al.application_id
                         LEFT JOIN programming_languages pl ON al.language_id = pl.id
                         GROUP BY a.id");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получение статистики по языкам
    $stmt = $pdo->query("SELECT pl.name, COUNT(al.application_id) as count
                         FROM programming_languages pl
                         LEFT JOIN application_languages al ON pl.id = al.language_id
                         GROUP BY pl.id");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Панель администратора</title>
</head>
<body>
    <h1>Панель администратора</h1>
    <p>Вы вошли как: <?= htmlspecialchars($_SESSION['admin_login']) ?> | <a href="logout.php">Выйти</a></p>

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
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?= htmlspecialchars($app['id']) ?></td>
                    <td><?= htmlspecialchars($app['name']) ?></td>
                    <td><?= htmlspecialchars($app['phone']) ?></td>
                    <td><?= htmlspecialchars($app['email']) ?></td>
                    <td><?= htmlspecialchars($app['birthdate']) ?></td>
                    <td><?= htmlspecialchars($app['gender']) ?></td>
                    <td><?= htmlspecialchars($app['languages'] ?? '') ?></td>
                    <td><?= htmlspecialchars(substr($app['bio'], 0, 50)) ?>...</td>
                    <td><?= $app['contract'] ? 'Да' : 'Нет' ?></td>
                    <td>
                        <a href="edit.php?id=<?= $app['id'] ?>">Редактировать</a> |
                        <a href="delete.php?id=<?= $app['id'] ?>" onclick="return confirm('Вы уверены?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <h2>Статистика по языкам программирования</h2>
    <div class="table-container">
        <table border="1">
            <tr>
                <th>Язык</th>
                <th>Количество пользователей</th>
            </tr>
            <?php foreach ($stats as $stat): ?>
                <tr>
                    <td><?= htmlspecialchars($stat['name']) ?></td>
                    <td><?= htmlspecialchars($stat['count']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
