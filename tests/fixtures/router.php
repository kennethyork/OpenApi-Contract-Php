<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($path === '/health' && $method === 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    return;
}

if ($path === '/health') {
    http_response_code(405);
    header('Allow: GET');
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    return;
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => 'Resource not found']);
