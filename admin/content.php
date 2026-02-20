<?php
require_once __DIR__. '/includes/functions.php';

$conn = getDBConnection();
requireAdminAuth($conn);

// Проверка прав (в вашем коде была проверка 'editor', оставляем её)
if (!hasPermission($conn, 'editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// --- ОБРАБОТКА СОХРАНЕНИЯ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit' && $id) {
    $title = cleanInput($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // Контент обычно не чистим через cleanInput, если там HTML
    
    if (updateContentBlock($conn, $id, $title, $content)) {
        logAdminAction($conn, 'content_edit', "Отредактирован блок: $title");
        redirectWithNotification('content.php', 'Контент успешно обновлен', 'success');
    } else {
        redirectWithNotification('content.php', 'Ошибка при обновлении', 'error');
    }
}

// --- ПОДГОТОВКА ДАННЫХ ДЛЯ ВЫВОДА ---
$blocks = ($action === 'list') ? getAllContentBlocks($conn) : [];
$currentBlock = ($action === 'edit' && $id) ? getContentBlockById($conn, $id) : null;

// Если зашли в редактирование, а блока нет — уходим
if ($action === 'edit' && !$currentBlock) {
    redirectWithNotification('content.php', 'Блок не найден', 'error');
}

require_once __DIR__. '/includes/header.php';
require_once __DIR__. '/includes/menu.php';
?>

<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
            <h1 class="header-title">
                <?php echo $action === 'edit' ? 'Редактировать контент' : 'Текстовые блоки'; ?>
            </h1>
        </div>
        <?php require_once __DIR__. '/includes/header-right.php'; ?>
    </header>
    
    <div class="content-container">
        <?php if ($action === 'list'): ?>
            <div class="card">
                <div class="card-body">
                    <div class="content-blocks-grid">
                        <?php foreach ($blocks as $block): ?>
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
                                <p><?php echo nl2br(getContentPreview($block['content'])); ?></p>
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
            <div class="card">
                <div class="card-header">
                    <h3>Редактирование: <?php echo htmlspecialchars($currentBlock['block_key']); ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="title">Заголовок</label>
                            <input type="text" id="title" name="title" class="form-control" 
                                   value="<?php echo htmlspecialchars($currentBlock['title'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="content">Содержание *</label>
                            <textarea id="content" name="content" rows="10" class="form-control" required><?php echo htmlspecialchars($currentBlock['content'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Сохранить</button>
                            <a href="content.php" class="btn btn-secondary">Отмена</a>
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