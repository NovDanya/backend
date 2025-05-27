<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid profile ID');
}

$id = $_GET['id'];
$dsn = 'mysql:host=localhost;dbname=u68821;charset=utf8';
$username = 'u68821';
$password = '8699290';
$db = new PDO($dsn, $username, $password, [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$stmt = $db->prepare("SELECT * FROM drupal_users WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die('Profile not found');
}

if (!empty($_SESSION['login'])) {
    $stmt = $db->prepare("SELECT id FROM drupal_users WHERE login = ?");
    $stmt->execute([$_SESSION['login']]);
    $user_id = $stmt->fetchColumn();
    if ($user_id != $id) {
        die('Access denied');
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .profile { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="profile">
        <h2>Ваш профиль (ID: <?php echo htmlspecialchars($id); ?>)</h2>
        <p><strong>ФИО:</strong> <?php echo htmlspecialchars($data['fio']); ?></p>
        <p><strong>Телефон:</strong> <?php echo htmlspecialchars($data['phone']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($data['email']); ?></p>
        <p><strong>Комментарий:</strong> <?php echo htmlspecialchars($data['comment']); ?></p>
        <?php if (!empty($_SESSION['login'])): ?>
            <a href="/8LAB/index.php">Редактировать</a>
        <?php endif; ?>
    </div>
</body>
</html>
