<?php
// session_start();

// Определяем базовый путь
define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/admin/includes/functions.php';

// Если пользователь уже авторизован, перенаправляем в админку
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($password, $admin['password_hash'])) {
                // Успешный вход
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_token'] = bin2hex(random_bytes(32));
                
                // Обновляем время последнего входа
                $updateStmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $admin['id']);
                $updateStmt->execute();
                
                // Логируем вход
                logAdminAction('login', 'Успешный вход в систему', $admin['id']);
                
                header('Location: index.php');
                exit();
            } else {
                $error = 'Неверный пароль';
            }
        } else {
            $error = 'Пользователь не найден';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель - <?php echo getSetting('site_title'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-lock"></i>
            </div>
            <h1>Админ-панель</h1>
            <p><?php echo getSetting('site_title'); ?></p>
        </div>
        
        <?php if ($error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Имя пользователя</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Войти
            </button>
        </form>
        
        <div class="login-footer">
            <p>© <?php echo date('Y'); ?> <?php echo getSetting('site_title'); ?></p>
            <p>Вернуться на <a href="../">главную</a></p>
        </div>
    </div>
    
    <script>
        // Фокус на поле ввода при загрузке
        document.getElementById('username').focus();
    </script>
</body>
</html>