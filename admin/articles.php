<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';

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
    $title = cleanInput($_POST['title'] ?? '');
    $excerpt = cleanInput($_POST['excerpt'] ?? '');
    $content = $_POST['content'] ?? '';
    $author = cleanInput($_POST['author'] ?? '');
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
    $imagePath = $_POST['existing_image'] ?? '';
    
    // Генерация слага
    $slug = createSlug($title);
    $counter = 1;
    $originalSlug = $slug;
    
    while (!isSlugUnique('articles', $slug, $id)) {
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
            $stmt = $conn->prepare("INSERT INTO articles (title, slug, excerpt, content, author, image_path, is_published, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssis", $title, $slug, $excerpt, $content, $author, $imagePath, $isPublished, $publishedAt);
            
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                logAdminAction('article_add', "Добавлена статья: $title");
                redirectWithNotification('articles.php', 'Статья успешно добавлена', 'success');
            } else {
                redirectWithNotification('articles.php?action=add', 'Ошибка при добавлении статьи', 'error');
            }
        } else {
            $stmt = $conn->prepare("UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, author = ?, image_path = ?, is_published = ?, published_at = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("ssssssisi", $title, $slug, $excerpt, $content, $author, $imagePath, $isPublished, $publishedAt, $id);
            
            if ($stmt->execute()) {
                logAdminAction('article_edit', "Отредактирована статья: $title");
                redirectWithNotification('articles.php', 'Статья успешно обновлена', 'success');
            } else {
                redirectWithNotification('articles.php?action=edit&id=' . $id, 'Ошибка при обновлении статьи', 'error');
            }
        }
    }
}

// Обработка удаления
if ($action === 'delete' && $id) {
    // Получаем информацию о статье для лога
    $stmt = $conn->prepare("SELECT title FROM articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAdminAction('article_delete', "Удалена статья: " . ($article['title'] ?? 'ID ' . $id));
        redirectWithNotification('articles.php', 'Статья успешно удалена', 'success');
    } else {
        redirectWithNotification('articles.php', 'Ошибка при удалении статьи', 'error');
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
                <?php echo $action === 'add' ? 'Добавить статью' : ($action === 'edit' ? 'Редактировать статью' : 'Статьи'); ?>
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
        <!-- Список статей -->
        <div class="page-header">
            <div class="header-actions">
                <a href="articles.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить статью
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Список статей</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Заголовок</th>
                                <th>Автор</th>
                                <th>Просмотры</th>
                                <th>Статус</th>
                                <th>Дата публикации</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pagination = getPagination('articles', 10);
                            $stmt = $conn->prepare("SELECT * FROM articles ORDER BY created_at DESC LIMIT ? OFFSET ?");
                            $stmt->bind_param("ii", $pagination['perPage'], $pagination['offset']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <a href="../article.php?slug=<?php echo $row['slug']; ?>" target="_blank">
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($row['author']); ?></td>
                                <td><?php echo $row['views']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['is_published'] ? 'published' : 'draft'; ?>">
                                        <?php echo $row['is_published'] ? 'Опубликовано' : 'Черновик'; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['published_at'] ? date('d.m.Y H:i', strtotime($row['published_at'])) : '—'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="articles.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="articles.php?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('Удалить эту статью?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php echo generatePaginationLinks($pagination); ?>
            </div>
        </div>
        
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Форма добавления/редактирования -->
        <?php
        $article = [];
        if ($action === 'edit' && $id) {
            $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $article = $result->fetch_assoc();
            
            if (!$article) {
                redirectWithNotification('articles.php', 'Статья не найдена', 'error');
            }
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Добавить новую статью' : 'Редактировать статью'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Заголовок *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($article['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">URL (слаг)</label>
                        <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($article['slug'] ?? ''); ?>" readonly>
                        <small>Генерируется автоматически из заголовка</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="excerpt">Краткое описание</label>
                        <textarea id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Содержание *</label>
                        <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($article['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="author">Автор</label>
                            <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($article['author'] ?? ''); ?>">
                        </div>
                        
                    <div class="form-group col-md-6">
                            <label for="published_at">Дата публикации</label>
                            <input type="datetime-local" id="published_at" name="published_at" class="form-control" 
                                value="<?php 
                                        // Безопасное форматирование даты для datetime-local
                                        $publishedAt = $article['published_at'] ?? '';
                                        if (!empty($publishedAt) && $publishedAt !== '0000-00-00 00:00:00' && $publishedAt !== '0000-00-00') {
                                            $timestamp = strtotime($publishedAt);
                                            if ($timestamp !== false && $timestamp > 0) {
                                                // Проверяем, что год не 0000
                                                $year = date('Y', $timestamp);
                                                if ($year != 0 && $year != '0000') {
                                                    echo date('Y-m-d\TH:i', $timestamp);
                                                } else {
                                                    echo '';
                                                }
                                            } else {
                                                echo '';
                                            }
                                        } else {
                                            echo '';
                                        }
                                ?>">
                            <small class="text-muted">Оставьте пустым для автоматической установки</small>
                        </div>
                        
                    </div>
                    
                    <div class="form-group">
                        <label>Обложка статьи</label>
                        <div class="image-upload-container">
                            <div class="drop-zone" onclick="document.getElementById('article-image').click()">
                                <?php if (!empty($article['image_path'])): ?>
                                    <img src="../<?php echo $article['image_path']; ?>" class="drop-zone__thumb" id="preview-img" alt="Обложка">
                                <?php else: ?>
                                    <span class="drop-zone__prompt">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        Перетащите обложку статьи или кликните для выбора
                                    </span>
                                <?php endif; ?>
                                <input type="file" id="article-image" name="image" class="drop-zone__input" accept="image/*">
                                <input type="hidden" name="existing_image" id="existing_image_input" value="<?php echo $article['image_path'] ?? ''; ?>">
                            </div>

                            <?php if (!empty($article['image_path'])): ?>
                                <div class="mt-2" id="remove-image-btn">
                                    <button type="button" onclick="removeArticleImage()" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i> Удалить текущую обложку
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <small class="text-muted">Рекомендуемый размер: 1200×600px (16:9), форматы: JPG, PNG, WebP, SVG</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_published" value="1" <?php echo ($article['is_published'] ?? 1) ? 'checked' : ''; ?>>
                            <span>Опубликовать статью</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="articles.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        
        <script src="assets/js/articles.js"></script>
        <?php endif; ?>
    </div>
</div>

<?php
// Подключаем скрипты
require_once __DIR__. '/includes/scripts.php';

// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>