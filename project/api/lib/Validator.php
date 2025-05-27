<?php
class Validator {
  public static function validateApplication($data) {
    $errors = [];
    if (empty($data['fio']) || !preg_match('/^[a-zA-Zа-яА-Я\s]{1,150}$/u', $data['fio'])) {
      $errors['fio'] = 'Некорректное ФИО';
    }
    if (empty($data['phone']) || !preg_match('/^\+?\d{10,15}$/', $data['phone'])) {
      $errors['phone'] = 'Некорректный телефон';
    }
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      $errors['email'] = 'Некорректный email';
    }
    if (empty($data['dob']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['dob'])) {
      $errors['dob'] = 'Некорректная дата рождения';
    }
    if (empty($data['gender']) || !in_array($data['gender'], ['male', 'female'])) {
      $errors['gender'] = 'Выберите пол';
    }
    if (empty($data['languages']) || !is_array($data['languages']) || count($data['languages']) === 0) {
      $errors['languages'] = 'Выберите хотя бы один язык';
    }
    if (empty($data['bio'])) {
      $errors['bio'] = 'Заполните биографию';
    }
    if (empty($data['contract'])) {
      $errors['contract'] = 'Необходимо согласиться с условиями';
    }
    return $errors;
  }
}