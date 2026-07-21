<?php
declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$headers = array_change_key_case(getallheaders() ?: [], CASE_LOWER);
$storePath = sys_get_temp_dir() . '/openapi-contract-php-items.json';
$randomStorePath = sys_get_temp_dir() . '/openapi-contract-php-random.json';

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

function fixture_raw(string $body, int $status = 200, string $contentType = 'text/plain', array $headers = []): void {
    http_response_code($status);
    header('Content-Type: ' . $contentType);
    foreach ($headers as $name => $headerValue) header($name . ': ' . $headerValue);
    if ($status !== 204) echo $body;
}

function fixture_store(string $storePath): array {
    if (!is_file($storePath)) return ['orders' => [], 'sticky' => []];
    $store = json_decode((string)file_get_contents($storePath), true);
    return is_array($store) ? array_merge(['orders' => [], 'sticky' => []], $store) : ['orders' => [], 'sticky' => []];
}

function fixture_save_store(string $storePath, array $store): void {
    file_put_contents($storePath, json_encode($store));
}

function fixture_json_body(): array {
    $payload = json_decode((string)file_get_contents('php://input'), true);
    return is_array($payload) ? $payload : [];
}

function fixture_valid_order_payload(array $payload): bool {
    $allowedOrderKeys = ['customerId', 'amount', 'currency', 'items'];
    foreach (array_keys($payload) as $key) {
        if (!in_array($key, $allowedOrderKeys, true)) return false;
    }
    if (!isset($payload['customerId'], $payload['amount'], $payload['currency'], $payload['items'])) return false;
    if (!is_string($payload['customerId']) || strlen($payload['customerId']) < 3) return false;
    if (!is_int($payload['amount']) || $payload['amount'] < 1 || $payload['amount'] > 1000) return false;
    if (!in_array($payload['currency'], ['USD', 'EUR', 'GBP'], true)) return false;
    if (!is_array($payload['items']) || $payload['items'] === []) return false;
    $allowedItemKeys = ['sku', 'quantity'];
    foreach ($payload['items'] as $item) {
        if (!is_array($item) || !isset($item['sku'], $item['quantity'])) return false;
        foreach (array_keys($item) as $key) {
            if (!in_array($key, $allowedItemKeys, true)) return false;
        }
        if (!is_string($item['sku']) || $item['sku'] === '') return false;
        if (!is_int($item['quantity']) || $item['quantity'] < 1 || $item['quantity'] > 10) return false;
    }
    return true;
}

function fixture_order_from_payload(array $payload, string $id = 'order_1'): array {
    return [
        'id' => $id,
        'customerId' => $payload['customerId'],
        'amount' => $payload['amount'],
        'currency' => $payload['currency'],
        'status' => $payload['status'] ?? 'created',
        'items' => $payload['items']
    ];
}

if ($method === 'TRACE' && $path === '/random/allow-drift') {
    fixture_json(['error' => 'Method not allowed'], 405, ['Allow' => 'POST']);
    return;
}

