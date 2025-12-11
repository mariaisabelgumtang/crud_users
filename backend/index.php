<?php
require_once __DIR__ . '../src/Database.php';
require_once __DIR__ . '../src/Validator.php';
require_once __DIR__ . '../src/UserModel.php';
require_once __DIR__ . '../src/AddressModel.php';

// --- CORS: allow requests from frontend apps (adjust as needed) ---
// You can restrict Access-Control-Allow-Origin to a specific origin instead of '*'
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // No body for OPTIONS
    http_response_code(204);
    exit;
}

$db = new Database();
$pdo = $db->getConnection();
$userModel = new UserModel($pdo);
$addrModel = new AddressModel($pdo);

// Helpers
function jsonResp($data, $status = 200)
{
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($base && strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
}
$parts = array_values(array_filter(explode('/', $uri)));

// Read JSON body
$body = null;
if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
}

// Routing
if (count($parts) === 0 || $parts[0] === '') {
    jsonResp(['message' => 'PHP CRUD API running'], 200);
}

// /users
if ($parts[0] === 'users') {
    if ($method === 'GET' && count($parts) === 1) {
        $users = $userModel->all();
        jsonResp($users);
    }

    if ($method === 'POST' && count($parts) === 1) {
        $errors = Validator::validateUser($body, $pdo, true);
        if (!empty($errors)) {
            jsonResp(['errors' => $errors], 422);
        }
        try {
            $user = $userModel->create($body);
            jsonResp($user, 201);
        } catch (Exception $e) {
            jsonResp(['error' => $e->getMessage()], 500);
        }
    }

    // /users/{id}
    if (isset($parts[1]) && is_numeric($parts[1])) {
        $id = (int)$parts[1];
        if ($method === 'GET') {
            $user = $userModel->find($id);
            if (!$user) jsonResp(['error' => 'not found'], 404);
            jsonResp($user);
        }

        if (($method === 'PUT' || $method === 'PATCH')) {
            $errors = Validator::validateUser($body, $pdo, false, $id);
            if (!empty($errors)) jsonResp(['errors' => $errors], 422);
            try {
                $user = $userModel->update($id, $body);
                jsonResp($user);
            } catch (Exception $e) {
                jsonResp(['error' => $e->getMessage()], 500);
            }
        }

        if ($method === 'DELETE') {
            $ok = $userModel->delete($id);
            if ($ok) jsonResp(['deleted' => true]);
            jsonResp(['deleted' => false], 404);
        }
    }

    // /users/{id}/addresses POST
    if (isset($parts[1]) && is_numeric($parts[1]) && isset($parts[2]) && $parts[2] === 'addresses' && $method === 'POST') {
        $uid = (int)$parts[1];
        $body['user_id'] = $uid;
        $errs = Validator::validateAddress($body, $pdo);
        if (!empty($errs)) jsonResp(['errors' => $errs], 422);
        try {
            $aid = $addrModel->create($uid, $body);
            $addr = $addrModel->find($aid);
            jsonResp($addr, 201);
        } catch (Exception $e) {
            jsonResp(['error' => $e->getMessage()], 500);
        }
    }

    // /users/{id}/addresses GET
    if (isset($parts[1]) && is_numeric($parts[1]) && isset($parts[2]) && $parts[2] === 'addresses' && $method === 'GET') {
        $uid = (int)$parts[1];
        $addrs = $addrModel->listByUser($uid);
        jsonResp($addrs);
    }
}

// /addresses/{id}
if ($parts[0] === 'addresses' && isset($parts[1]) && is_numeric($parts[1])) {
    $aid = (int)$parts[1];
    if ($method === 'GET') {
        $addr = $addrModel->find($aid);
        if (!$addr) jsonResp(['error' => 'not found'], 404);
        jsonResp($addr);
    }
    if ($method === 'PUT' || $method === 'PATCH') {
        $errs = Validator::validateAddress($body, $pdo, true);
        if (!empty($errs)) jsonResp(['errors' => $errs], 422);
        try {
            $addr = $addrModel->update($aid, $body);
            jsonResp($addr);
        } catch (Exception $e) {
            jsonResp(['error' => $e->getMessage()], 500);
        }
    }
    if ($method === 'DELETE') {
        $ok = $addrModel->delete($aid);
        if ($ok) jsonResp(['deleted' => true]);
        jsonResp(['deleted' => false], 404);
    }
}

jsonResp(['error' => 'route not found'], 404);
