<?php
session_start();
$response = $_SESSION['form_response'] ?? [];
unset($_SESSION['form_response']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Результат</title>
</head>
<body>
    <?php if (isset($response['message'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($response['message']); ?></p>
        <p>Логин: <?php echo htmlspecialchars($response['login']); ?></p>
        <p>Пароль: <?php echo htmlspecialchars($response['password']); ?></p>
        <p>Профиль: <a href="<?php echo htmlspecialchars($response['profile_url']); ?>">
            <?php echo htmlspecialchars($response['profile_url']); ?>
        </a></p>
    <?php else: ?>
        <p style="color: red;"><?php echo htmlspecialchars($response['errors'][0] ?? 'Произошла ошибка'); ?></p>
    <?php endif; ?>
</body>
</html>
