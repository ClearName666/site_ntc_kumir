<?php

// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', 'password');
// define('DB_NAME', 'ntc-kumir');

define('DB_HOST', 'localhost');
define('DB_USER', 'ntcuser');
define('DB_PASS', 'StrongPassword123!');
define('DB_NAME', 'ntc-kumir');

// Функция подключения к БД
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Ошибка подключения: " . $conn->connect_error);
        }
        
        // Устанавливаем кодировку UTF-8 для соединения
        if (!$conn->set_charset("utf8mb4")) {
            die("Ошибка установки кодировки utf8mb4: " . $conn->error);
        }
        
        // Устанавливаем дополнительные настройки для работы с UTF-8
        $conn->query("SET NAMES 'utf8mb4'");
        $conn->query("SET CHARACTER SET utf8mb4");
        $conn->query("SET character_set_connection = utf8mb4");
        $conn->query("SET SQL_MODE = ''");
        $conn->query("SET time_zone = '+03:00'");
    }
    
    return $conn;
}

function getSetting($key) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    
    return null;
}

function getMenuItems() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM menu_items WHERE is_active = 1 ORDER BY sort_order");
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

function getFeatures() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM features WHERE is_active = 1 ORDER BY sort_order");
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

function getCards() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM cards WHERE is_active = 1 ORDER BY sort_order");
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

function getAdvantages() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM advantages WHERE is_active = 1 ORDER BY sort_order");
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

function getStatistics() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM statistics WHERE is_active = 1 ORDER BY sort_order");
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    return $items;
}

function getContentBlock($key) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT title, content FROM content_blocks WHERE block_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return ['title' => '', 'content' => ''];
}

require_once __DIR__ . '/../includes/article-functions.php';
?>