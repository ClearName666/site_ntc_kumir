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
    $title = cleanInput($_POST['title'] ?? '');
    
    // Подготовка данных в массив
    $data = [
        'title'        => $title,
        'excerpt'      => cleanInput($_POST['excerpt'] ?? ''),
        'content'      => $_POST['content'] ?? '',
        'author'       => cleanInput($_POST['author'] ?? ''),
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
        'published_at' => !empty($_POST['published_at']) ? $_POST['published_at'] : null,
        'image_path'   => $_POST['existing_image'] ?? '',
        'slug'         => createSlug($title)
    ];

    // Уникализация слага
    $counter = 1;
    $originalSlug = $data['slug'];
    while (!isSlugUnique($conn, 'news', $data['slug'], $id)) {
        $data['slug'] = $originalSlug . '-' . $counter++;
    }

    // Загрузка фото
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['image']);
        if ($uploadResult['success']) $data['image_path'] = $uploadResult['path'];
    }

    // Вызов функций сохранения
    if ($action === 'add') {
        if (addNews($conn, $data)) {
            logAdminAction($conn, 'news_add', "Добавлена новость: $title");
            redirectWithNotification('news.php', 'Новость успешно добавлена', 'success');
        }
    } elseif ($action === 'edit' && $id) {
        if (updateNews($conn, $id, $data)) {
            logAdminAction($conn, 'news_edit', "Отредактирована новость: $title");
            redirectWithNotification('news.php', 'Новость успешно обновлена', 'success');
        }
    }
}

// Обработка удаления
if ($action === 'delete' && $id) {
    $news = getNewsById($conn, $id);
    if ($news && deleteNews($conn, $id)) {
        logAdminAction($conn, 'news_delete', "Удалена новость: " . $news['title']);
        redirectWithNotification('news.php', 'Новость успешно удалена', 'success');
    }
}

// Подключаем шапку
require_once __DIR__. '/includes/header.php';

// Подключаем меню
require_once __DIR__. '/includes/menu.php';
?>

<link rel="stylesheet" href="assets/css/redactor.css">

<!-- Основной контент -->
<div class="main-content">
    <!-- Шапка -->
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">
                <?php echo $action === 'add' ? 'Добавить новость' : ($action === 'edit' ? 'Редактировать новость' : 'Новости'); ?>
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
        <!-- Список новостей -->
        <div class="page-header">
            <div class="header-actions">
                <a href="news.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить новость
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Список новостей</h3>
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
                            $pagination = getPagination($conn, 'news', 10);
                            $newsList = getNewsList($conn, $pagination['perPage'], $pagination['offset']);
                            
                            foreach ($newsList as $row): 
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <a href="../news.php?news=<?php echo $row['slug']; ?>" target="_blank">
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
                                        <a href="news.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="news.php?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('Удалить эту новость?')">
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
        $news = [];
        if ($action === 'edit' && $id) {
            $news = getNewsById($conn, $id);
            if (!$news) {
                redirectWithNotification('news.php', 'Новость не найдена', 'error');
            }
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Добавить новую новость' : 'Редактировать новость'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Заголовок *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($news['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">URL (слаг)</label>
                        <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($news['slug'] ?? ''); ?>" readonly>
                        <small>Генерируется автоматически из заголовка</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="excerpt">Краткое описание</label>
                        <textarea id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($news['excerpt'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="content" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Содержание *</span>
                            <button type="button" onclick="openArticleBuilder()" style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">
                                <i class="fas fa-pen"></i> Редактор блоков
                            </button>
                        </label>
                        <textarea id="content" name="content" rows="10" required><?php echo htmlspecialchars($article['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="author">Автор</label>
                            <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($news['author'] ?? ''); ?>">
                        </div>
                        
                    <div class="form-group col-md-6">
                        <label for="published_at">Дата публикации</label>
                        <input type="datetime-local" id="published_at" name="published_at" 
                            value="<?php 
                                    if (!empty($news['published_at']) && $news['published_at'] !== '0000-00-00 00:00:00') {
                                        // Проверяем, что дата валидна
                                        $timestamp = strtotime($news['published_at']);
                                        if ($timestamp !== false) {
                                            echo date('Y-m-d\TH:i', $timestamp);
                                        } else {
                                            echo '';
                                        }
                                    } else {
                                        echo '';
                                    }
                            ?>">
                    </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Обложка новости</label>
                        <div class="image-upload-container">
                            <div class="drop-zone" > <!-- onclick="document.getElementById('news-image').click()" -->
                                <?php if (!empty($news['image_path'])): ?>
                                    <img src="../<?php echo $news['image_path']; ?>" class="drop-zone__thumb" id="preview-img" alt="Обложка">
                                <?php else: ?>
                                    <span class="drop-zone__prompt">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        Перетащите обложку новости или кликните для выбора
                                    </span>
                                <?php endif; ?>
                                <input type="file" id="news-image" name="image" class="drop-zone__input" accept="image/*">
                                <input type="hidden" name="existing_image" id="existing_image_input" value="<?php echo $news['image_path'] ?? ''; ?>">
                            </div>
                            
                            <small class="text-muted">Рекомендуемый размер: 1200×600px, форматы: JPG, PNG, WebP, SVG</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_published" value="1" <?php echo ($news['is_published'] ?? 1) ? 'checked' : ''; ?>>
                            <span>Опубликовать новость</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="news.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<!-- Модальное окно редактора -->
<div id="articleBuilderModal" class="builder-modal">
    <div class="builder-modal__content">
        <div class="builder-modal__header">
            <h2>Редактор статей</h2>
            <button class="builder-modal__close" onclick="closeArticleBuilder()">&times;</button>
        </div>
        <div class="builder-modal__body">
            <div class="builder-toolbar">
                <button class="builder-btn builder-btn-primary" onclick="addTextBlock()">
                    <i class="fas fa-font"></i> Текстовый блок
                </button>
                <button class="builder-btn builder-btn-primary" onclick="addImageBlock()">
                    <i class="fas fa-image"></i> Блок изображения
                </button>
                <button class="builder-btn builder-btn-primary" onclick="addSliderBlock()">
                    <i class="fas fa-images"></i> Слайдер
                </button>
                <button class="builder-btn builder-btn-danger" onclick="clearAllBlocks()">
                    <i class="fas fa-trash-alt"></i> Очистить всё
                </button>
                <button class="builder-btn builder-btn-primary" onclick="generateAndSave()">
                    <i class="fas fa-code"></i> Сохранить и сгенерировать HTML
                </button>
            </div>
            <div id="blocksGrid" class="builder-grid"></div>
        </div>
    </div>
</div>

<script src="assets/js/redactor.js"></script>
<script src="assets/js/news.js"></script>
<script src="assets/js/scripts.js"></script>

<?php
// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>