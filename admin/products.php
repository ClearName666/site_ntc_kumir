<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';

// подключаемся к базе 
$conn = getDBConnection();

// Проверяем авторизацию
requireAdminAuth($conn);

// Проверяем права доступа
if (!hasPermission($conn, 'editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Получаем список категорий через функцию
$categories = getActiveCategories($conn);

// Обработка добавления/редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = intval($_POST['category_id'] ?? 0);
    $name = cleanInput($_POST['name'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $fullDescription = $_POST['full_description'] ?? '';
    $price = !empty($_POST['price']) ? floatval($_POST['price']) : null;
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $imagePath = $_POST['existing_image'] ?? '';
    
    // Подготовка спецификаций
    $specifications = [];
    if (isset($_POST['spec_key']) && isset($_POST['spec_value'])) {
        $keys = $_POST['spec_key'];
        $values = $_POST['spec_value'];
        
        for ($i = 0; $i < count($keys); $i++) {
            $key = cleanInput($keys[$i]);
            $value = cleanInput($values[$i]);
            
            if (!empty($key) && !empty($value)) {
                $specifications[$key] = $value;
            }
        }
    }
    
    $specificationsJson = json_encode($specifications, JSON_UNESCAPED_UNICODE);
    
    // Генерация слага
    $slug = createSlug($name);
    $counter = 1;
    $originalSlug = $slug;
    
    while (!isSlugUnique($conn, 'products', $slug, $id)) {
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
    
    // Собираем все данные в массив для функций
    $productData = [
        'category_id' => intval($_POST['category_id'] ?? 0),
        'name' => cleanInput($_POST['name'] ?? ''),
        'slug' => $slug, // уже сгенерированный вами ранее в коде
        'description' => cleanInput($_POST['description'] ?? ''),
        'full_description' => $_POST['full_description'] ?? '',
        'image_path' => $imagePath,
        'price' => !empty($_POST['price']) ? floatval($_POST['price']) : null,
        'specifications' => $specificationsJson,
        'is_available' => isset($_POST['is_available']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'sort_order' => intval($_POST['sort_order'] ?? 0)
    ];

    if ($action === 'add') {
        $newId = addProduct($conn, $productData);
        if ($newId) {
            logAdminAction($conn, 'product_add', "Добавлен товар: " . safeSubstr($productData['name'], 0, 50));
            redirectWithNotification('products.php', 'Товар успешно добавлен', 'success');
        }
    } elseif ($action === 'edit' && $id) {
        if (updateProduct($conn, $id, $productData)) {
            logAdminAction($conn, 'product_edit', "Отредактирован товар: " . safeSubstr($productData['name'], 0, 50));
            redirectWithNotification('products.php', 'Товар успешно обновлен', 'success');
        }
    }
}

// Обработка удаления
if ($action === 'delete' && $id) {
    $product = getProductById($conn, $id); // Используем функцию для лога
    if ($product && deleteProduct($conn, $id)) {
        logAdminAction($conn, 'product_delete', "Удален товар: " . $product['name']);
        redirectWithNotification('products.php', 'Товар успешно удален', 'success');
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
                <?php echo $action === 'add' ? 'Добавить товар' : ($action === 'edit' ? 'Редактировать товар' : 'Товары'); ?>
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
        <!-- Список товаров -->
        <div class="page-header">
            <div class="header-actions">
                <a href="products.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить товар
                </a>
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-tags"></i> Управление категориями
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Список товаров</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Изображение</th>
                                <th>Название</th>
                                <th>Категория</th>
                                <th>Цена</th>
                                <th>Наличие</th>
                                <th>Сортировка</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pagination = getPagination($conn, 'products', 10);
                            $products = getProductsList($conn, $pagination['perPage'], $pagination['offset']);
                            
                            foreach ($products as $row):
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <?php if ($row['image_path']): ?>
                                    <img src="../<?php echo $row['image_path']; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars(safeSubstr($row['description'], 0, 50)) . '...'; ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['category_name'] ?? '—'); ?></td>
                                <td>
                                    <?php if ($row['price']): ?>
                                    <?php echo number_format($row['price'], 2, '.', ' '); ?> ₽
                                    <?php else: ?>
                                    —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $row['is_available'] ? 'available' : 'not-available'; ?>">
                                        <?php echo $row['is_available'] ? 'В наличии' : 'Нет в наличии'; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['sort_order']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $row['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="products.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="products.php?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('Удалить этот товар?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
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
        
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Форма добавления/редактирования -->
        <?php
        $product = [];
        $specifications = [];
        
        if ($action === 'edit' && $id) {
            $product = getProductById($conn, $id);
            if (!$product) redirectWithNotification('products.php', 'Товар не найден', 'error');
            $specifications = json_decode($product['specifications'], true) ?? [];
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Добавить новый товар' : 'Редактировать товар'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="name">Название товара *</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="category_id">Категория *</label>
                            <select id="category_id" name="category_id" class="form-control" required>
                                <option value="">Выберите категорию</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">URL (слаг)</label>
                        <input type="text" id="slug" name="slug" class="form-control" value="<?php echo htmlspecialchars($product['slug'] ?? ''); ?>" readonly>
                        <small class="text-muted">Генерируется автоматически из названия</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Краткое описание</label>
                        <textarea id="description" name="description" rows="3" class="form-control"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="full_description">Полное описание</label>
                        <textarea id="full_description" name="full_description" rows="6" class="form-control"><?php echo htmlspecialchars($product['full_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="price">Цена (₽)</label>
                            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?php echo $product['price'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="sort_order">Порядок сортировки</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?php echo $product['sort_order'] ?? 0; ?>">
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label>Изображение товара</label>
                            <div class="image-upload-container">
                                <div class="drop-zone <?php echo !empty($product['image_path']) ? 'has-image' : ''; ?>" > <!-- onclick="document.getElementById('product-image').click()" -->
                                    
                                    <?php if (!empty($product['image_path'])): ?>
                                        <img src="../<?php echo $product['image_path']; ?>" class="drop-zone__thumb" id="preview-img">
                                    <?php else: ?>
                                        <span class="drop-zone__prompt">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <span>Перетащите фото или кликните</span>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <input type="file" id="product-image" name="image" class="drop-zone__input" accept="image/*">
                                    <input type="hidden" name="existing_image" id="existing_image_input" value="<?php echo $product['image_path'] ?? ''; ?>">
                                </div>
                                <small class="text-muted d-block mt-1">Рекомендуемый размер: 800×800px (JPG, PNG)</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Спецификации -->
                    <div class="form-group">
                        <label>Характеристики</label>
                        <div id="specifications-container">
                            <?php if (!empty($specifications)): ?>
                                <?php foreach ($specifications as $key => $value): ?>
                                <div class="specification-row row mb-2">
                                    <div class="col-md-5">
                                        <input type="text" name="spec_key[]" class="form-control" placeholder="Название характеристики" value="<?php echo htmlspecialchars($key); ?>">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" name="spec_value[]" class="form-control" placeholder="Значение" value="<?php echo htmlspecialchars($value); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger remove-spec">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="specification-row row mb-2">
                                    <div class="col-md-5">
                                        <input type="text" name="spec_key[]" class="form-control" placeholder="Название характеристики">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" name="spec_value[]" class="form-control" placeholder="Значение">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger remove-spec">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="add-spec" class="btn btn-secondary btn-sm mt-2">
                            <i class="fas fa-plus"></i> Добавить характеристику
                        </button>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_available" value="1" <?php echo ($product['is_available'] ?? 1) ? 'checked' : ''; ?>>
                                <span>В наличии</span>
                            </label>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="1" <?php echo ($product['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Активный</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="products.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

    <script src="assets/js/products.js"></script>
    <script src="assets/js/scripts.js"></script>

<?php
// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>