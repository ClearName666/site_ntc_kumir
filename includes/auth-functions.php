<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function getCurrentAdmin($conn) {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

function adminLogin($conn, $username, $password) {
    
    $stmt = $conn->prepare("SELECT * FROM admins WHERE (username = ? OR email = ?) AND is_active = 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($admin = $result->fetch_assoc()) {
        if (password_verify($password, $admin['password_hash'])) {
            $updateStmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
            $updateStmt->bind_param("i", $admin['id']);
            $updateStmt->execute();
            
            logAdminAction($admin['id'], 'login', 'Успешный вход в систему');
            
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
    
    logAdminAction(null, 'failed_login', 'Неудачная попытка входа для: ' . $username);
    
    return [
        'success' => false,
        'message' => 'Неверные учетные данные'
    ];
}

function adminLogout() {
    if (isAdminLoggedIn()) {
        logAdminAction($_SESSION['admin_id'], 'logout', 'Выход из системы');
    }
    
    session_unset();
    session_destroy();
    
    return [
        'success' => true,
        'message' => 'Успешный выход'
    ];
}

function logAdminAction($conn, $action, $description = null, $adminId = null) {
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

function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        header('Location: /ntc-kumir/admin/login.php');
        exit();
    }
}

function redirectIfLoggedIn() {
    if (isAdminLoggedIn()) {
        header('Location: /ntc-kumir/admin/');
        exit();
    }
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

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

function cleanInput($data) {
    if (empty($data)) {
        return '';
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    return $data;
}
?>