<?php
require_once __DIR__. '/includes/functions.php';

$conn = getDBConnection();
requireAdminAuth($conn);

if (!hasPermission($conn, 'editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = (int)($_GET['id'] ?? 0);

// Обработка POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title'       => cleanInput($_POST['title'] ?? ''),
        'description' => cleanInput($_POST['description'] ?? ''),
        'color'       => cleanInput($_POST['color'] ?? '#007bff'),
        'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        'image_path'  => $_POST['existing_image'] ?? ''
    ];

    // Загрузка изображения
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image']); // Используем твою функцию из news
        if ($uploadResult['success']) $data['image_path'] = $uploadResult['path'];
    }

    if ($action === 'add') {
        if (addCard($conn, $data)) {
            logAdminAction($conn, 'card_add', "Добавлена карточка: {$data['title']}");
            redirectWithNotification('cards.php', 'Карточка добавлена', 'success');
        }
    } elseif ($action === 'edit' && $id) {
        if (updateCard($conn, $id, $data)) {
            logAdminAction($conn, 'card_edit', "Изменена карточка ID: $id");
            redirectWithNotification('cards.php', 'Изменения сохранены', 'success');
        }
    }
}

if ($action === 'delete' && $id) {
    if (deleteCard($conn, $id)) {
        logAdminAction($conn, 'card_delete', "Удалена карточка ID: $id");
        redirectWithNotification('cards.php', 'Карточка удалена', 'success');
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
                <?php echo $action === 'add' ? 'Новая карточка' : ($action === 'edit' ? 'Редактирование карточки' : 'Карточки (продукты)'); ?>
            </h1>
        </div>
        <?php require_once __DIR__. '/includes/header-right.php'; ?>
    </header>
    
    <div class="content-container">
        <?php if ($action === 'list'): ?>
        <div class="page-header">
            <div class="header-actions">
                <a href="cards.php?action=add" class="btn btn-primary"><i class="fas fa-plus"></i> Добавить карточку</a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Сорт.</th>
                                <th>Иконка</th>
                                <th>Заголовок</th>
                                <th>Цвет</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pagination = getPagination($conn, 'cards', 20);
                            $cards = getCardsList($conn, $pagination['perPage'], $pagination['offset']);
                            foreach ($cards as $row): 
                            ?>
                            <tr>
                                <td><?php echo $row['sort_order']; ?></td>
                                <td>
                                    <?php if($row['image_path']): ?>
                                        <img src="../<?php echo $row['image_path']; ?>" alt="" style="width: 40px; height: 40px; object-fit: contain;">
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                                <td>
                                    <span style="display:inline-block; width:20px; height:20px; background:<?php echo $row['color']; ?>; border-radius:3px; vertical-align:middle;"></span>
                                    <code><?php echo $row['color']; ?></code>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $row['is_active'] ? 'published' : 'draft'; ?>">
                                        <?php echo $row['is_active'] ? 'Активно' : 'Скрыто'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="cards.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                                        <a href="cards.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Удалить карточку?')"><i class="fas fa-trash"></i></a>
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
            $item = ($action === 'edit') ? getCardById($conn, $id) : [];
        ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Заголовок карточки</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($item['title'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Описание</label>
                        <textarea name="description" rows="3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Акцентный цвет</label>
                            <input type="color" name="color" value="<?php echo $item['color'] ?? '#007bff'; ?>" style="height: 45px;">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Порядок сортировки</label>
                            <input type="number" name="sort_order" value="<?php echo $item['sort_order'] ?? 0; ?>">
                        </div>
                        <div class="form-group col-md-4" style="padding-top: 35px;">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="1" <?php echo ($item['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Активна</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Изображение/Иконка</label>
                        <div class="image-upload-container">
                            <div class="drop-zone">
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="../<?php echo $item['image_path']; ?>" class="drop-zone__thumb">
                                <?php else: ?>
                                    <span class="drop-zone__prompt">Кликните для загрузки иконки</span>
                                <?php endif; ?>
                                <input type="file" name="image" class="drop-zone__input" accept="image/*">
                                <input type="hidden" name="existing_image" value="<?php echo $item['image_path'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить</button>
                        <a href="cards.php" class="btn btn-secondary">Отмена</a>
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