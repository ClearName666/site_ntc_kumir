<?php
// Определяем базовый путь
define('BASE_PATH', dirname(__DIR__));

// Подключаем функции
require_once BASE_PATH . '/admin/includes/functions.php';

// Проверяем авторизацию
requireAdminAuth();

$conn = getDBConnection();
$admin = getCurrentAdmin();

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = cleanInput($_POST['full_name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    
    // Проверка уникальности email
    $checkStmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    $checkStmt->bind_param("si", $email, $admin['id']);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        redirectWithNotification('profile.php', 'Пользователь с таким email уже существует', 'error');
    }
    
    // Обновление данных
    $stmt = $conn->prepare("UPDATE admins SET full_name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $fullName, $email, $admin['id']);
    
    if ($stmt->execute()) {
        logAdminAction('profile_update', 'Обновлен профиль администратора');
        redirectWithNotification('profile.php', 'Профиль успешно обновлен', 'success');
    } else {
        redirectWithNotification('profile.php', 'Ошибка при обновлении профиля', 'error');
    }
}

// Обработка смены пароля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Проверка текущего пароля
    if (!password_verify($currentPassword, $admin['password_hash'])) {
        redirectWithNotification('profile.php', 'Неверный текущий пароль', 'error');
    }
    
    if ($newPassword !== $confirmPassword) {
        redirectWithNotification('profile.php', 'Новые пароли не совпадают', 'error');
    }
    
    if (strlen($newPassword) < 6) {
        redirectWithNotification('profile.php', 'Пароль должен быть не менее 6 символов', 'error');
    }
    
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $newPasswordHash, $admin['id']);
    
    if ($stmt->execute()) {
        logAdminAction('password_change', 'Изменен пароль администратора');
        redirectWithNotification('profile.php', 'Пароль успешно изменен', 'success');
    } else {
        redirectWithNotification('profile.php', 'Ошибка при изменении пароля', 'error');
    }
}

// Получаем статистику администратора
$stats = [
    'logins' => $conn->query("SELECT COUNT(*) as count FROM admin_logs WHERE admin_id = {$admin['id']} AND action LIKE '%login%'")->fetch_assoc()['count'],
    'actions' => $conn->query("SELECT COUNT(*) as count FROM admin_logs WHERE admin_id = {$admin['id']}")->fetch_assoc()['count'],
    'last_login' => $conn->query("SELECT created_at FROM admin_logs WHERE admin_id = {$admin['id']} AND action = 'login' ORDER BY created_at DESC LIMIT 1")->fetch_assoc()['created_at'] ?? null,
];

// Подключаем шапку
require_once BASE_PATH . '/admin/includes/header.php';

// Подключаем меню
require_once BASE_PATH . '/admin/includes/menu.php';
?>

<!-- Основной контент -->
<div class="main-content">
    <!-- Шапка -->
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">Мой профиль</h1>
        </div>
        
        <div class="header-right">
            <div class="user-menu">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></h4>
                    <span><?php echo $admin['role'] === 'superadmin' ? 'Супер-администратор' : 'Администратор'; ?></span>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Контент -->
    <div class="content-container">
        <div class="row">
            <div class="col-md-4">
                <!-- Информация о профиле -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-circle"></i> Информация о профиле</h3>
                    </div>
                    <div class="card-body text-center">
                        <div class="profile-avatar mb-3">
                            <div class="avatar-large">
                                <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                            </div>
                        </div>
                        
                        <h4><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></h4>
                        <p class="text-muted">@<?php echo htmlspecialchars($admin['username']); ?></p>
                        
                        <div class="profile-info mt-4">
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($admin['email']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-user-tag"></i>
                                <span>
                                    <?php 
                                    $roleNames = [
                                        'superadmin' => 'Супер-администратор',
                                        'admin' => 'Администратор',
                                        'editor' => 'Редактор'
                                    ];
                                    echo $roleNames[$admin['role']] ?? $admin['role'];
                                    ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Регистрация: <?php echo date('d.m.Y', strtotime($admin['created_at'])); ?></span>
                            </div>
                            
                            <?php if ($stats['last_login']): ?>
                            <div class="info-item">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Последний вход: <?php echo date('d.m.Y H:i', strtotime($stats['last_login'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Статистика -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Статистика</h3>
                    </div>
                    <div class="card-body">
                        <div class="stats-list">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $stats['actions']; ?></div>
                                <div class="stat-label">Всего действий</div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $stats['logins']; ?></div>
                                <div class="stat-label">Входов в систему</div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-value">
                                    <?php echo $admin['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                </div>
                                <div class="stat-label">Статус аккаунта</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Редактирование профиля -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-edit"></i> Редактирование профиля</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username">Имя пользователя</label>
                                <input type="text" id="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($admin['username']); ?>" disabled>
                                <small class="text-muted">Имя пользователя нельзя изменить</small>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="full_name">Полное имя</label>
                                    <input type="text" id="full_name" name="full_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Роль</label>
                                <input type="text" id="role" class="form-control" 
                                       value="<?php 
                                       $roleNames = [
                                           'superadmin' => 'Супер-администратор',
                                           'admin' => 'Администратор',
                                           'editor' => 'Редактор'
                                       ];
                                       echo $roleNames[$admin['role']] ?? $admin['role'];
                                       ?>" disabled>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Сохранить изменения
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Смена пароля -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3><i class="fas fa-key"></i> Смена пароля</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="current_password">Текущий пароль *</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="new_password">Новый пароль *</label>
                                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                                    <small class="text-muted">Минимум 6 символов</small>
                                </div>
                                
                                <div class="form-group col-md-6">
                                    <label for="confirm_password">Подтверждение пароля *</label>
                                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Сменить пароль
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<?php
// Подключаем стили
require_once BASE_PATH . '/admin/includes/profile.php';

// Подключаем скрипты
require_once BASE_PATH . '/admin/includes/scripts.php';

// Подключаем подвал
require_once BASE_PATH . '/admin/includes/footer.php';
?>