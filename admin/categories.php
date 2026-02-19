<?php
// Определяем базовый путь
define('BASE_PATH', dirname(__DIR__));

// Подключаем функции
require_once BASE_PATH . '/admin/includes/functions.php';

// Проверяем авторизацию
requireAdminAuth();

// Проверяем права доступа
if (!hasPermission('editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

$conn = getDBConnection();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Обработка добавления/редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cleanInput($_POST['name'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $imagePath = $_POST['existing_image'] ?? '';
    
    // Генерация слага
    $slug = createSlug($name);
    $counter = 1;
    $originalSlug = $slug;
    
    while (!isSlugUnique('product_categories', $slug, $id)) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    
    // Загрузка изображения
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image']);
        if ($uploadResult['success']) {
            $imagePath = $uploadResult['path'];
        }
    }
    
    if ($action === 'add' || ($action === 'edit' && $id)) {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO product_categories (name, slug, description, image_path, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $name, $slug, $description, $imagePath, $sortOrder, $isActive);
            
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                logAdminAction('category_add', "Добавлена категория: " . safeSubstr($name, 0, 50));
                redirectWithNotification('categories.php', 'Категория успешно добавлена', 'success');
            } else {
                redirectWithNotification('categories.php?action=add', 'Ошибка при добавлении категории', 'error');
            }
        } else {
            $stmt = $conn->prepare("UPDATE product_categories SET name = ?, slug = ?, description = ?, image_path = ?, sort_order = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssssiii", $name, $slug, $description, $imagePath, $sortOrder, $isActive, $id);
            
            if ($stmt->execute()) {
                logAdminAction('category_edit', "Отредактирована категория: " . safeSubstr($name, 0, 50));
                redirectWithNotification('categories.php', 'Категория успешно обновлена', 'success');
            } else {
                redirectWithNotification('categories.php?action=edit&id=' . $id, 'Ошибка при обновлении категории', 'error');
            }
        }
    }
}

// Обработка удаления
if ($action === 'delete' && $id) {
    // Проверяем, есть ли товары в категории
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $count = $checkResult->fetch_assoc()['count'];
    
    if ($count > 0) {
        redirectWithNotification('categories.php', 'Нельзя удалить категорию, в которой есть товары', 'error');
    }
    
    // Получаем информацию о категории для лога
    $stmt = $conn->prepare("SELECT name FROM product_categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAdminAction('category_delete', "Удалена категория: " . safeSubstr($category['name'] ?? '', 0, 50));
        redirectWithNotification('categories.php', 'Категория успешно удалена', 'success');
    } else {
        redirectWithNotification('categories.php', 'Ошибка при удалении категории', 'error');
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
                <?php echo $action === 'add' ? 'Добавить категорию' : ($action === 'edit' ? 'Редактировать категорию' : 'Категории товаров'); ?>
            </h1>
        </div>
        
        <?php 
            // Подключаем правую шапку
            require_once BASE_PATH . '/admin/includes/header-right.php';
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
                            $categories = $conn->query("
                                SELECT pc.*, COUNT(p.id) as product_count 
                                FROM product_categories pc 
                                LEFT JOIN products p ON pc.id = p.category_id 
                                GROUP BY pc.id 
                                ORDER BY pc.sort_order, pc.name
                            ")->fetch_all(MYSQLI_ASSOC);
                            
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
            $stmt = $conn->prepare("SELECT * FROM product_categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $category = $result->fetch_assoc();
            
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
                                <div class="drop-zone" onclick="document.getElementById('cat-image').click()">
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
require_once BASE_PATH . '/admin/includes/scripts.php';

// Подключаем подвал
require_once BASE_PATH . '/admin/includes/footer.php';
?>