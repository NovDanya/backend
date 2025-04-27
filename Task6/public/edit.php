<?php

session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/validate.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$applicationId = $_GET['id'] ?? null;
if (!$applicationId) {
    header('Location: admin.php');
    exit();
}

$pdo = getDbConnection();
$form_data = [];
$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fio = trim($_POST['fio'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $languages = $_POST['languages'] ?? [];
    $bio = trim($_POST['bio'] ?? '');
    $contract = !empty($_POST['contract']);

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

    if (empty($form_errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE applications SET name = ?, phone = ?, email = ?, birthdate = ?, gender = ?, bio = ?, contract = ? WHERE id = ?");
            $stmt->execute([$fio, $phone, $email, $birthdate, $gender, $bio, $contract, $applicationId]);

            $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?");
            $stmt->execute([$applicationId]);

            foreach ($languages as $languageId) {
                $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
                $stmt->execute([$applicationId, $languageId]);
            }

            header('Location: admin.php');
            exit();
        } catch (PDOException $e) {
            die('Ошибка: ' . $e->getMessage());
        }
    } else {
        $form_data = [
            'fio' => $fio,
            'phone' => $phone,
            'email' => $email,
            'birthdate' => $birthdate,
            'gender' => $gender,
            'languages' => $languages,
            'bio' => $bio,
            'contract' => $contract ? 'yes' : ''
        ];
    }
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$applicationId]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        $langs = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
        die('Ошибка: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Редактирование заявки</title>
</head>
<body>
    <h1>Редактирование заявки</h1>
    <form action="edit.php?id=<?= htmlspecialchars($applicationId) ?>" method="POST">
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
            $stmt = $pdo->query("SELECT id, name FROM programming_languages");
            $languages = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $selectedLangs = $form_data['languages'] ?? [];
            foreach ($languages as $id => $name):
            ?>
                <option value="<?= $id ?>" <?= in_array((string)$id, $selectedLangs) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
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

        <button type="submit">Сохранить</button>
    </form>
    <p><a href="admin.php">Вернуться назад</a></p>
</body>
</html>
