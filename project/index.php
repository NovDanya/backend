<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600);
        setcookie('login', '', time() - 3600);
        setcookie('pass', '', time() - 3600);
        $messages[] = 'Спасибо, результаты сохранены.';
        if (!empty($_COOKIE['pass'])) {
            $messages[] = sprintf(
                'Вы можете войти с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.',
                strip_tags($_COOKIE['login']),
                strip_tags($_COOKIE['pass'])
            );
        }
    }

    $errors = array();
    $errors['fio'] = !empty($_COOKIE['fio_error']);
    $errors['phone'] = !empty($_COOKIE['phone_error']);
    $errors['email'] = !empty($_COOKIE['email_error']);
    $errors['bio'] = !empty($_COOKIE['bio_error']);
    $errors['contract'] = !empty($_COOKIE['contract_error']);

    if ($errors['fio']) { setcookie('fio_error', '', time() - 3600); $messages[] = '<div class="error">Заполните ФИО.</div>'; }
    if ($errors['phone']) { setcookie('phone_error', '', time() - 3600); $messages[] = '<div class="error">Некорректный телефон.</div>'; }
    if ($errors['email']) { setcookie('email_error', '', time() - 3600); $messages[] = '<div class="error">Некорректный email.</div>'; }
    if ($errors['bio']) { setcookie('bio_error', '', time() - 3600); $messages[] = '<div class="error">Заполните биографию.</div>'; }
    if ($errors['contract']) { setcookie('contract_error', '', time() - 3600); $messages[] = '<div class="error">Ознакомьтесь с контрактом.</div>'; }

    $values = array();
    $values['fio'] = empty($_COOKIE['fio_value']) ? '' : strip_tags($_COOKIE['fio_value']);
    $values['phone'] = empty($_COOKIE['phone_value']) ? '' : strip_tags($_COOKIE['phone_value']);
    $values['email'] = empty($_COOKIE['email_value']) ? '' : strip_tags($_COOKIE['email_value']);
    $values['bio'] = empty($_COOKIE['bio_value']) ? '' : strip_tags($_COOKIE['bio_value']);
    $values['contract'] = !empty($_COOKIE['contract_value']);

    if (isset($_GET['id']) && !empty($_SESSION['login'])) {
        $dsn = 'mysql:host=localhost;dbname=u68821;charset=utf8';
        $username = 'u68821';
        $password = '8699290';
        $db = new PDO($dsn, $username, $password, [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $stmt = $db->prepare("SELECT * FROM drupal_users WHERE id = ? AND login = ?");
        $stmt->execute([$_GET['id'], $_SESSION['login']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $values['fio'] = strip_tags($data['fio']);
            $values['phone'] = strip_tags($data['phone']);
            $values['email'] = strip_tags($data['email']);
            $values['bio'] = strip_tags($data['comment']);
            $values['contract'] = true; // Предполагаем, что контракт принят при авторизации
        }
    }

    include('form.php');
} else {
    $errors = FALSE;

    if (empty($_POST['fio'])) {
        setcookie('fio_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('fio_value', $_POST['fio'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['phone'])) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('phone_value', $_POST['phone'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['email'])) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('email_value', $_POST['email'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['bio'])) {
        setcookie('bio_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('bio_value', $_POST['bio'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['contract'])) {
        setcookie('contract_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('contract_value', $_POST['contract'], time() + 30 * 24 * 60 * 60);

    if ($errors) {
        header('Location: index.php');
        exit();
    } else {
        setcookie('fio_error', '', time() - 3600);
        setcookie('phone_error', '', time() - 3600);
        setcookie('email_error', '', time() - 3600);
        setcookie('bio_error', '', time() - 3600);
        setcookie('contract_error', '', time() - 3600);
    }

    $dsn = 'mysql:host=localhost;dbname=u68821;charset=utf8';
    $username = 'u68821';
    $password = '8699290';
    $db = new PDO($dsn, $username, $password, [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    try {
        $db->beginTransaction();

        if (!empty($_SESSION['login'])) {
            $stmt = $db->prepare("UPDATE drupal_users SET fio = ?, phone = ?, email = ?, comment = ? WHERE id = (SELECT id FROM drupal_users WHERE login = ?)");
            $stmt->execute([$_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['bio'], $_SESSION['login']]);
        } else {
            $stmt = $db->prepare("INSERT INTO drupal_users (login, password, fio, phone, email, comment) VALUES (?, ?, ?, ?, ?, ?)");
            $login = substr(uniqid(), 0, 8);
            $pass = substr(uniqid(), 0, 8);
            $pass_hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt->execute([$login, $pass_hash, $_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['bio']]);

            $profile_url = "profile.php?id=" . $db->lastInsertId();
            $stmt = $db->prepare("INSERT INTO drupal_profiles (user_id, profile_url) VALUES (?, ?)");
            $stmt->execute([$db->lastInsertId(), $profile_url]);

            setcookie('login', $login);
            setcookie('pass', $pass);
        }

        $db->commit();
        setcookie('save', '1');
        header('Location: index.php');
    } catch (PDOException $e) {
        $db->rollBack();
        print('Ошибка: ' . $e->getMessage());
        exit();
    }
}
?>
