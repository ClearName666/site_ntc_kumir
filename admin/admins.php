<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';

// подключаемся к базе 
$conn = getDBConnection();

// Проверяем авторизацию
requireAdminAuth($conn);

// Проверяем права доступа (только superadmin)
if (!hasPermission($conn, 'superadmin')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}


$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Обработка добавления/редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    // Проверка уникальности через функцию
    if (!isAdminUnique($conn, $username, $email, $id)) {
        redirectWithNotification("admins.php?action=$action&id=$id", 'Пользователь уже существует', 'error');
    }
    
    // Подготовка данных
    $data = [
        'username'  => $username,
        'email'     => $email,
        'full_name' => cleanInput($_POST['full_name'] ?? ''),
        'role'      => cleanInput($_POST['role'] ?? 'admin'),
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'password'  => $password
    ];

    if ($action === 'add') {
        if (empty($password) || $password !== $passwordConfirm) {
            redirectWithNotification('admins.php?action=add', 'Ошибка в паролях', 'error');
        }
        if (addAdmin($conn, $data)) {
            logAdminAction($conn, 'admin_add', "Добавлен: $username");
            redirectWithNotification('admins.php', 'Админ добавлен', 'success');
        }
    } else {
        if (!empty($password) && $password !== $passwordConfirm) {
            redirectWithNotification("admins.php?action=edit&id=$id", 'Пароли не совпадают', 'error');
        }
        if (updateAdmin($conn, $id, $data)) {
            logAdminAction($conn, 'admin_edit', "Обновлен: $username");
            redirectWithNotification('admins.php', 'Данные обновлены', 'success');
        }
    }
}

// Обработка удаления
if ($action === 'delete' && $id) {
    if ($id == $_SESSION['admin_id']) {
        redirectWithNotification('admins.php', 'Нельзя удалить себя', 'error');
    }
    
    $adminInfo = getAdminById($conn, $id);
    if (deleteAdmin($conn, $id)) {
        logAdminAction($conn, 'admin_delete', "Удален: " . ($adminInfo['username'] ?? $id));
        redirectWithNotification('admins.php', 'Удалено', 'success');
    }
}

// Подключаем шапку
require_once __DIR__. '/includes/header.php';

// Подключаем меню
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
            <h1 class="header-title">
                <?php echo $action === 'add' ? 'Добавить администратора' : ($action === 'edit' ? 'Редактировать администратора' : 'Администраторы'); ?>
            </h1>
        </div>
        
        <?php 
            // Подключаем правую шапку
            require_once __DIR__. '/includes/header-right.php';
        ?>
    </header>
    
    <!-- Контент -->
    <div class="content-container">
        <?php if ($action === 'list'): ?>
        <!-- Список администраторов -->
        <div class="page-header">
            <div class="header-actions">
                <a href="admins.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить администратора
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Список администраторов</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя пользователя</th>
                                <th>Email</th>
                                <th>Полное имя</th>
                                <th>Роль</th>
                                <th>Статус</th>
                                <th>Последний вход</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $admins = getAllAdmins($conn);
                            foreach ($admins as $admin):
                            ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($admin['username']); ?></strong>
                                    <?php if ($admin['id'] == $_SESSION['admin_id']): ?>
                                    <span class="badge badge-primary ml-2">Вы</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo htmlspecialchars($admin['full_name'] ?? '—'); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $admin['role']; ?>">
                                        <?php 
                                        $roleNames = [
                                            'superadmin' => 'Супер-админ',
                                            'admin' => 'Администратор',
                                            'editor' => 'Редактор'
                                        ];
                                        echo $roleNames[$admin['role']] ?? $admin['role'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $admin['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $admin['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $admin['last_login'] ? date('d.m.Y H:i', strtotime($admin['last_login'])) : '—'; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="admins.php?action=edit&id=<?php echo $admin['id']; ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                        <a href="admins.php?action=delete&id=<?php echo $admin['id']; ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('Удалить этого администратора?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Форма добавления/редактирования -->
        <?php
        $adminData = [];
        if ($action === 'edit' && $id) {
            $adminData = getAdminById($conn, $id);
            if (!$adminData) {
                redirectWithNotification('admins.php', 'Администратор не найден', 'error');
            }
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Добавить нового администратора' : 'Редактировать администратора'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="username">Имя пользователя *</label>
                            <input type="text" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($adminData['username'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($adminData['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name">Полное имя</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($adminData['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="role">Роль *</label>
                            <select id="role" name="role" required>
                                <option value="editor" <?php echo ($adminData['role'] ?? '') === 'editor' ? 'selected' : ''; ?>>Редактор</option>
                                <option value="admin" <?php echo ($adminData['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Администратор</option>
                                <option value="superadmin" <?php echo ($adminData['role'] ?? '') === 'superadmin' ? 'selected' : ''; ?>>Супер-администратор</option>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="1" <?php echo ($adminData['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Активный аккаунт</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="password"><?php echo $action === 'add' ? 'Пароль *' : 'Новый пароль'; ?></label>
                            <input type="password" id="password" name="password" <?php echo $action === 'add' ? 'required' : ''; ?>>
                            <?php if ($action === 'edit'): ?>
                            <small>Оставьте пустым, если не хотите менять пароль</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="password_confirm">Подтверждение пароля</label>
                            <input type="password" id="password_confirm" name="password_confirm" <?php echo $action === 'add' ? 'required' : ''; ?>>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="admins.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Подключаем скрипты
require_once __DIR__. '/includes/scripts.php';

// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>