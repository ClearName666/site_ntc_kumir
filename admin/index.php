<?php
// Подключаем функции
require_once __DIR__. '/includes/functions.php';
require_once __DIR__. '/../Cache.php';

// Подключаемся к базе 
$conn = getDBConnection();

// Проверяем авторизацию
requireAdminAuth($conn);

// Обработка AJAX запроса для очистки кэша
if (isset($_POST['action']) && $_POST['action'] === 'clear_cache') {
    header('Content-Type: application/json');
    
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'message' => 'Ошибка безопасности']);
        exit;
    }
    
    // Используем функцию clearCache
    $result = clearCache($conn);
    
    // Отправляем результат
    echo json_encode($result);
    exit;
}

// Генерируем CSRF токен
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// СОЗДАЕМ ОБЪЕКТ КЭША 
$cacheStats = $cache->getStats();






// Получаем данные через функции
$admin = getCurrentAdmin($conn);
$stats = getDashboardStats($conn);
$recentLogs = getRecentAdminLogs($conn, 10);



// Подключаем шапку и меню
require_once __DIR__. '/includes/header.php';
require_once __DIR__. '/includes/menu.php';
?>

<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
            <h1 class="header-title">Дашборд</h1>
        </div>
        <?php require_once __DIR__. '/includes/header-right.php'; ?>
    </header>
    
    <div class="content-container">
        <section class="welcome-section">
            <div class="welcome-header">
                <div class="welcome-text">
                    <h1>Добро пожаловать, <?php echo htmlspecialchars($admin['username']); ?>!</h1>
                    <p>Управляйте контентом и настройками вашего сайта</p>
                </div>
                <div class="welcome-icon"><i class="fas fa-crown"></i></div>
            </div>
        </section>
        
        <div class="stats-grid">
            <?php 
            $cards = [
                ['icon' => 'fa-newspaper', 'title' => 'Статьи', 'val' => $stats['articles']],
                ['icon' => 'fa-bullhorn', 'title' => 'Новости', 'val' => $stats['news']],
                ['icon' => 'fa-box', 'title' => 'Товары', 'val' => $stats['products']],
                ['icon' => 'fa-question-circle', 'title' => 'Вопросы', 'val' => $stats['faq']],
                ['icon' => 'fa-tags', 'title' => 'Категории', 'val' => $stats['categories']],
                ['icon' => 'fa-users', 'title' => 'Админы', 'val' => $stats['admins']],
            ];
            foreach ($cards as $card): ?>
                <div class="stat-card">
                    <div class="stat-header">
                        <div class="stat-icon"><i class="fas <?php echo $card['icon']; ?>"></i></div>
                        <div class="stat-title"><?php echo $card['title']; ?></div>
                    </div>
                    <div class="stat-value"><?php echo $card['val']; ?></div>
                </div>
            <?php endforeach; ?>
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
                <!-- Кнопка очистки кэша -->
                <button onclick="clearCache()" class="action-btn" id="clearCacheBtn">
                    <div class="action-icon">
                        <i class="fas fa-broom"></i>
                    </div>
                    <div class="action-text">
                        <h4>Очистить кэш</h4>
                        <p>Файлов: <?php echo $cacheStats; ?></p>
                    </div>
                </button>
            </div>
        </section>

        <section class="recent-activity">
            <h2 class="section-title"><i class="fas fa-history"></i> Последние действия</h2>
            <div class="activity-list">
                <?php if (!empty($recentLogs)): ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas <?php echo getLogIcon($log['action']); ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo htmlspecialchars($log['username'] ?? 'Система'); ?></div>
                                <div class="activity-desc"><?php echo htmlspecialchars($log['description'] ?? $log['action']); ?></div>
                            </div>
                            <div class="activity-time"><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Нет последних действий</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
</div>

<script src="assets/js/scripts.js"></script>
<script>
    function clearCache(csrf_token) {
    if (!confirm('Вы уверены, что хотите очистить кэш?')) {
        return;
    }
    
    const btn = document.getElementById('clearCacheBtn');
    btn.style.opacity = '0.6';
    btn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'clear_cache',
            'csrf_token': '<?php echo $_SESSION['csrf_token']; ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Кэш успешно очищен!');
            location.reload(); // Просто перезагружаем страницу
        } else {
            alert('Ошибка: ' + (data.message || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        alert('Ошибка соединения');
    })
    .finally(() => {
        btn.style.opacity = '1';
        btn.disabled = false;
    });
}
</script>


<?php
require_once __DIR__. '/includes/footer.php';
?>