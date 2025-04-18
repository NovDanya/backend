<?php

$form_data = [];
$form_errors = [];
$successMessage = '';

if (!empty($_COOKIE['form_data'])) {
    $form_data = json_decode($_COOKIE['form_data'], true);
    setcookie('form_data', '', time() - 3600, '/');
}

if (!empty($_COOKIE['form_errors'])) {
    $form_errors = json_decode($_COOKIE['form_errors'], true);
    setcookie('form_errors', '', time() - 3600, '/');
}

if (!empty($_COOKIE['success_message'])) {
    $successMessage = $_COOKIE['success_message'];
    setcookie('success_message', '', time() - 3600, '/');
}

// Автозаполнение
foreach (['fio', 'phone', 'email', 'birthdate', 'gender', 'bio', 'languages', 'contract'] as $key) {
    if (!isset($form_data[$key]) && isset($_COOKIE["saved_$key"])) {
        $form_data[$key] = ($key === 'languages') ? json_decode($_COOKIE["saved_$key"], true) : $_COOKIE["saved_$key"];
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Форма</title>
</head>
<body>
    <h1>Заполните форму</h1>
    <?php if (!empty($successMessage)): ?>
        <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <form action="actionsWForm.php" method="POST">
        <!-- ФИО -->
        <label for="fio">ФИО:</label>
        <input type="text" id="fio" name="fio"
            value="<?= htmlspecialchars($form_data['fio'] ?? '') ?>"
            class="<?= isset($form_errors['fio']) ? 'error' : '' ?>">
        <?php if (!empty($form_errors['fio'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['fio']) ?></div>
        <?php endif; ?>

        <!-- Телефон -->
        <label for="phone">Телефон:</label>
        <input type="text" id="phone" name="phone"
            value="<?= htmlspecialchars($form_data['phone'] ?? '') ?>"
            class="<?= isset($form_errors['phone']) ? 'error' : '' ?>">
        <?php if (!empty($form_errors['phone'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['phone']) ?></div>
        <?php endif; ?>

        <!-- Email -->
        <label for="email">Email:</label>
        <input type="email" id="email" name="email"
            value="<?= htmlspecialchars($form_data['email'] ?? '') ?>"
            class="<?= isset($form_errors['email']) ? 'error' : '' ?>">
        <?php if (!empty($form_errors['email'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['email']) ?></div>
        <?php endif; ?>

        <!-- Дата рождения -->
        <label for="birthdate">Дата рождения:</label>
        <input type="date" id="birthdate" name="birthdate"
            value="<?= htmlspecialchars($form_data['birthdate'] ?? '') ?>"
            class="<?= isset($form_errors['birthdate']) ? 'error' : '' ?>">
        <?php if (!empty($form_errors['birthdate'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['birthdate']) ?></div>
        <?php endif; ?>

        <!-- Пол -->
        <label>Пол:</label>
        <label><input type="radio" name="gender" value="male"
            <?= (isset($form_data['gender']) && $form_data['gender'] === 'male') ? 'checked' : '' ?>> Мужской</label>
        <label><input type="radio" name="gender" value="female"
            <?= (isset($form_data['gender']) && $form_data['gender'] === 'female') ? 'checked' : '' ?>> Женский</label>
        <?php if (!empty($form_errors['gender'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['gender']) ?></div>
        <?php endif; ?>

        <!-- Языки программирования -->
        <label for="languages">Языки программирования:</label>
        <select id="languages" name="languages[]" multiple
                class="<?= isset($form_errors['languages']) ? 'error' : '' ?>">
            <?php
            $languageList = [
                1 => 'Pascal', 2 => 'C', 3 => 'C++', 4 => 'Java',
                5 => 'C#', 6 => 'Python', 7 => 'JavaScript', 8 => 'PHP',
                9 => 'Perl', 10 => 'Ruby', 11 => 'Go', 12 => 'Swift'
            ];
            $selectedLangs = $form_data['languages'] ?? [];
            foreach ($languageList as $id => $name):
            ?>
                <option value="<?= $id ?>" <?= in_array($id, $selectedLangs) ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (!empty($form_errors['languages'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['languages']) ?></div>
        <?php endif; ?>

        <!-- Биография -->
        <label for="bio">Биография:</label>
        <textarea id="bio" name="bio"
                class="<?= isset($form_errors['bio']) ? 'error' : '' ?>"><?= htmlspecialchars($form_data['bio'] ?? '') ?></textarea>
        <?php if (!empty($form_errors['bio'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['bio']) ?></div>
        <?php endif; ?>

        <!-- Контракт -->
        <label>
            <input type="checkbox" name="contract" value="yes"
                <?= (isset($form_data['contract']) && $form_data['contract'] === 'yes') ? 'checked' : '' ?>> С контрактом ознакомлен(а)
        </label>
        <?php if (!empty($form_errors['contract'])): ?>
            <div class="error-text"><?= htmlspecialchars($form_errors['contract']) ?></div>
        <?php endif; ?>

        <!-- Кнопка -->
        <button type="submit">Отправить</button>
    </form>

</body>
</html>