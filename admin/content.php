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

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = cleanInput($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    
    if ($action === 'edit' && $id) {
        $stmt = $conn->prepare("UPDATE content_blocks SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $title, $content, $id);
        
        if ($stmt->execute()) {
            logAdminAction('content_edit', "Отредактирован контент-блок: $title");
            redirectWithNotification('content.php', 'Контент успешно обновлен', 'success');
        } else {
            redirectWithNotification('content.php', 'Ошибка при обновлении контента', 'error');
        }
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
                <?php echo $action === 'edit' ? 'Редактировать контент' : 'Текстовые блоки'; ?>
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
        <!-- Список контент-блоков -->
        <div class="card">
            <div class="card-header">
                <h3>Текстовые блоки</h3>
            </div>
            <div class="card-body">
                <p>Редактируйте текстовые блоки, которые отображаются на сайте:</p>
                
                <div class="content-blocks-grid">
                    <?php
                    $blocks = $conn->query("SELECT * FROM content_blocks ORDER BY block_key")->fetch_all(MYSQLI_ASSOC);
                    
                    foreach ($blocks as $block):
                    ?>
                    <div class="content-block-card">
                        <div class="block-header">
                            <h4><?php echo htmlspecialchars($block['block_key']); ?></h4>
                            <?php if (!empty($block['category'])): ?>
                            <span class="block-category"><?php echo htmlspecialchars($block['category']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="block-content">
                            <?php if (!empty($block['title'])): ?>
                            <p><strong><?php echo htmlspecialchars($block['title']); ?></strong></p>
                            <?php endif; ?>
                            <p><?php 
                                // Используем substr вместо mb_substr если mbstring не установлен
                                if (function_exists('mb_substr')) {
                                    echo nl2br(htmlspecialchars(mb_substr($block['content'], 0, 100))) . '...';
                                } else {
                                    echo nl2br(htmlspecialchars(substr($block['content'], 0, 100))) . '...';
                                }
                            ?></p>
                        </div>
                        <div class="block-actions">
                            <a href="content.php?action=edit&id=<?php echo $block['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Редактировать
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php elseif ($action === 'edit'): ?>
        <!-- Форма редактирования -->
        <?php
        if (!$id) {
            redirectWithNotification('content.php', 'Не указан ID блока', 'error');
        }
        
        $stmt = $conn->prepare("SELECT * FROM content_blocks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $block = $result->fetch_assoc();
        
        if (!$block) {
            redirectWithNotification('content.php', 'Блок не найден', 'error');
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Редактирование: <?php echo htmlspecialchars($block['block_key']); ?></h3>
                <?php if (!empty($block['category'])): ?>
                <small>Категория: <?php echo htmlspecialchars($block['category']); ?></small>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Заголовок</label>
                        <input type="text" id="title" name="title" class="form-control" 
                               value="<?php echo htmlspecialchars($block['title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Содержание *</label>
                        <textarea id="content" name="content" rows="10" class="form-control" required><?php echo htmlspecialchars($block['content'] ?? ''); ?></textarea>
                        <small class="text-muted">Для разделения строк используйте перевод строки (Enter)</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="content.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Подключаем скрипты
require_once BASE_PATH . '/admin/includes/scripts.php';

// Подключаем подвал
require_once BASE_PATH . '/admin/includes/footer.php';
?>