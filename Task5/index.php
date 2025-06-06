<?php
session_start();

// Инициализация переменных
$form_data = [];
$form_errors = [];

// Проверка и очистка cookies с ошибками и данными
if (!empty($_COOKIE['form_data'])) {
    $form_data = json_decode($_COOKIE['form_data'], true);
    setcookie('form_data', '', time() - 3600, '/');
}

if (!empty($_COOKIE['form_errors'])) {
    $form_errors = json_decode($_COOKIE['form_errors'], true);
    setcookie('form_errors', '', time() - 3600, '/');
}

// Автозаполнение из cookies для неавторизованных пользователей
if (!isset($_SESSION['user_id'])) {
    foreach (['fio', 'phone', 'email', 'birthdate', 'gender', 'bio', 'languages', 'contract'] as $key) {
        if (!isset($form_data[$key]) && isset($_COOKIE["saved_$key"])) {
            $form_data[$key] = ($key === 'languages') ? json_decode($_COOKIE["saved_$key"], true) : $_COOKIE["saved_$key"];
        }
    }
}

// Загрузка данных авторизованного пользователя
if (isset($_SESSION['user_id'])) {
    try {
        $dsn = 'mysql:host=localhost;dbname=u68821;charset=utf8';
        $username = 'u68821';
        $password = '8699290';
        $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Получение заявки пользователя
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = (SELECT application_id FROM users WHERE id = ?)");
        $stmt->execute([$_SESSION['user_id']]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        // Получение выбранных языков
        $stmt = $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
        $stmt->execute([$app['id']]);
        $langs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Подготовка данных для формы
        $form_data = [
            'fio' => $app['name'],
            'phone' => $app['phone'],
            'email' => $app['email'],
            'birthdate' => $app['birthdate'],
            'gender' => $app['gender'],
            'bio' => $app['bio'],
            'languages' => $langs,
            'contract' => $app['contract'] ? 'yes' : ''
        ];
    } catch (PDOException $e) {
        die('Ошибка подключения: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Форма</title>
</head>
<body>
    <h1>Заполните форму</h1>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="success-message">Данные успешно сохранены!</div>
        <?php if (isset($_SESSION['new_user'])): ?>
            <div class="success-message">
                Ваш логин: <?= htmlspecialchars($_SESSION['new_user']['login']) ?><br>
                Ваш пароль: <?= htmlspecialchars($_SESSION['new_user']['password']) ?><br>
                Сохраните эти данные!
            </div>
            <?php unset($_SESSION['new_user']); ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['user_id'])): ?>
        <p>Вы вошли как: <?= htmlspecialchars($_SESSION['login']) ?> | <a href="logout.php">Выйти</a></p>
    <?php else: ?>
        <p><a href="login.php">Войти</a></p>
    <?php endif; ?>

    <form action="actionsWForm.php" method="POST">
        <label for="fio">ФИО:</label>
        <input type="text" id="fio" name="fio" value="<?= htmlspecialchars($form_data['fio'] ?? '') ?>" class="<?= isset($form_errors['fio']) ? 'error' : '' ?>">
        <?php if (!empty($form_errors['fio'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['fio']) ?></div>
        <?php endif; ?>

        <label for="phone">Телефон:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>" class="<?= isset($form_errors['phone']) ? 'error' : '' ?>">
        <?php if (!empty($form_errors['phone'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['phone']) ?></div>
        <?php endif; ?>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" class="<?= isset($form_errors['email']) ? 'error' : '' ?>">
        <?php if (!empty($form_errors['email'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['email']) ?></div>
        <?php endif; ?>

        <label for="birthdate">Дата рождения:</label>
        <input type="date" id="birthdate" name="birthdate" value="<?= htmlspecialchars($form_data['birthdate'] ?? '') ?>" class="<?= isset($form_errors['birthdate']) ? 'error' : '' ?>">
        <?php if (!empty($form_errors['birthdate'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['birthdate']) ?></div>
        <?php endif; ?>

        <label>Пол:</label>
        <label><input type="radio" name="gender" value="male" <?= (isset($form_data['gender']) && $form_data['gender'] === 'male') ? 'checked' : '' ?>> Мужской</label>
        <label><input type="radio" name="gender" value="female" <?= (isset($form_data['gender']) && $form_data['gender'] === 'female') ? 'checked' : '' ?>> Женский</label>
        <?php if (!empty($form_errors['gender'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['gender']) ?></div>
        <?php endif; ?>

        <label for="languages">Языки программирования:</label>
        <select id="languages" name="languages[]" multiple class="<?= isset($form_errors['languages']) ? 'error' : '' ?>">
            <?php
            $languageList = [
                1 => 'Pascal', 2 => 'C', 3 => 'C++', 4 => 'Java',
                5 => 'C#', 6 => 'Python', 7 => 'JavaScript', 8 => 'PHP',
                9 => 'Perl', 10 => 'Ruby', 11 => 'Go', 12 => 'Swift'
            ];
            $selectedLangs = $form_data['languages'] ?? [];
            foreach ($languageList as $id => $name):
            ?>
                <option value="<?= $id ?>" <?= in_array((string)$id, $selectedLangs) ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($form_errors['languages'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['languages']) ?></div>
        <?php endif; ?>

        <label for="bio">Биография:</label>
        <textarea id="bio" name="bio" class="<?= isset($form_errors['bio']) ? 'error' : '' ?>"><?= htmlspecialchars($form_data['bio'] ?? '') ?></textarea>
        <?php if (!empty($form_errors['bio'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['bio']) ?></div>
        <?php endif; ?>

        <label>
            <input type="checkbox" name="contract" value="yes" <?= (isset($form_data['contract']) && $form_data['contract'] === 'yes') ? 'checked' : '' ?>> С контрактом ознакомлен(а)
        </label>
        <?php if (!empty($form_errors['contract'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['contract']) ?></div>
        <?php endif; ?>

        <button type="submit">Отправить</button>
    </form>
</body>
</html>
