<?php
// Определяем базовый путь
define('BASE_PATH', dirname(__DIR__));

// Подключаем функции
// require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/admin/includes/functions.php';

// Проверяем авторизацию
requireAdminAuth();

// Получаем информацию о текущем администраторе
$admin = getCurrentAdmin();

// Получаем статистику
$conn = getDBConnection();
$stats = [
    'articles' => $conn->query("SELECT COUNT(*) as count FROM articles")->fetch_assoc()['count'],
    'news' => $conn->query("SELECT COUNT(*) as count FROM news")->fetch_assoc()['count'],
    'products' => $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
    'faq' => $conn->query("SELECT COUNT(*) as count FROM faq")->fetch_assoc()['count'],
    'categories' => $conn->query("SELECT COUNT(*) as count FROM product_categories")->fetch_assoc()['count'],
    'admins' => $conn->query("SELECT COUNT(*) as count FROM admins WHERE is_active = 1")->fetch_assoc()['count'],
    'feedback'     => $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'],
    'feedback_new' => $conn->query("SELECT COUNT(*) as count FROM feedback WHERE is_read = 0")->fetch_assoc()['count'],
    'requests' => $conn->query("SELECT COUNT(*) as count FROM product_requests")->fetch_assoc()['count'],
    'requests_new' => $conn->query("SELECT COUNT(*) as count FROM product_requests WHERE status = 'new'")->fetch_assoc()['count'],
];

// Получаем последние действия
$recentLogs = $conn->query("SELECT al.*, a.username 
                           FROM admin_logs al 
                           LEFT JOIN admins a ON al.admin_id = a.id 
                           ORDER BY al.created_at DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

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
            <h1 class="header-title">Дашборд</h1>
        </div>

        <?php 
            // Подключаем правую шапку
            require_once BASE_PATH . '/admin/includes/header-right.php';
        ?>
    </header>
    
    <!-- Контент -->
    <div class="content-container">
        <!-- Приветствие -->
        <section class="welcome-section">
            <div class="welcome-header">
                <div class="welcome-text">
                    <h1>Добро пожаловать, <?php echo htmlspecialchars($admin['username']); ?>!</h1>
                    <p>Управляйте контентом, товарами и настройками вашего сайта</p>
                </div>
                <div class="welcome-icon">
                    <i class="fas fa-crown"></i>
                </div>
            </div>
        </section>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-title">Статьи</div>
                </div>
                <div class="stat-value"><?php echo $stats['articles']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-title">Новости</div>
                </div>
                <div class="stat-value"><?php echo $stats['news']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-title">Товары</div>
                </div>
                <div class="stat-value"><?php echo $stats['products']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-title">Вопросы</div>
                </div>
                <div class="stat-value"><?php echo $stats['faq']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-title">Категории</div>
                </div>
                <div class="stat-value"><?php echo $stats['categories']; ?></div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-title">Администраторы</div>
                </div>
                <div class="stat-value"><?php echo $stats['admins']; ?></div>
            </div>
        </div>
        
        <!-- Быстрые действия -->
        <section class="quick-actions">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Быстрые действия
            </h2>
            
            <div class="actions-grid">
                <a href="articles.php?action=add" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="action-text">
                        <h4>Добавить статью</h4>
                        <p>Создать новую статью</p>
                    </div>
                </a>
                
                <a href="news.php?action=add" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="action-text">
                        <h4>Добавить новость</h4>
                        <p>Опубликовать новость</p>
                    </div>
                </a>
                
                <a href="products.php?action=add" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="action-text">
                        <h4>Добавить товар</h4>
                        <p>Добавить новый товар</p>
                    </div>
                </a>
                
                <a href="faq.php?action=add" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="action-text">
                        <h4>Добавить вопрос</h4>
                        <p>Добавить новый FAQ</p>
                    </div>
                </a>
            </div>
        </section>
        
        <!-- Последние действия -->
        <section class="recent-activity">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Последние действия
            </h2>
            
            <div class="activity-list">
                <?php if (!empty($recentLogs)): ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php 
                                $icon = 'fa-user';
                                if (strpos($log['action'], 'login') !== false) $icon = 'fa-sign-in-alt';
                                elseif (strpos($log['action'], 'logout') !== false) $icon = 'fa-sign-out-alt';
                                elseif (strpos($log['action'], 'add') !== false) $icon = 'fa-plus';
                                elseif (strpos($log['action'], 'edit') !== false) $icon = 'fa-edit';
                                elseif (strpos($log['action'], 'delete') !== false) $icon = 'fa-trash';
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <?php echo htmlspecialchars($log['username'] ?? 'Система'); ?>
                                </div>
                                <div class="activity-desc">
                                    <?php echo htmlspecialchars($log['description'] ?? $log['action']); ?>
                                </div>
                            </div>
                            <div class="activity-time">
                                <?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <div class="activity-content">
                            <div class="activity-desc">Нет последних действий</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php
// Подключаем скрипты
require_once BASE_PATH . '/admin/includes/scripts.php';

// Подключаем подвал
require_once BASE_PATH . '/admin/includes/footer.php';
?>