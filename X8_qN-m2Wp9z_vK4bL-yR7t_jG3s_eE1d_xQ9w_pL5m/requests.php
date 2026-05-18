<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';

// подключаемся к базе 
$conn = getDBConnection();

// Проверяем авторизацию
requireAdminAuth($conn);

// Проверяем права доступа (используем тот же уровень, что и для FAQ)
if (!hasPermission($conn, 'editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Обработка удаления через вынесенную функцию
if ($action === 'delete' && $id > 0) {
    if (deleteProductRequest($conn, $id)) {
        redirectWithNotification('requests.php', 'Заявка успешно удалена', 'success');
    } else {
        redirectWithNotification('requests.php', 'Ошибка при удалении или заявка не найдена', 'error');
    }
}

// Подключаем шапку
require_once __DIR__. '/includes/header.php';

// Подключаем меню
require_once __DIR__. '/includes/menu.php';
?>

<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar" style="display: none;">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">Заявки на коммерческие предложения</h1>
        </div>
        
        <?php 
            // Подключаем правую шапку
            require_once __DIR__. '/includes/header-right.php';
        ?>
    </header>
    
    <div class="content-container">
        <div class="card">
            <div class="card-header">
                <h3>Список активных запросов</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table responsive-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Товар</th>
                                <th>Клиент</th>
                                <th>Контакты</th>
                                <th>Кол-во</th>
                                <th>Сообщение</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Используем твою стандартную функцию пагинации
                            $pagination = getPagination($conn, 'product_requests', 10);
                            $result = getProductRequests($conn, $pagination['perPage'], $pagination['offset']);
                            
                            if ($result->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px;">Заявок пока нет</td>
                                </tr>
                            <?php endif;

                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td data-label="Дата" style="font-size: 0.85rem;">
                                    <strong><?php echo date('d.m.Y', strtotime($row['created_at'])); ?></strong><br>
                                    <?php echo date('H:i', strtotime($row['created_at'])); ?>
                                </td>
                                
                                <td data-label="Товар">
                                    <span class="category-badge" style="background: #f1f4f9; color: #333; border: 1px solid #ddd;">
                                        <?php echo htmlspecialchars($row['product_name']); ?>
                                    </span>
                                </td>
                                
                                <td data-label="Клиент">
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                </td>
                                
                                <td data-label="Контакты">
                                    <i class="fas fa-phone-alt" style="font-size: 0.7rem;"></i> <?php echo htmlspecialchars($row['phone']); ?><br>
                                    <i class="fas fa-envelope" style="font-size: 0.7rem;"></i> <small><?php echo htmlspecialchars($row['email']); ?></small>
                                </td>
                                
                                <td data-label="Кол-во">
                                    <?php echo $row['quantity']; ?> шт.
                                </td>
                                
                                <td data-label="Сообщение">
                                    <div style="max-width: 200px; font-size: 0.9rem; color: #666;">
                                        <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                    </div>
                                </td>
                                
                                <td data-label="Действия">
                                    <div class="action-buttons">
                                        <a href="requests.php?action=delete&id=<?php echo $row['id']; ?>" 
                                        class="btn btn-sm btn-delete"
                                        onclick="return confirm('Вы уверены, что хотите удалить эту заявку?')">
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
    </div>
</div>

<script src="assets/js/miniAdminstration.js"></script>

<?php
// Подключаем скрипты и подвал
require_once __DIR__. '/includes/footer.php';
?>