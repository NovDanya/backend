<?php
require_once __DIR__ . '/../lib/Validator.php';
require_once __DIR__ . '/../models/Application.php';

class ApplicationController {
    private $db;

    public function __construct() {
        error_log('Constructing ApplicationController');
        try {
            $this->db = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            error_log('Database connection successful');
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed']);
            exit;
        }
    }

    public function create() {
        error_log('Entering create method');
        $data = json_decode(file_get_contents('php://input'), true);
        error_log('Received data: ' . print_r($data, true));
        $errors = Validator::validateApplication($data);
        if (!empty($errors)) {
            error_log('Validation errors: ' . print_r($errors, true));
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO applications (fio, phone, email, dob, gender, bio, contract) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['fio'],
                $data['phone'],
                $data['email'],
                $data['dob'],
                $data['gender'],
                $data['bio'],
                isset($data['contract']) ? 1 : 0
            ]);
            $application_id = $this->db->lastInsertId();
            error_log('Inserted application with ID: ' . $application_id);

            try {
                $login = $this->generateUniqueLogin();
            } catch (Exception $e) {
                $this->db->rollBack();
                error_log('Failed to generate unique login: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Unable to generate unique login']);
                return;
            }

            $password = substr(uniqid(), 0, 8);
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
            $stmt->execute([$login, $password_hash, $application_id]);
            error_log('Inserted user with login: ' . $login);

            $this->saveLanguages($application_id, $data['languages']);
            error_log('Saved languages for application ID: ' . $application_id);

            $this->db->commit();
            echo json_encode([
                'login' => $login,
                'password' => $password,
                'profile_url' => "http://u68818.kubsu-dev.ru/8LAB/profile.php?id=$application_id",
                'application_id' => $application_id
            ]);
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Create transaction failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function update($id) {
        error_log('Entering update method for ID: ' . $id);
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['login'])) {
            error_log('Unauthorized access attempt: login not provided');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $stmt = $this->db->prepare("SELECT id FROM applications WHERE id = ? AND id = (SELECT application_id FROM users WHERE login = ?)");
        $stmt->execute([$id, $data['login']]);
        if (!$stmt->fetch()) {
            error_log('Forbidden access for ID: ' . $id . ', login: ' . $data['login']);
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        error_log('Received update data: ' . print_r($data, true));
        $errors = Validator::validateApplication($data);
        if (!empty($errors)) {
            error_log('Validation update errors: ' . print_r($errors, true));
            http_response_code(400);
            echo json_encode(['errors' => $errors]);
            return;
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE applications SET fio = ?, phone = ?, email = ?, dob = ?, gender = ?, bio = ?, contract = ? WHERE id = ?");
            $stmt->execute([
                $data['fio'],
                $data['phone'],
                $data['email'],
                $data['dob'],
                $data['gender'],
                $data['bio'],
                isset($data['contract']) ? 1 : 0,
                $id
            ]);
            error_log('Updated application with ID: ' . $id);

            $this->db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$id]);
            $this->saveLanguages($id, $data['languages']);
            error_log('Updated languages for application ID: ' . $id);

            $this->db->commit();
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Update transaction failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function login($login, $pass) {
        error_log('Entering login method for login: ' . $login);
        $stmt = $this->db->prepare("SELECT id, password_hash, application_id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password_hash'])) {
            echo json_encode([
                'login' => $login,
                'application_id' => $user['application_id']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Неверный логин или пароль']);
        }
    }

    public function get($id) {
        error_log('Entering get method for ID: ' . $id);
        $stmt = $this->db->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            http_response_code(404);
            echo json_encode(['error' => 'Profile not found']);
            return;
        }

        $stmt = $this->db->prepare("SELECT pl.name FROM programming_languages pl JOIN application_languages al ON pl.id = al.language_id WHERE al.application_id = ?");
        $stmt->execute([$id]);
        $languages = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'fio' => $data['fio'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'dob' => $data['dob'],
            'gender' => $data['gender'],
            'bio' => $data['bio'],
            'contract' => (bool)$data['contract'],
            'languages' => $languages
        ]);
    }

    private function generateUniqueLogin() {
        $maxAttempts = 50;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $login = 'user_' . bin2hex(random_bytes(4));
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE login = ?");
            $stmt->execute([$login]);
            if ($stmt->fetchColumn() == 0) {
                return $login;
            }
        }
        throw new Exception('Unable to generate unique login after ' . $maxAttempts . ' attempts');
    }

    private function saveLanguages($application_id, $languages) {
        error_log('Saving languages for application ID: ' . $application_id);
        if (!is_array($languages)) {
            error_log('No languages provided or invalid format');
            return;
        }
        $stmt = $this->db->prepare("SELECT id FROM programming_languages WHERE name = ?");
        $insertLang = $this->db->prepare("INSERT INTO programming_languages (name) VALUES (?)");
        $linkStmt = $this->db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");

        foreach ($languages as $language) {
            $stmt->execute([$language]);
            $languageData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$languageData) {
                $insertLang->execute([$language]);
                $language_id = $this->db->lastInsertId();
                error_log('Inserted new language: ' . $language . ', ID: ' . $language_id);
            } else {
                $language_id = $languageData['id'];
                error_log('Found existing language: ' . $language . ', ID: ' . $language_id);
            }
            $linkStmt->execute([$application_id, $language_id]);
            error_log('Linked language ID: ' . $language_id . ' to application ID: ' . $application_id);
        }
    }
}
?>