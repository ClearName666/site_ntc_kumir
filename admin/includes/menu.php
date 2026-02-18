<?php
// Получаем статистику если переменная не определена
if (!isset($stats)) {
    $conn = getDBConnection();
    $stats = [
        'articles' => $conn->query("SELECT COUNT(*) as count FROM articles")->fetch_assoc()['count'],
        'news' => $conn->query("SELECT COUNT(*) as count FROM news")->fetch_assoc()['count'],
        'products' => $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'],
        'faq' => $conn->query("SELECT COUNT(*) as count FROM faq")->fetch_assoc()['count'],
        'categories' => $conn->query("SELECT COUNT(*) as count FROM product_categories")->fetch_assoc()['count'],
        'admins' => $conn->query("SELECT COUNT(*) as count FROM admins WHERE is_active = 1")->fetch_assoc()['count'],
        'feedback' => $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'],
        'feedback_new' => $conn->query("SELECT COUNT(*) as count FROM feedback WHERE is_read = 0")->fetch_assoc()['count'],
        'requests' => $conn->query("SELECT COUNT(*) as count FROM product_requests")->fetch_assoc()['count'],
        'requests_new' => $conn->query("SELECT COUNT(*) as count FROM product_requests WHERE status = 'new'")->fetch_assoc()['count'],
    ];
}



// Определяем активную страницу
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Сайдбар -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="/" class="sidebar-logo">
            <img src="../<?php echo getSetting('logo_path'); ?>" alt="Логотип">
            <div class="logo-text">
                <h2>НТЦ КУМИР</h2>
                <span>Админ-панель</span>
            </div>
        </a>
    </div>
    
    <nav class="sidebar-menu">
        <div class="menu-title">Главная</div>
        <a href="index.php" class="menu-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Дашборд</span>
        </a>

        <div class="menu-title">Взаимодействие</div>
        <a href="feedback.php" class="menu-item <?php echo ($current_page == 'feedback.php') ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span>Обращения</span>
            <?php if ($stats['feedback_new'] > 0): ?>
                <span class="menu-badge" style="background: #e74c3c;"><?php echo $stats['feedback_new']; ?></span>
            <?php else: ?>
                <span class="menu-badge"><?php echo $stats['feedback']; ?></span>
            <?php endif; ?>
        </a>

        <a href="requests.php" class="menu-item <?php echo ($current_page == 'requests.php') ? 'active' : ''; ?>">
            <i class="fas fa-shopping-cart"></i>
            <span>Заявки на КП</span>
            <?php if ($stats['requests_new'] > 0): ?>
                <span class="menu-badge" style="background: #e67e22;"><?php echo $stats['requests_new']; ?></span>
            <?php else: ?>
                <span class="menu-badge"><?php echo $stats['requests']; ?></span>
            <?php endif; ?>
        </a>
        
        <div class="menu-title">Контент</div>
        <a href="content.php" class="menu-item <?php echo ($current_page == 'content.php') ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>Текстовые блоки</span>
        </a>
        <a href="articles.php" class="menu-item <?php echo ($current_page == 'articles.php') ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i>
            <span>Статьи</span>
            <span class="menu-badge"><?php echo isset($stats['articles']) ? $stats['articles'] : '0'; ?></span>
        </a>
        <a href="news.php" class="menu-item <?php echo ($current_page == 'news.php') ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i>
            <span>Новости</span>
            <span class="menu-badge"><?php echo isset($stats['news']) ? $stats['news'] : '0'; ?></span>
        </a>
        <a href="faq.php" class="menu-item <?php echo ($current_page == 'faq.php') ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i>
            <span>Вопрос-ответ</span>
            <span class="menu-badge"><?php echo isset($stats['faq']) ? $stats['faq'] : '0'; ?></span>
        </a>
        
        <div class="menu-title">Товары</div>
        <a href="categories.php" class="menu-item <?php echo ($current_page == 'categories.php') ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i>
            <span>Категории</span>
        </a>
        <a href="products.php" class="menu-item <?php echo ($current_page == 'products.php') ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Товары</span>
            <span class="menu-badge"><?php echo isset($stats['products']) ? $stats['products'] : '0'; ?></span>
        </a>
        
        <div class="menu-title">Настройки</div>
        <a href="settings.php" class="menu-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Настройки сайта</span>
        </a>
        <a href="menu.php" class="menu-item <?php echo ($current_page == 'menu.php') ? 'active' : ''; ?>">
            <i class="fas fa-bars"></i>
            <span>Меню</span>
        </a>
        <a href="contacts.php" class="menu-item <?php echo ($current_page == 'contacts.php') ? 'active' : ''; ?>">
            <i class="fas fa-address-book"></i>
            <span>Контакты</span>
        </a>
        
        <div class="menu-title">Система</div>
        <a href="admins.php" class="menu-item <?php echo ($current_page == 'admins.php') ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i>
            <span>Администраторы</span>
            <span class="menu-badge"><?php echo isset($stats['admins']) ? $stats['admins'] : '0'; ?></span>
        </a>
        <a href="logs.php" class="menu-item <?php echo ($current_page == 'logs.php') ? 'active' : ''; ?>">
            <i class="fas fa-history"></i>
            <span>Логи</span>
        </a>
    </nav>
</aside>