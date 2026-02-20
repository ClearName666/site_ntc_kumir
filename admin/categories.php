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
    $name = cleanInput($_POST['name'] ?? '');
    $imagePath = $_POST['existing_image'] ?? '';
    
    // 1. Работа с изображением
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image']);
        if ($uploadResult['success']) $imagePath = $uploadResult['path'];
    }
    
    // 2. Подготовка данных
    $data = [
        'name'        => $name,
        'slug'        => generateUniqueCategorySlug($conn, $name, $id),
        'description' => cleanInput($_POST['description'] ?? ''),
        'sort_order'  => intval($_POST['sort_order'] ?? 0),
        'is_active'   => isset($_POST['is_active']) ? 1 : 0,
        'image_path'  => $imagePath
    ];
    
    if ($action === 'add') {
        if (addCategory($conn, $data)) {
            logAdminAction($conn, 'category_add', "Добавлена категория: $name");
            redirectWithNotification('categories.php', 'Успешно добавлена', 'success');
        }
    } elseif ($action === 'edit' && $id) {
        if (updateCategory($conn, $id, $data)) {
            logAdminAction($conn, 'category_edit', "Изменена категория: $name");
            redirectWithNotification('categories.php', 'Успешно обновлена', 'success');
        }
    }
}

// --- ОБРАБОТКА УДАЛЕНИЯ ---
if ($action === 'delete' && $id) {
    if (getCategoryProductCount($conn, $id) > 0) {
        redirectWithNotification('categories.php', 'Нельзя удалить категорию с товарами', 'error');
    }
    
    $category = getCategoryById($conn, $id);
    
    // ВЫНОСИМ ЗАПРОС:
    if (deleteCategory($conn, $id)) {
        logAdminAction($conn, 'category_delete', "Удалена категория: " . ($category['name'] ?? $id));
        redirectWithNotification('categories.php', 'Категория успешно удалена', 'success');
    } else {
        redirectWithNotification('categories.php', 'Ошибка при удалении', 'error');
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
                <?php echo $action === 'add' ? 'Добавить категорию' : ($action === 'edit' ? 'Редактировать категорию' : 'Категории товаров'); ?>
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
        <!-- Список категорий -->
        <div class="page-header">
            <div class="header-actions">
                <a href="categories.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить категорию
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Список категорий</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Изображение</th>
                                <th>Название</th>
                                <th>Описание</th>
                                <th>Кол-во товаров</th>
                                <th>Сортировка</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // ПОЛУЧАЕМ ДАННЫЕ ЧЕРЕЗ ФУНКЦИЮ:
                            $categories = getAllCategoriesWithCount($conn);
                            
                            foreach ($categories as $category):
                            ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td>
                                    <?php if ($category['image_path']): ?>
                                    <img src="../<?php echo $category['image_path']; ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    <small class="text-muted d-block"><?php echo $category['slug']; ?></small>
                                </td>
                                <td>
                                    <?php 
                                    // Используем safeSubstr вместо mb_substr
                                    if (!empty($category['description'])) {
                                        echo htmlspecialchars(safeSubstr($category['description'], 0, 50)) . '...';
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $category['product_count']; ?></td>
                                <td><?php echo $category['sort_order']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $category['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $category['is_active'] ? 'Активна' : 'Неактивна'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="categories.php?action=delete&id=<?php echo $category['id']; ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('Удалить эту категорию?')">
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
        
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Форма добавления/редактирования -->
        <?php
        $category = [];
        if ($action === 'edit' && $id) {
            // ИСПОЛЬЗУЕМ ФУНКЦИЮ:
            $category = getCategoryById($conn, $id);
            
            if (!$category) {
                redirectWithNotification('categories.php', 'Категория не найдена', 'error');
            }
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Добавить новую категорию' : 'Редактировать категорию'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Название категории *</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">URL (слаг)</label>
                        <input type="text" id="slug" name="slug" class="form-control" value="<?php echo htmlspecialchars($category['slug'] ?? ''); ?>" readonly>
                        <small class="text-muted">Генерируется автоматически из названия</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Описание</label>
                        <textarea id="description" name="description" rows="3" class="form-control"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="sort_order">Порядок сортировки</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?php echo $category['sort_order'] ?? 0; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Изображение категории</label>
                            <div class="image-upload-container">
                                <div class="drop-zone" >  <!-- onclick="document.getElementById('cat-image').click()" -->
                                    <?php if (!empty($category['image_path'])): ?>
                                        <img src="../<?php echo $category['image_path']; ?>" class="drop-zone__thumb" id="preview-img">
                                    <?php else: ?>
                                        <span class="drop-zone__prompt">
                                            <i class="fas fa-image"></i>
                                            Кликните или перетащите иконку категории
                                        </span>
                                    <?php endif; ?>
                                    <input type="file" id="cat-image" name="image" class="drop-zone__input" accept="image/*">
                                    <input type="hidden" name="existing_image" id="existing_image_input" value="<?php echo $category['image_path'] ?? ''; ?>">
                                </div>

                                <?php if (!empty($category['image_path'])): ?>
                                    <div class="mt-2" id="remove-image-wrapper">
                                        <button type="button" onclick="removeCategoryImage()" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times"></i> Удалить изображение
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">Рекомендуемый размер: 400×400px (квадрат)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($category['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <span>Активная категория</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="categories.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        
        <script src="assets/js/categories.js"></script>
        <?php endif; ?>
    </div>
</div>

<?php
// Подключаем скрипты
require_once __DIR__. '/includes/scripts.php';

// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>