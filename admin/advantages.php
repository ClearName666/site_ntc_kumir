<?php

require_once __DIR__. '/includes/functions.php';

$conn = getDBConnection();
requireAdminAuth($conn);

// Проверка прав (как в твоем примере)
if (!hasPermission($conn, 'editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// --- ОБРАБОТКА POST (Добавление и Редактирование) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = cleanInput($_POST['title'] ?? '');
    
    $data = [
        'title'       => $title,
        'description' => $_POST['description'] ?? '',
        'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        'icon_path'   => $_POST['existing_icon'] ?? ''
    ];

    // Загрузка иконки (аналогично загрузке фото в новостях)
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['icon']); // Используем ту же функцию загрузки
        if ($uploadResult['success']) {
            $data['icon_path'] = $uploadResult['path'];
        }
    }

    if ($action === 'add') {
        if (addAdvantage($conn, $data)) {
            logAdminAction($conn, 'advantage_add', "Добавлено преимущество: $title");
            redirectWithNotification('advantages.php', 'Преимущество успешно добавлено', 'success');
        }
    } elseif ($action === 'edit' && $id) {
        if (updateAdvantage($conn, $id, $data)) {
            logAdminAction($conn, 'advantage_edit', "Отредактировано преимущество: $title");
            redirectWithNotification('advantages.php', 'Преимущество успешно обновлено', 'success');
        }
    }
}

// --- ОБРАБОТКА УДАЛЕНИЯ ---
if ($action === 'delete' && $id) {
    $item = getAdvantageById($conn, $id);
    if ($item && deleteAdvantage($conn, $id)) {
        logAdminAction($conn, 'advantage_delete', "Удалено преимущество: " . $item['title']);
        redirectWithNotification('advantages.php', 'Преимущество успешно удалено', 'success');
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
                <?php echo $action === 'add' ? 'Добавить преимущество' : ($action === 'edit' ? 'Редактировать преимущество' : 'Преимущества'); ?>
            </h1>
        </div>
        <?php require_once __DIR__. '/includes/header-right.php'; ?>
    </header>
    
    <div class="content-container">
        <?php if ($action === 'list'): ?>
        <div class="page-header">
            <div class="header-actions">
                <a href="advantages.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить преимущество
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header"><h3>Список преимуществ</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Сорт.</th>
                                <th>Иконка</th>
                                <th>Заголовок</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $items = getAdvantagesList($conn);
                            foreach ($items as $row): 
                            ?>
                            <tr>
                                <td><?php echo $row['sort_order']; ?></td>
                                <td>
                                    <?php if ($row['icon_path']): ?>
                                        <img src="../<?php echo $row['icon_path']; ?>" width="40" alt="">
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['is_active'] ? 'published' : 'draft'; ?>">
                                        <?php echo $row['is_active'] ? 'Активно' : 'Скрыто'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="advantages.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="advantages.php?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-delete" onclick="return confirm('Удалить?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php elseif ($action === 'add' || $action === 'edit'): 
            $adv = [];
            if ($action === 'edit' && $id) {
                $adv = getAdvantageById($conn, $id);
                if (!$adv) redirectWithNotification('advantages.php', 'Не найдено', 'error');
            }
        ?>
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Новое преимущество' : 'Редактирование'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Заголовок *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($adv['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Описание</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($adv['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="sort_order">Порядок сортировки</label>
                            <input type="number" id="sort_order" name="sort_order" value="<?php echo $adv['sort_order'] ?? 0; ?>">
                        </div>
                        <div class="form-group">
                            <label>Иконка преимущества</label>
                            <div class="image-upload-container">
                                <div class="drop-zone"> 
                                    <?php if (!empty($adv['icon_path'])): ?>
                                        <img src="../<?php echo $adv['icon_path']; ?>" class="drop-zone__thumb" id="preview-img" alt="Иконка">
                                    <?php else: ?>
                                        <span class="drop-zone__prompt">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            Перетащите иконку или кликните для выбора
                                        </span>
                                    <?php endif; ?>
                                    
                                    <input type="file" id="advantage-icon" name="icon" class="drop-zone__input" accept="image/*">
                                    <input type="hidden" name="existing_icon" id="existing_image_input" value="<?php echo $adv['icon_path'] ?? ''; ?>">
                                </div>
                                
                                <small class="text-muted">Форматы: SVG (рекомендуется), PNG, WebP. Размер примерно 64×64px или 128×128px.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($adv['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <span>Отображать на сайте</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить</button>
                        <a href="advantages.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__. '/includes/scripts.php';
require_once __DIR__. '/includes/footer.php';
?>