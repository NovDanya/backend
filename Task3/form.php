<?php

// Подключение к базе данных
$dsn = 'mysql:host=localhost;dbname=u68821;charset=utf8';
$username = 'u68821';
$password = '8699290';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных: ' . $e->getMessage());
}

// Список допустимых языков (ID из таблицы programming_languages)
$allowedLanguages = range(1, 12); // ID от 1 до 12

// Массив для хранения ошибок
$errors = [];

// Проверяем, была ли отправлена форма
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Валидация ФИО
    if (empty($_POST['fio'])) {
        $errors['fio'] = 'Поле ФИО обязательно для заполнения';
    } else {
        $fio = trim($_POST['fio']);
        if (strlen($fio) > 150) {
            $errors['fio'] = 'ФИО не должно превышать 150 символов';
        } elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s]+$/u', $fio)) {
            $errors['fio'] = 'ФИО должно содержать только буквы и пробелы';
        }
    }

    // Валидация телефона
    if (empty($_POST['phone'])) {
        $errors['phone'] = 'Поле Телефон обязательно для заполнения';
    } else {
        $phone = trim($_POST['phone']);
        if (!preg_match('/^\+?\d{10,15}$/', $phone)) {
            $errors['phone'] = 'Телефон должен содержать от 10 до 15 цифр, можно с + в начале';
        }
    }

    // Валидация email
    if (empty($_POST['email'])) {
        $errors['email'] = 'Поле E-mail обязательно для заполнения';
    } else {
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Введите корректный email адрес';
        }
    }

    // Валидация даты рождения
    if (empty($_POST['birthdate'])) {
        $errors['birthdate'] = 'Поле Дата рождения обязательно для заполнения';
    } else {
        $birthdate = $_POST['birthdate'];
        $today = new DateTime();
        $birthdateObj = DateTime::createFromFormat('Y-m-d', $birthdate);

        if (!$birthdateObj) {
            $errors['birthdate'] = 'Некорректный формат даты';
        } else {
            $age = $today->diff($birthdateObj)->y;
            if ($age < 12) {
                $errors['birthdate'] = 'Вы должны быть старше 12 лет';
            } elseif ($age > 120) {
                $errors['birthdate'] = 'Проверьте дату рождения';
            }
        }
    }

    // Валидация пола
    if (empty($_POST['gender'])) {
        $errors['gender'] = 'Укажите ваш пол';
    } else {
        $gender = $_POST['gender'];
        if (!in_array($gender, ['male', 'female'])) {
            $errors['gender'] = 'Выбран недопустимый пол';
        }
    }

    // Валидация языков программирования
    if (empty($_POST['languages'])) {
        $errors['languages'] = 'Выберите хотя бы один язык программирования';
    } else {
        $languages = $_POST['languages'];
        foreach ($languages as $lang) {
            if (!in_array((int)$lang, $allowedLanguages)) {
                $errors['languages'] = 'Выбран недопустимый язык программирования';
                break;
            }
        }
    }

    // Валидация биографии
    if (empty($_POST['bio'])) {
        $errors['bio'] = 'Поле Биография обязательно для заполнения';
    } else {
        $bio = trim($_POST['bio']);
        if (strlen($bio) > 5000) {
            $errors['bio'] = 'Биография не должна превышать 5000 символов';
        }
    }

    // Валидация чекбокса с контрактом
    if (empty($_POST['contract'])) {
        $errors['contract'] = 'Необходимо подтвердить ознакомление с контрактом';
    } elseif ($_POST['contract'] !== 'yes') {
        $errors['contract'] = 'Необходимо подтвердить ознакомление с контрактом';
    }

    // Если ошибок нет, сохраняем данные в базу
    if (empty($errors)) {
        try {
            // Начинаем транзакцию
            $pdo->beginTransaction();

            // Вставка данных в таблицу applications
            $stmt = $pdo->prepare("
                INSERT INTO applications (name, phone, email, birthdate, gender, bio, contract)
                VALUES (:name, :phone, :email, :birthdate, :gender, :bio, 1)
            ");
            $stmt->execute([
                ':name' => $fio,
                ':phone' => $phone,
                ':email' => $email,
                ':birthdate' => $birthdate,
                ':gender' => $gender,
                ':bio' => $bio
            ]);

            // Получаем ID последней вставленной записи
            $applicationId = $pdo->lastInsertId();

            // Вставка выбранных языков в таблицу application_languages
            $stmt = $pdo->prepare("
                INSERT INTO application_languages (application_id, language_id)
                VALUES (:application_id, :language_id)
            ");
            foreach ($languages as $languageId) {
                $stmt->execute([
                    ':application_id' => $applicationId,
                    ':language_id' => $languageId
                ]);
            }

            // Фиксируем транзакцию
            $pdo->commit();

            // Получаем названия языков для вывода
            $stmt = $pdo->prepare("
                SELECT name FROM programming_languages
                WHERE id IN (" . implode(',', array_map('intval', $languages)) . ")
            ");
            $stmt->execute();
            $languageNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Выводим сообщение об успехе
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Успешная отправка</title>
                <style>
                    .success-message {
                        background-color: #e8f5e9;
                        border-left: 4px solid #4caf50;
                        padding: 15px;
                        margin: 20px auto;
                        max-width: 800px;
                        color: #2e7d32;
                        font-family: Arial, sans-serif;
                    }
                    .success-message h3 {
                        margin-top: 0;
                        color: #2e7d32;
                    }
                    .button-container {
                        text-align: center;
                        margin-top: 20px;
                    }
                    .return-button {
                        display: inline-block;
                        padding: 10px 20px;
                        background-color: #3498db;
                        color: white;
                        text-decoration: none;
                        border-radius: 4px;
                        font-size: 16px;
                        cursor: pointer;
                    }
                    .return-button:hover {
                        background-color: #2980b9;
                    }
                </style>
            </head>
            <body>
                <div class="success-message">
                    <h3>Данные успешно сохранены!</h3>
                    <p>ФИО: <?php echo htmlspecialchars($fio); ?></p>
                    <p>Телефон: <?php echo htmlspecialchars($phone); ?></p>
                    <p>Email: <?php echo htmlspecialchars($email); ?></p>
                    <p>Дата рождения: <?php echo htmlspecialchars($birthdate); ?></p>
                    <p>Пол: <?php echo ($gender == 'male' ? 'Мужской' : 'Женский'); ?></p>
                    <p>Любимые языки: <?php echo implode(', ', array_map('htmlspecialchars', $languageNames)); ?></p>
                    <p>Биография: <?php echo nl2br(htmlspecialchars($bio)); ?></p>
                    <div class="button-container">
                        <a href="index.php" class="return-button">Вернуться назад</a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit();
        } catch (PDOException $e) {
            // Откатываем транзакцию в случае ошибки
            $pdo->rollBack();
            die('Ошибка сохранения данных: ' . $e->getMessage());
        }
    } else {
        // Выводим ошибки
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Ошибки в форме</title>
            <style>
                .error-message {
                    background-color: #ffebee;
                    border-left: 4px solid #f44336;
                    padding: 15px;
                    margin: 20px auto;
                    max-width: 800px;
                    color: #d32f2f;
                    font-family: Arial, sans-serif;
                }
                .error-message h3 {
                    margin-top: 0;
                    color: #d32f2f;
                }
                button {
                    display: block;
                    margin: 20px auto;
                    padding: 10px 20px;
                    background-color: #3498db;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                }
                button:hover {
                    background-color: #2980b9;
                }
            </style>
        </head>
        <body>
            <div class="error-message">
                <h3>Исправьте следующие ошибки:</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <button onclick="window.history.back()">Вернуться назад</button>
        </body>
        </html>
        <?php
        exit();
    }
} else {
    // Редирект на index.php, если форма не отправлена
    header('Location: index.php');
    exit();
}