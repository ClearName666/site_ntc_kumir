<?php
require_once __DIR__. '/includes/functions.php';

$conn = getDBConnection();
requireAdminAuth($conn);

if (!hasPermission($conn, 'editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// --- ОБРАБОТКА POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'title'       => cleanInput($_POST['title'] ?? ''),
        'description' => cleanInput($_POST['description'] ?? ''),
        'sort_order'  => intval($_POST['sort_order'] ?? 0),
        'is_active'   => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if ($action === 'add') {
        if (addFeature($conn, $formData)) {
            logAdminAction($conn, 'feature_add', "Добавлено преимущество: " . $formData['title']);
            redirectWithNotification('features.php', 'Запись добавлена', 'success');
        }
    } elseif ($action === 'edit' && $id) {
        if (updateFeature($conn, $id, $formData)) {
            logAdminAction($conn, 'feature_edit', "Изменена запись ID: $id");
            redirectWithNotification('features.php', 'Запись обновлена', 'success');
        }
    }
}

// --- УДАЛЕНИЕ ---
if ($action === 'delete' && $id) {
    $item = getFeatureById($conn, $id);
    if (deleteFeature($conn, $id)) {
        logAdminAction($conn, 'feature_delete', "Удалено: " . ($item['title'] ?? $id));
        redirectWithNotification('features.php', 'Запись удалена', 'success');
    }
}

require_once __DIR__. '/includes/header.php';
require_once __DIR__. '/includes/menu.php';
?>

<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
            <h1 class="header-title">
                <?php echo $action === 'add' ? 'Новое преимущество' : ($action === 'edit' ? 'Редактирование' : 'Преимущества (Features)'); ?>
            </h1>
        </div>
        <?php require_once __DIR__. '/includes/header-right.php'; ?>
    </header>
    
    <div class="content-container">
        <?php if ($action === 'list'): ?>
        <div class="page-header">
            <div class="header-actions">
                <a href="features.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить запись
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Заголовок</th>
                                <th>Порядок</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pagination = getPagination($conn, 'features', 15);
                            $features = getAdminFeaturesList($conn, $pagination['perPage'], $pagination['offset']);
                            foreach ($features as $row):
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                                <td><?php echo $row['sort_order']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $row['is_active'] ? 'Активен' : 'Скрыт'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="features.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                                        <a href="features.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-delete"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php echo generatePaginationLinks($pagination); ?>
            </div>
        </div>
        
        <?php elseif ($action === 'add' || $action === 'edit'): 
            if ($action === 'edit' && $id) {
                $feature = getFeatureById($conn, $id);
                if (!$feature) redirectWithNotification('features.php', 'Не найдено', 'error');
            }
        ?>
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label>Заголовок *</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($feature['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Описание</label>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($feature['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Порядок сортировки</label>
                            <input type="number" name="sort_order" value="<?php echo $feature['sort_order'] ?? 0; ?>">
                        </div>
                        <div class="form-group col-md-6" style="padding-top: 35px;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" <?php echo ($feature['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Активен (отображать на сайте)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить</button>
                        <a href="features.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/miniAdminstration.js"></script>

<?php 
require_once __DIR__. '/includes/footer.php'; 
?>

