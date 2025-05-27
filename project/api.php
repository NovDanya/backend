<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

error_log('API accessed: Method: ' . $_SERVER['REQUEST_METHOD'] . ', URI: ' . $_SERVER['REQUEST_URI']);

require_once __DIR__ . '/api/controllers/ApplicationController.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $controller = new ApplicationController();
    $controller->get($_GET['id']);
    exit;
}

if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request: action is required']);
    exit;
}

$controller = new ApplicationController();

if ($method === 'POST' && $data['action'] === 'create') {
    $controller->create();
} elseif ($method === 'PUT' && $data['action'] === 'update' && isset($data['id'])) {
    $controller->update($data['id']);
} elseif ($method === 'POST' && $data['action'] === 'login') {
    $controller->login($data['login'], $data['pass']);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
