<?php
// session_start();


require_once __DIR__. '/includes/functions.php';

// подключаемся к базе 
$conn = getDBConnection();

if (isset($_SESSION['admin_id'])) {
    $adminId = $_SESSION['admin_id'];
    
    // Логируем выход
    logAdminAction($conn, 'logout', 'Выход из системы', $adminId);
    
    // Удаляем все переменные сессии
    $_SESSION = array();
    
    // Удаляем куки сессии
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Уничтожаем сессию
    session_destroy();
}

// Перенаправляем на страницу входа
header('Location: login.php');
exit();
?>