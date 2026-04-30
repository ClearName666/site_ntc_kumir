<?php
require_once __DIR__. '/includes/functions.php';

$conn = getDBConnection();
requireAdminAuth($conn);

if (!hasPermission($conn, 'editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// Обработка POST (Добавление и Редактирование)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title'       => cleanInput($_POST['title'] ?? ''),
        'value'       => cleanInput($_POST['value'] ?? ''),
        'description' => cleanInput($_POST['description'] ?? ''),
        'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        'is_active'   => isset($_POST['is_active']) ? 1 : 0
    ];

    if ($action === 'add') {
        if (addStat($conn, $data)) {
            logAdminAction($conn, 'stat_add', "Добавлена статистика: {$data['title']}");
            redirectWithNotification('statistics.php', 'Данные успешно добавлены', 'success');
        }
    } elseif ($action === 'edit' && $id) {
        if (updateStat($conn, $id, $data)) {
            logAdminAction($conn, 'stat_edit', "Изменена статистика ID: $id");
            redirectWithNotification('statistics.php', 'Данные успешно обновлены', 'success');
        }
    }
}

// Удаление
if ($action === 'delete' && $id) {
    if (deleteStat($conn, $id)) {
        logAdminAction($conn, 'stat_delete', "Удалена статистика ID: $id");
        redirectWithNotification('statistics.php', 'Запись удалена', 'success');
    }
}

require_once __DIR__. '/includes/header.php';
require_once __DIR__. '/includes/menu.php';
?>

<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars" style="display: none;"></i></button>
            <h1 class="header-title">
                <?php echo $action === 'add' ? 'Добавить показатель' : ($action === 'edit' ? 'Редактировать показатель' : 'Статистика'); ?>
            </h1>
        </div>
        <?php require_once __DIR__. '/includes/header-right.php'; ?>
    </header>
    
    <div class="content-container">
        <?php if ($action === 'list'): ?>
        <div class="page-header">
            <div class="header-actions">
                <a href="statistics.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить показатель
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header"><h3>Показатели на сайте</h3></div>
            <div class="card-body">
            <div class="table-responsive">
                <table class="data-table responsive-table">
                    <thead>
                        <tr>
                            <th>Сорт.</th>
                            <th>Заголовок</th>
                            <th>Значение</th>
                            <th>Описание</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pagination = getPagination($conn, 'statistics', 20);
                        $stats = getStatsList($conn, $pagination['perPage'], $pagination['offset']);
                        foreach ($stats as $row): 
                        ?>
                        <tr>
                            <td data-label="Сорт."><?php echo $row['sort_order']; ?></td>
                            <td data-label="Заголовок"><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td data-label="Значение"><?php echo htmlspecialchars($row['value']); ?></td>
                            <td data-label="Описание"><small><?php echo htmlspecialchars($row['description']); ?></small></td>
                            <td data-label="Статус">
                                <span class="status-badge <?php echo $row['is_active'] ? 'published' : 'draft'; ?>">
                                    <?php echo $row['is_active'] ? 'Активно' : 'Скрыто'; ?>
                                </span>
                            </td>
                            <td data-label="Действия">
                                <div class="action-buttons">
                                    <a href="statistics.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                                    <a href="statistics.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-delete"><i class="fas fa-trash"></i></a>
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
            $item = ($action === 'edit') ? getStatById($conn, $id) : [];
        ?>
        <div class="card">
            <div class="card-header"><h3>Параметры</h3></div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Заголовок (например: "лет разработки")</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Значение (например: "15 000")</label>
                        <input type="text" name="value" value="<?php echo htmlspecialchars($item['value'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Описание</label>
                        <textarea name="description" rows="2"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Порядок сортировки</label>
                            <input type="number" name="sort_order" value="<?php echo $item['sort_order'] ?? 0; ?>">
                        </div>
                        <div class="form-group col-md-6" style="padding-top: 30px;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="1" <?php echo ($item['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Показывать на сайте</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить</button>
                        <a href="statistics.php" class="btn btn-secondary">Отмена</a>
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