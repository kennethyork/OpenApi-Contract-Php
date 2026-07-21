<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$headers = array_change_key_case(getallheaders() ?: [], CASE_LOWER);
$storePath = sys_get_temp_dir() . '/openapi-contract-php-items.json';

function fixture_items(string $storePath): array {
    if (!is_file($storePath)) return [];
    $items = json_decode((string)file_get_contents($storePath), true);
    return is_array($items) ? $items : [];
}

function fixture_save_items(string $storePath, array $items): void {
    file_put_contents($storePath, json_encode($items));
}

function fixture_json($value, int $status = 200, array $headers = []): void {
    http_response_code($status);
    header('Content-Type: application/json');
    foreach ($headers as $name => $headerValue) header($name . ': ' . $headerValue);
    if ($status !== 204) echo json_encode($value);
}

if ($path === '/health' && $method === 'GET') {
    header('Content-Type: application/json');
    header('X-RateLimit-Remaining: 42');
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

if ($path === '/graphql' && $method === 'POST') {
    $payload = json_decode((string)file_get_contents('php://input'), true);
    $query = (string)($payload['query'] ?? '');
    if (str_contains($query, 'hello')) {
        fixture_json(['data' => ['hello' => 'world']]);
        return;
    }
    fixture_json(['errors' => [['message' => 'Unknown field']]], 200);
    return;
}

if (str_starts_with($path, '/items')) {
    if (($headers['x-api-key'] ?? '') !== 'secret') {
        fixture_json(['error' => 'Unauthorized'], 401);
        return;
    }
    if (($headers['x-client'] ?? '') === '') {
        fixture_json(['error' => 'Missing X-Client'], 400);
        return;
    }

    $items = fixture_items($storePath);
    if ($path === '/items' && $method === 'POST') {
        $payload = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($payload) || !isset($payload['name']) || !is_string($payload['name'])) {
            fixture_json(['error' => 'Invalid item'], 422);
            return;
        }
        $id = (string)(count($items) + 1);
        $items[$id] = ['id' => $id, 'name' => $payload['name']];
        fixture_save_items($storePath, $items);
        fixture_json($items[$id], 201, ['Location' => '/items/' . $id]);
        return;
    }
    if ($path === '/items') {
        fixture_json(['error' => 'Method not allowed'], 405, ['Allow' => 'POST']);
        return;
    }

    if (preg_match('#^/items/([^/]+)$#', $path, $match)) {
        $id = $match[1];
        if ($method === 'GET') {
            if (!isset($items[$id])) {
                fixture_json(['error' => 'Not found'], 404);
                return;
            }
            fixture_json($items[$id]);
            return;
        }
        if ($method === 'DELETE') {
            if (!isset($items[$id])) {
                fixture_json(['error' => 'Not found'], 404);
                return;
            }
            unset($items[$id]);
            fixture_save_items($storePath, $items);
            fixture_json(null, 204);
            return;
        }
        fixture_json(['error' => 'Method not allowed'], 405, ['Allow' => 'GET, DELETE']);
        return;
    }
}

http_response_code(404);
header('Content-Type: application/json');
echo json_encode(['error' => 'Resource not found']);