if ($method === 'TRACE' && (str_starts_with($path, '/random/') || str_starts_with($path, '/random-broken/'))) {
    fixture_json(['error' => 'Method not allowed'], 405, ['Allow' => 'GET, POST, PATCH, DELETE']);
    return;
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

if ($path === '/random/status' && $method === 'GET') {
    fixture_json(['status' => 'ok', 'code' => 200, 'active' => true], 200, ['X-Fixture-Count' => '7']);
    return;
}

if ($path === '/random/search' && $method === 'GET') {
    $limit = $_GET['limit'] ?? 3;
    if (!is_scalar($limit) || filter_var($limit, FILTER_VALIDATE_INT) === false || (int)$limit < 1 || (int)$limit > 50) {
        fixture_json(['error' => 'Invalid limit'], 422);
        return;
    }
    $q = $_GET['q'] ?? 'alpha';
    if (!is_scalar($q) || trim((string)$q) === '') {
        fixture_json(['error' => 'Invalid query'], 422);
        return;
    }
    fixture_json([
        'total' => 2,
        'cursor' => 'cursor_1',
        'results' => [
            ['id' => 'prod_42', 'name' => 'Atlas Jacket', 'price' => 99.95, 'tags' => ['outerwear', 'featured'], 'stock' => 12],
            ['id' => 'prod_77', 'name' => 'Beacon Pack', 'price' => 59.50, 'tags' => ['travel'], 'stock' => 4]
        ]
    ]);
    return;
}

if (preg_match('#^/random/products/([^/]+)$#', $path, $match) && $method === 'GET') {
    if ($match[1] === 'not-an-id') {
        fixture_json(['error' => 'Not found'], 404);
        return;
    }
    fixture_json([
        'id' => $match[1],
        'name' => 'Atlas Jacket',
        'price' => 99.95,
        'tags' => ['outerwear', 'featured'],
        'stock' => 12,
        'discontinued' => false
    ]);
    return;
}

if ($path === '/random/orders' && $method === 'POST') {
    $payload = fixture_json_body();
    if (!fixture_valid_order_payload($payload)) {
        fixture_json(['error' => 'Invalid order'], 422);
        return;
    }
    $store = fixture_store($randomStorePath);
    $order = fixture_order_from_payload($payload);
    $store['orders'][$order['id']] = $order;
    fixture_save_store($randomStorePath, $store);
    fixture_json($order, 201, ['Location' => '/random/orders/' . $order['id']]);
    return;
}

if (preg_match('#^/random/orders/([^/]+)$#', $path, $match)) {
    $orderId = $match[1];
    $store = fixture_store($randomStorePath);
    if ($orderId === 'not-an-id' || !isset($store['orders'][$orderId])) {
        fixture_json(['error' => 'Not found'], 404);
        return;
    }

    if ($method === 'GET') {
        fixture_json($store['orders'][$orderId]);
        return;
    }

    if ($method === 'PATCH') {
        $payload = fixture_json_body();
        if (!isset($payload['status']) || !in_array($payload['status'], ['created', 'paid', 'shipped', 'cancelled'], true)) {
            fixture_json(['error' => 'Invalid status'], 422);
            return;
        }
        $store['orders'][$orderId]['status'] = $payload['status'];
        fixture_save_store($randomStorePath, $store);
        fixture_json($store['orders'][$orderId]);
        return;
    }

    if ($method === 'DELETE') {
        unset($store['orders'][$orderId]);
        fixture_save_store($randomStorePath, $store);
        fixture_json(null, 204);
        return;
    }
}

if ($path === '/random/text' && $method === 'POST') {
    $body = (string)file_get_contents('php://input');
    if (trim($body) === '') {
        fixture_json(['error' => 'Empty text'], 422);
        return;
    }
    fixture_raw('echo:' . $body, 200, 'text/plain');
    return;
}

if ($path === '/random/blob' && $method === 'GET') {
    fixture_raw('openapi-contract-binary', 200, 'application/octet-stream');
    return;
}

if ($path === '/random/headers' && $method === 'GET') {
    if (($headers['x-trace-id'] ?? '') === '') {
        fixture_json(['error' => 'Missing X-Trace-Id'], 400);
        return;
    }
    fixture_json(['traceId' => $headers['x-trace-id'], 'accepted' => true], 200, ['X-Fixture-Count' => '7', 'X-Fixture-Mode' => 'pass']);
    return;
}

if ($path === '/random/polymorphic' && $method === 'GET') {
    fixture_json(['type' => 'card', 'cardNumber' => '4111111111111111', 'last4' => '1111']);
    return;
}

if ($path === '/random/secure/profile' && $method === 'GET') {
    if (($headers['x-api-key'] ?? '') !== 'secret') {
        fixture_json(['error' => 'Unauthorized'], 401);
        return;
    }
    fixture_json(['id' => 'user_1', 'email' => 'user1@example.com', 'role' => 'admin']);
    return;
}

if ($path === '/random/status-drift' && $method === 'GET') {
    fixture_json(['error' => 'Teapot'], 418);
    return;
}

if ($path === '/random/content-drift' && $method === 'GET') {
    fixture_raw('plain body', 200, 'text/plain');
    return;
}

if ($path === '/random/schema-drift' && $method === 'GET') {
    fixture_json(['id' => 'schema_1', 'count' => 'seven']);
    return;
}

if ($path === '/random/header-drift' && $method === 'GET') {
    fixture_json(['ok' => true]);
    return;
}

if ($path === '/random/public-profile' && $method === 'GET') {
    fixture_json(['id' => 'public_1', 'email' => 'public@example.com']);
    return;
}

if ($path === '/random/lenient' && $method === 'POST') {
    fixture_json(['accepted' => true]);
    return;
}

if ($path === '/random/reject-valid' && $method === 'POST') {
    fixture_json(['error' => 'Rejected'], 400);
    return;
}

if ($path === '/random/allow-drift' && $method === 'GET') {
    fixture_json(['ok' => true]);
    return;
}

if ($path === '/random-broken/resources' && $method === 'POST') {
    fixture_json(['id' => 'res_1', 'name' => 'broken availability'], 201, ['Location' => '/random-broken/resources/res_1']);
    return;
}

if (preg_match('#^/random-broken/resources/([^/]+)$#', $path) && $method === 'GET') {
    fixture_json(['error' => 'Not found'], 404);
    return;
}

if ($path === '/random-broken/sticky-resources' && $method === 'POST') {
    $store = fixture_store($randomStorePath);
    $store['sticky']['sticky_1'] = ['id' => 'sticky_1', 'name' => 'sticky resource'];
    fixture_save_store($randomStorePath, $store);
    fixture_json($store['sticky']['sticky_1'], 201, ['Location' => '/random-broken/sticky-resources/sticky_1']);
    return;
}

if (preg_match('#^/random-broken/sticky-resources/([^/]+)$#', $path, $match)) {
    if ($method === 'GET') {
        fixture_json(['id' => $match[1], 'name' => 'sticky resource']);
        return;
    }
    if ($method === 'DELETE') {
        fixture_json(null, 204);
        return;
    }
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
