<?php
require_once __DIR__. '/includes/functions.php';

$conn = getDBConnection();
requireAdminAuth($conn);
$admin = getCurrentAdmin($conn);

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    $fullName = cleanInput($_POST['full_name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    
    if (isEmailTaken($conn, $email, $admin['id'])) {
        redirectWithNotification('profile.php', 'Пользователь с таким email уже существует', 'error');
    }
    
    if (updateAdminProfile($conn, $admin['id'], $fullName, $email)) {
        logAdminAction($conn, 'profile_update', 'Обновлен профиль администратора');
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
    
    if (!password_verify($currentPassword, $admin['password_hash'])) {
        redirectWithNotification('profile.php', 'Неверный текущий пароль', 'error');
    }
    
    if ($newPassword !== $confirmPassword || strlen($newPassword) < 6) {
        redirectWithNotification('profile.php', 'Ошибка в новом пароле или его подтверждении', 'error');
    }
    
    if (updateAdminPassword($conn, $admin['id'], $newPassword)) {
        logAdminAction($conn, 'password_change', 'Изменен пароль администратора');
        redirectWithNotification('profile.php', 'Пароль успешно изменен', 'success');
    }
}

// Получаем статистику через одну функцию
$stats_admin = getAdminStats($conn, $admin['id']);

// Подключаем шапку и меню...
require_once __DIR__. '/includes/header.php';
require_once __DIR__. '/includes/menu.php';
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
        
        <?php 
            // Подключаем правую шапку
            require_once __DIR__. '/includes/header-right.php';
        ?>
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
                            
                            <?php if ($stats_admin['last_login']): ?>
                            <div class="info-item">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Последний вход: <?php echo date('d.m.Y H:i', strtotime($stats_admin['last_login'])); ?></span>
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
                                <div class="stat-value"><?php echo $stats_admin['actions']; ?></div>
                                <div class="stat-label">Всего действий</div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $stats_admin['logins']; ?></div>
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
            </div>
        </div>
    </div>
</div>



<?php
// Подключаем стили
require_once __DIR__. '/includes/profile.php';

// Подключаем скрипты
require_once __DIR__. '/includes/scripts.php';

// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>