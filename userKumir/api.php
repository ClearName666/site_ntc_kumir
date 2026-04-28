<?php
require_once 'config.php';

// ОТКЛЮЧАЕМ ВЫВОД ОШИБОК В БРАУЗЕР
ini_set('display_errors', 0);
error_reporting(0);

// Функция логирования в файл
function logAll($message, $data = null) {
    $logFile = '/tmp/api_debug.log';
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= " - " . print_r($data, true);
    }
    file_put_contents($logFile, $log . "\n\n", FILE_APPEND);
}

logAll("=== НОВЫЙ ЗАПРОС ===");
logAll("REQUEST_METHOD", $_SERVER['REQUEST_METHOD']);
logAll("GET", $_GET);
logAll("SESSION", isset($_SESSION) ? $_SESSION : 'no session');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
logAll("ACTION", $action);

// ========== ВХОД ==========
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    logAll("LOGIN START");
    
    $rawInput = file_get_contents('php://input');
    logAll("RAW INPUT", $rawInput);
    
    $input = json_decode($rawInput, true);
    logAll("DECODED INPUT", $input);
    
    $login = $input['login'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        logAll("LOGIN ERROR: Empty credentials");
        echo json_encode(['success' => false, 'error' => 'Логин и пароль обязательны']);
        exit;
    }
    
    $url = API_BASE_URL . '/login.php';
    $postData = http_build_query(['login' => $login, 'password' => $password]);
    
    logAll("LOGIN URL", $url);
    logAll("LOGIN POST DATA", $postData);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    logAll("LOGIN CURL ERROR", $error);
    logAll("LOGIN HTTP CODE", $httpCode);
    
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    logAll("LOGIN HEADERS", $headers);
    logAll("LOGIN BODY", $body);
    
    // Не используем curl_close() если PHP 8.0+
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        curl_close($ch);
    }
    
    // Извлекаем SESSID
    preg_match('/SESSID=([a-f0-9]{32})/', $headers, $matches);
    logAll("SESSID MATCHES", $matches);
    
    if (isset($matches[1])) {
        $_SESSION['sessid'] = $matches[1];
        logAll("LOGIN SUCCESS. SESSID", $matches[1]);
        echo json_encode(['success' => true]);
    } else {
        logAll("LOGIN FAILED: No SESSID in headers");
        echo json_encode(['success' => false, 'error' => 'Неверный логин или пароль']);
    }
    exit;
}

// ========== ПОИСК ==========
if ($action === 'search' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    logAll("SEARCH START");
    
    if (!isset($_SESSION['sessid'])) {
        logAll("SEARCH ERROR: No sessid in session");
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Не авторизован', 'nodes' => []]);
        exit;
    }
    
    logAll("SEARCH SESSID", $_SESSION['sessid']);
    
    $rawInput = file_get_contents('php://input');
    logAll("SEARCH RAW INPUT", $rawInput);
    
    $input = json_decode($rawInput, true);
    logAll("SEARCH DECODED INPUT", $input);
    
    $query = $input['query'] ?? '';
    logAll("SEARCH QUERY", $query);
    
    $url = API_BASE_URL . '/modules/filter_search/action.php';
    $postData = http_build_query([
        'type' => 'nodes',
        'query' => $query,
        'resource' => 'all'
    ]);
    
    logAll("SEARCH URL", $url);
    logAll("SEARCH POST DATA", $postData);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, 'SESSID=' . $_SESSION['sessid']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    logAll("SEARCH CURL ERROR", $error);
    logAll("SEARCH HTTP CODE", $httpCode);
    logAll("SEARCH RESPONSE LENGTH", strlen($response));
    logAll("SEARCH RESPONSE", $response);
    
    if (version_compare(PHP_VERSION, '8.0', '<')) {
        curl_close($ch);
    }
    
    if ($error) {
        logAll("SEARCH ERROR: CURL error", $error);
        echo json_encode(['success' => false, 'error' => 'CURL error: ' . $error, 'nodes' => []]);
        exit;
    }
    
    if ($httpCode != 200) {
        logAll("SEARCH ERROR: HTTP code not 200", $httpCode);
        echo json_encode(['success' => false, 'error' => 'Ошибка поиска (HTTP ' . $httpCode . ')', 'nodes' => []]);
        exit;
    }
    
    if (empty($response)) {
        logAll("SEARCH ERROR: Empty response");
        echo json_encode(['success' => false, 'error' => 'Пустой ответ от сервера', 'nodes' => []]);
        exit;
    }
    
    // Пробуем парсить XML
    $cleanResponse = $response;
    if (($pos = strpos($response, '<?xml')) !== false) {
        $cleanResponse = substr($response, $pos);
    }
    
    logAll("SEARCH CLEAN RESPONSE", $cleanResponse);
    
    $xml = @simplexml_load_string($cleanResponse);
    
    if ($xml === false) {
        logAll("SEARCH ERROR: Failed to parse XML");
        echo json_encode(['success' => false, 'error' => 'Ошибка парсинга XML', 'nodes' => []]);
        exit;
    }
    
    $nodes = [];
    if (isset($xml->row)) {
        foreach ($xml->row as $row) {
            $nodes[] = [
                'id' => (string)$row->id,
                'code' => (string)$row->code,
                'data' => (string)$row->data,
                'mdmid' => (string)$row->mdmid,
                'equipid' => (string)$row->equipid,
                'resource_id' => (int)$row->resource_id,
                'full_count' => (int)$row->full_count,
                'driver' => (string)$row->driver,
                'driver_id' => (int)$row->driver_id,
                'enabled' => (int)$row->enabled
            ];
        }
    }
    
    logAll("SEARCH SUCCESS: Found nodes", count($nodes));
    echo json_encode(['success' => true, 'nodes' => $nodes]);
    exit;
}

// ========== ВЫХОД ==========
if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    logAll("LOGOUT START");
    
    if (isset($_SESSION['sessid'])) {
        logAll("LOGOUT SESSID", $_SESSION['sessid']);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, API_BASE_URL . '/logout.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, 'SESSID=' . $_SESSION['sessid']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            curl_close($ch);
        }
    }
    
    session_destroy();
    logAll("LOGOUT SUCCESS");
    echo json_encode(['success' => true]);
    exit;
}

logAll("UNKNOWN ACTION", $action);
http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
?>