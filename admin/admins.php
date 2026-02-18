<?php
// Определяем базовый путь
define('BASE_PATH', dirname(__DIR__));

// Подключаем функции
require_once BASE_PATH . '/admin/includes/functions.php';

// Проверяем авторизацию
requireAdminAuth();

// Проверяем права доступа (только superadmin)
if (!hasPermission('superadmin')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

$conn = getDBConnection();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Обработка добавления/редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $fullName = cleanInput($_POST['full_name'] ?? '');
    $role = cleanInput($_POST['role'] ?? 'admin');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Проверка уникальности username и email
    $checkStmt = $conn->prepare("SELECT id FROM admins WHERE (username = ? OR email = ?) AND id != ?");
    $checkStmt->bind_param("ssi", $username, $email, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        redirectWithNotification('admins.php?action=' . $action . ($id ? '&id=' . $id : ''), 
                               'Пользователь с таким именем или email уже существует', 'error');
    }
    
    if ($action === 'add' || ($action === 'edit' && $id)) {
        if ($action === 'add') {
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            
            if (empty($password)) {
                redirectWithNotification('admins.php?action=add', 'Пароль обязателен', 'error');
            }
            
            if ($password !== $passwordConfirm) {
                redirectWithNotification('admins.php?action=add', 'Пароли не совпадают', 'error');
            }
            
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO admins (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $username, $email, $passwordHash, $fullName, $role, $isActive);
            
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                logAdminAction('admin_add', "Добавлен администратор: $username");
                redirectWithNotification('admins.php', 'Администратор успешно добавлен', 'success');
            } else {
                redirectWithNotification('admins.php?action=add', 'Ошибка при добавлении администратора', 'error');
            }
        } else {
            $updatePassword = !empty($_POST['password']);
            
            if ($updatePassword) {
                $password = $_POST['password'] ?? '';
                $passwordConfirm = $_POST['password_confirm'] ?? '';
                
                if ($password !== $passwordConfirm) {
                    redirectWithNotification('admins.php?action=edit&id=' . $id, 'Пароли не совпадают', 'error');
                }
                
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, password_hash = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("sssssii", $username, $email, $passwordHash, $fullName, $role, $isActive, $id);
            } else {
                $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
                $stmt->bind_param("ssssii", $username, $email, $fullName, $role, $isActive, $id);
            }
            
            if ($stmt->execute()) {
                logAdminAction('admin_edit', "Отредактирован администратор: $username");
                redirectWithNotification('admins.php', 'Администратор успешно обновлен', 'success');
            } else {
                redirectWithNotification('admins.php?action=edit&id=' . $id, 'Ошибка при обновлении администратора', 'error');
            }
        }
    }
}

// Обработка удаления
if ($action === 'delete' && $id) {
    // Нельзя удалить самого себя
    if ($id == $_SESSION['admin_id']) {
        redirectWithNotification('admins.php', 'Нельзя удалить самого себя', 'error');
    }
    
    // Получаем информацию об администраторе для лога
    $stmt = $conn->prepare("SELECT username FROM admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAdminAction('admin_delete', "Удален администратор: " . ($admin['username'] ?? 'ID ' . $id));
        redirectWithNotification('admins.php', 'Администратор успешно удален', 'success');
    } else {
        redirectWithNotification('admins.php', 'Ошибка при удалении администратора', 'error');
    }
}

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
            <h1 class="header-title">
                <?php echo $action === 'add' ? 'Добавить администратора' : ($action === 'edit' ? 'Редактировать администратора' : 'Администраторы'); ?>
            </h1>
        </div>
        
        <div class="header-right">
            <div class="user-menu">
                <div class="user-avatar">
                    <?php $admin = getCurrentAdmin(); echo strtoupper(substr($admin['username'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></h4>
                    <span>Супер-администратор</span>
                </div>
            </div>
        </div>
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
                            $admins = $conn->query("SELECT * FROM admins ORDER BY role, username")->fetch_all(MYSQLI_ASSOC);
                            
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
            $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $adminData = $result->fetch_assoc();
            
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
require_once BASE_PATH . '/admin/includes/scripts.php';

// Подключаем подвал
require_once BASE_PATH . '/admin/includes/footer.php';
?>