<?php
// Подключаем database.php
require_once __DIR__ . '/../config/database.php';

// Старт сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функция для проверки авторизации
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Функция для получения текущего администратора
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Функция для логина администратора
function adminLogin($username, $password) {
    $conn = getDBConnection();
    
    // Ищем администратора по username или email
    $stmt = $conn->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) AND is_active = 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($admin = $result->fetch_assoc()) {
        // Проверяем пароль
        if (password_verify($password, $admin['password_hash'])) {
            // Обновляем время последнего входа
            $updateStmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $admin['id']);
            $updateStmt->execute();
            
            // Логируем вход
            logAdminAction($admin['id'], 'login', 'Успешный вход в систему');
            
            // Устанавливаем сессию
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            
            return [
                'success' => true,
                'message' => 'Успешный вход',
                'admin' => $admin
            ];
        }
    }
    
    // Логируем неудачную попытку входа
    logAdminAction(null, 'failed_login', 'Неудачная попытка входа для: ' . $username);
    
    return [
        'success' => false,
        'message' => 'Неверные учетные данные'
    ];
}

// Функция для выхода
function adminLogout() {
    if (isAdminLoggedIn()) {
        logAdminAction($_SESSION['admin_id'], 'logout', 'Выход из системы');
    }
    
    // Уничтожаем сессию
    session_unset();
    session_destroy();
    
    return [
        'success' => true,
        'message' => 'Успешный выход'
    ];
}

// Функция для логирования действий
function logAdminAction($action, $description = null, $adminId = null) {
    $conn = getDBConnection();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    if ($adminId === null && isset($_SESSION['admin_id'])) {
        $adminId = $_SESSION['admin_id'];
    }

    $stmt = $conn->prepare("
        INSERT INTO admin_logs (admin_id, action, description, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $adminId, $action, $description, $ip);
    $stmt->execute();
}

// Функция для перенаправления если не авторизован
function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: /ntc-kumir/admin/login.php');
        exit();
    }
}

// Функция для перенаправления если уже авторизован
function redirectIfLoggedIn() {
    if (isAdminLoggedIn()) {
        header('Location: /ntc-kumir/admin/');
        exit();
    }
}

// Функция для хеширования пароля
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Функция для проверки сложности пароля
function validatePassword($password) {
    if (strlen($password) < 8) {
        return 'Пароль должен содержать минимум 8 символов';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return 'Пароль должен содержать хотя бы одну заглавную букву';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return 'Пароль должен содержать хотя бы одну строчную букву';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return 'Пароль должен содержать хотя бы одну цифру';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return 'Пароль должен содержать хотя бы один специальный символ';
    }
    
    return true;
}

/**
 * Очистка пользовательского ввода от потенциально опасных символов
 */
function cleanInput($data) {
    if (empty($data)) {
        return '';
    }
    
    // Удаляем лишние пробелы
    $data = trim($data);
    // Удаляем обратные слеши
    $data = stripslashes($data);
    // Преобразуем специальные символы в HTML-сущности
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    return $data;
}
?>