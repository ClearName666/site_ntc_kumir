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
            <button class="toggle-sidebar" id="toggleSidebar"  style="display: none;">
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
                    <!-- Добавляем класс responsive-table для адаптивности -->
                    <table class="data-table responsive-table">
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
                                        <!-- Добавляем data-label для мобильной версии -->
                                        <td data-label="Дата" style="white-space: nowrap;">
                                            <?php echo date('d.m.Y H:i', strtotime($item['created_at'])); ?>
                                        </td>
                                        
                                        <td data-label="Отправитель">
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                        </td>
                                        
                                        <td data-label="Контакты">
                                            <small>
                                                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($item['email']); ?><br>
                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($item['phone'] ?: '-'); ?>
                                            </small>
                                        </td>
                                        
                                        <td data-label="Тема">
                                            <?php echo htmlspecialchars($item['subject'] ?: 'Не указана'); ?>
                                        </td>
                                        
                                        <td data-label="Сообщение">
                                            <div style="font-size: 0.9em;">
                                                <?php echo nl2br(htmlspecialchars($item['message'])); ?>
                                            </div>
                                        </td>
                                        
                                        <td data-label="Статус">
                                            <span class="status-badge <?php echo $item['is_read'] ? 'active' : 'inactive'; ?>">
                                                <?php echo $item['is_read'] ? '<i class="fas fa-check-circle"></i> Прочитано' : '<i class="fas fa-clock"></i> Новое'; ?>
                                            </span>
                                        </td>
                                        
                                        <td data-label="Действия">
                                            <div class="action-buttons">
                                                <?php if (!$item['is_read']): ?>
                                                    <a href="feedback.php?action=read&id=<?php echo $item['id']; ?>" 
                                                       class="btn btn-sm btn-edit" title="Пометить прочитанным">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="feedback.php?action=delete&id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-delete" 
                                                   onclick="return confirm('Вы уверены, что хотите удалить это обращение?')"
                                                   title="Удалить">
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
</div>

<script src="assets/js/miniAdminstration.js"></script>

<?php
require_once __DIR__. '/includes/footer.php';
?>