<?php
// Определяем базовый путь
define('BASE_PATH', dirname(__DIR__));

// Подключаем функции
require_once BASE_PATH . '/admin/includes/functions.php';

// Проверяем авторизацию
requireAdminAuth();

// Проверяем права доступа
if (!hasPermission('admin')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

$conn = getDBConnection();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Обработка добавления/редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = cleanInput($_POST['title'] ?? '');
    $url = cleanInput($_POST['url'] ?? '');
    $parentId = intval($_POST['parent_id'] ?? 0);
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if ($action === 'add' || ($action === 'edit' && $id)) {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO menu_items (title, url, parent_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiii", $title, $url, $parentId, $sortOrder, $isActive);
            
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                logAdminAction('menu_add', "Добавлен пункт меню: $title");
                redirectWithNotification('menu.php', 'Пункт меню успешно добавлен', 'success');
            } else {
                redirectWithNotification('menu.php?action=add', 'Ошибка при добавлении пункта меню', 'error');
            }
        } else {
            $stmt = $conn->prepare("UPDATE menu_items SET title = ?, url = ?, parent_id = ?, sort_order = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssiiii", $title, $url, $parentId, $sortOrder, $isActive, $id);
            
            if ($stmt->execute()) {
                logAdminAction('menu_edit', "Отредактирован пункт меню: $title");
                redirectWithNotification('menu.php', 'Пункт меню успешно обновлен', 'success');
            } else {
                redirectWithNotification('menu.php?action=edit&id=' . $id, 'Ошибка при обновлении пункта меню', 'error');
            }
        }
    }
}

// Обработка удаления
if ($action === 'delete' && $id) {
    // Проверяем, есть ли подпункты
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM menu_items WHERE parent_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $count = $checkResult->fetch_assoc()['count'];
    
    if ($count > 0) {
        redirectWithNotification('menu.php', 'Нельзя удалить пункт меню, у которого есть подпункты', 'error');
    }
    
    // Получаем информацию о пункте меню для лога
    $stmt = $conn->prepare("SELECT title FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $menuItem = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAdminAction('menu_delete', "Удален пункт меню: " . ($menuItem['title'] ?? 'ID ' . $id));
        redirectWithNotification('menu.php', 'Пункт меню успешно удален', 'success');
    } else {
        redirectWithNotification('menu.php', 'Ошибка при удалении пункта меню', 'error');
    }
}

// Получаем все пункты меню
$menuItems = $conn->query("SELECT * FROM menu_items ORDER BY parent_id, sort_order, title")->fetch_all(MYSQLI_ASSOC);

// Функция для построения дерева меню
function buildMenuTree($items, $parentId = 0) {
    $tree = [];
    
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $children = buildMenuTree($items, $item['id']);
            if ($children) {
                $item['children'] = $children;
            }
            $tree[] = $item;
        }
    }
    
    return $tree;
}

// Строим дерево меню
$menuTree = buildMenuTree($menuItems);

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
                <?php echo $action === 'add' ? 'Добавить пункт меню' : ($action === 'edit' ? 'Редактировать пункт меню' : 'Управление меню'); ?>
            </h1>
        </div>
        
        <div class="header-right">
            <div class="user-menu">
                <div class="user-avatar">
                    <?php $admin = getCurrentAdmin(); echo strtoupper(substr($admin['username'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></h4>
                    <span>Администратор</span>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Контент -->
    <div class="content-container">
        <?php if ($action === 'list'): ?>
        <!-- Список пунктов меню -->
        <div class="page-header">
            <div class="header-actions">
                <a href="menu.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить пункт меню
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Структура меню</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>URL</th>
                                <th>Родитель</th>
                                <th>Сортировка</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            function renderMenuRows($items, $level = 0) {
                                foreach ($items as $item) {
                                    $paddingLeft = $level * 20;
                                    ?>
                                    <tr>
                                        <td><?php echo $item['id']; ?></td>
                                        <td style="padding-left: <?php echo $paddingLeft; ?>px;">
                                            <?php if ($level > 0): ?>
                                            <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['url']); ?></td>
                                        <td>
                                            <?php if ($item['parent_id'] > 0): ?>
                                            <span class="badge badge-secondary">Подпункт</span>
                                            <?php else: ?>
                                            <span class="badge badge-primary">Основной</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $item['sort_order']; ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $item['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $item['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="menu.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="menu.php?action=delete&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-delete" 
                                                   onclick="return confirm('Удалить этот пункт меню?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                    if (isset($item['children'])) {
                                        renderMenuRows($item['children'], $level + 1);
                                    }
                                }
                            }
                            
                            renderMenuRows($menuTree);
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Форма добавления/редактирования -->
        <?php
        $menuItem = [];
        if ($action === 'edit' && $id) {
            $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $menuItem = $result->fetch_assoc();
            
            if (!$menuItem) {
                redirectWithNotification('menu.php', 'Пункт меню не найден', 'error');
            }
        }
        
        // Получаем основные пункты меню для выбора родителя
        $mainMenuItems = $conn->query("SELECT id, title FROM menu_items WHERE parent_id = 0 AND id != " . intval($id) . " ORDER BY title")->fetch_all(MYSQLI_ASSOC);
        ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Добавить новый пункт меню' : 'Редактировать пункт меню'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Название пункта меню *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($menuItem['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="url">URL *</label>
                        <input type="text" id="url" name="url" value="<?php echo htmlspecialchars($menuItem['url'] ?? ''); ?>" required>
                        <small>Пример: /ntc-kumir/page.php или # для заголовка</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="parent_id">Родительский пункт</label>
                            <select id="parent_id" name="parent_id">
                                <option value="0">Основной пункт меню</option>
                                <?php foreach ($mainMenuItems as $item): ?>
                                <option value="<?php echo $item['id']; ?>" <?php echo ($menuItem['parent_id'] ?? 0) == $item['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($item['title']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="sort_order">Порядок сортировки</label>
                            <input type="number" id="sort_order" name="sort_order" value="<?php echo $menuItem['sort_order'] ?? 0; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($menuItem['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <span>Активный пункт меню</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="menu.php" class="btn btn-secondary">Отмена</a>
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