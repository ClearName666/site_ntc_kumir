<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';

// подключаемся к базе 
$conn = getDBConnection();

// Проверяем авторизацию
requireAdminAuth($conn);

// Проверяем права доступа
if (!hasPermission($conn, 'admin')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// Обработка действий (Прочитать/Удалить)
if ($action === 'read' && $id) {
    if (markFeedbackAsRead($conn, $id)) {
        logAdminAction($conn, 'feedback_read', "Обращение ID $id помечено как прочитанное");
        redirectWithNotification('feedback.php', 'Обращение помечено как прочитанное', 'success');
    }
}

if ($action === 'delete' && $id) {
    if (deleteFeedback($conn, $id)) {
        logAdminAction($conn, 'feedback_delete', "Удалено обращение ID $id");
        redirectWithNotification('feedback.php', 'Обращение успешно удалено', 'success');
    } else {
        redirectWithNotification('feedback.php', 'Ошибка при удалении', 'error');
    }
}

// Получаем список обращений через функцию, которую мы добавили ранее
$feedbacks = getAllFeedback($conn);

// Подключаем шапку и меню
require_once __DIR__. '/includes/header.php';
require_once __DIR__. '/includes/menu.php';
?>

<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">Обращения с сайта</h1>
        </div>
        
        <?php 
            // Подключаем правую шапку
            require_once __DIR__. '/includes/header-right.php';
        ?>
    </header>
    
    <div class="content-container">
        <div class="card">
            <div class="card-header">
                <h3>Список входящих сообщений</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Отправитель</th>
                                <th>Контакты</th>
                                <th>Тема</th>
                                <th>Сообщение</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($feedbacks)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">Сообщений пока нет</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($feedbacks as $item): ?>
                                    <tr class="<?php echo !$item['is_read'] ? 'unread-row' : ''; ?>">
                                        <td style="white-space: nowrap;">
                                            <?php echo date('d.m.Y H:i', strtotime($item['created_at'])); ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                                        <td>
                                            <small>
                                                Email: <?php echo htmlspecialchars($item['email']); ?><br>
                                                Тел: <?php echo htmlspecialchars($item['phone'] ?: '-'); ?>
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['subject'] ?: 'Не указана'); ?></td>
                                        <td>
                                            <div style="max-width: 300px; font-size: 0.9em;">
                                                <?php echo nl2br(htmlspecialchars($item['message'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $item['is_read'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $item['is_read'] ? 'Прочитано' : 'Новое'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if (!$item['is_read']): ?>
                                                    <a href="feedback.php?action=read&id=<?php echo $item['id']; ?>" 
                                                       class="btn btn-sm btn-edit" title="Пометить прочитанным">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="feedback.php?action=delete&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-delete" 
                                                   onclick="return confirm('Удалить это обращение?')" title="Удалить">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__. '/includes/scripts.php';
require_once __DIR__. '/includes/footer.php';
?>