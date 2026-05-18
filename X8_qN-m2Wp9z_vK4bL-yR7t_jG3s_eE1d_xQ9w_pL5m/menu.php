<?php

require_once __DIR__. '/includes/functions.php';

$conn = getDBConnection();
requireAdminAuth($conn);

if (!hasPermission($conn, 'admin')) {
    redirectWithNotification('index.php', 'Недостаточно прав', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// --- ОБРАБОТКА POST (Сохранение / Редактирование) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'title'      => cleanInput($_POST['title'] ?? ''),
        'url'        => cleanInput($_POST['url'] ?? ''),
        'parent_id'  => intval($_POST['parent_id'] ?? 0),
        'sort_order' => intval($_POST['sort_order'] ?? 0),
        'is_active'  => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if ($action === 'add') {
        if ($newId = addMenuItem($conn, $formData)) {
            logAdminAction($conn, 'menu_add', "Добавлен пункт: " . $formData['title']);
            redirectWithNotification('menu.php', 'Успешно добавлено', 'success');
        }
    } elseif ($action === 'edit' && $id) {
        if (updateMenuItem($conn, $id, $formData)) {
            logAdminAction($conn, 'menu_edit', "Изменен пункт: " . $formData['title']);
            redirectWithNotification('menu.php', 'Успешно обновлено', 'success');
        }
    }
}

// --- ОБРАБОТКА УДАЛЕНИЯ ---
if ($action === 'delete' && $id) {
    if (hasChildMenu($conn, $id)) {
        redirectWithNotification('menu.php', 'Сначала удалите подпункты!', 'error');
    }
    
    $menuItem = getMenuItemById($conn, $id);
    if (deleteMenuItem($conn, $id)) {
        logAdminAction($conn, 'menu_delete', "Удален пункт: " . ($menuItem['title'] ?? $id));
        redirectWithNotification('menu.php', 'Пункт удален', 'success');
    }
}

// --- ПОДГОТОВКА ДАННЫХ ДЛЯ ВЫВОДА ---
$allMenuItems = getAllMenuItems($conn);
$menuTree = buildMenuTree($allMenuItems);

// Если редактируем, получаем данные для формы
$menuItem = ($action === 'edit' && $id) ? getMenuItemById($conn, $id) : null;

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
            <button class="toggle-sidebar" id="toggleSidebar"  style="display: none;">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">
                <?php echo $action === 'add' ? 'Добавить пункт меню' : ($action === 'edit' ? 'Редактировать пункт меню' : 'Управление меню'); ?>
            </h1>
        </div>
        
        <div class="header-right">
            <div class="user-menu">
                <div class="user-avatar">
                    <?php $admin = getCurrentAdmin($conn); echo strtoupper(substr($admin['username'], 0, 1)); ?>
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
                    <table class="data-table responsive-table">
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
                                        <td data-label="ID"><?php echo $item['id']; ?></td>
                                        <td data-label="Название" style="padding-left: <?php echo $paddingLeft; ?>px;">
                                            <?php if ($level > 0): ?>
                                            <i class="fas fa-level-up-alt fa-rotate-90 text-muted mr-2"></i>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($item['title']); ?>
                                        </td>
                                        <td data-label="URL"><?php echo htmlspecialchars($item['url']); ?></td>
                                        <td data-label="Родитель">
                                            <?php if ($item['parent_id'] > 0): ?>
                                            <span class="badge badge-secondary">Подпункт</span>
                                            <?php else: ?>
                                            <span class="badge badge-primary">Основной</span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="Сортировка"><?php echo $item['sort_order']; ?></td>
                                        <td data-label="Статус">
                                            <span class="status-badge <?php echo $item['is_active'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $item['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                            </span>
                                        </td>
                                        <td data-label="Действия">
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
        // Получаем список родителей через функцию
        $mainMenuItems = getPotentialParents($conn, $id);
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

<script src="assets/js/miniAdminstration.js"></script>

<?php
// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>