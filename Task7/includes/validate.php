<?php

function validateFormData($data) {
    $errors = [];

    if (empty($data['fio']) || !preg_match('/^[а-яА-ЯёЁa-zA-Z\s\-]+$/u', $data['fio'])) {
        $errors['fio'] = 'ФИО должно содержать только буквы, пробелы и дефис';
    }

    if (empty($data['phone']) || !preg_match('/^\+?[0-9]{10,15}$/', $data['phone'])) {
        $errors['phone'] = 'Введите корректный номер телефона';
    }

    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email';
    }

    if (empty($data['birthdate']) || !strtotime($data['birthdate'])) {
        $errors['birthdate'] = 'Введите корректную дату рождения';
    }

    if (!in_array($data['gender'], ['male', 'female'])) {
        $errors['gender'] = 'Выберите пол';
    }

    if (empty($data['bio'])) {
        $errors['bio'] = 'Введите биографию';
    }

    if (empty($data['contract'])) {
        $errors['contract'] = 'Примите условия контракта';
    }

    return $errors;
}
