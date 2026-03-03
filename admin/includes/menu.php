<?php
// загружаем ее через нашу новую функцию
if (!isset($stats)) {
    $stats = getDashboardStats($conn);
}



// Определяем активную страницу
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Сайдбар -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="/" class="sidebar-logo">
            <img src="../<?php echo getSetting($conn, 'logo_path'); ?>" alt="Логотип">
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
        <a href="cards.php" class="menu-item <?php echo ($current_page == 'cards.php') ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i> 
            <span>Карточки товаров</span>
        </a>

        <a href="statistics.php" class="menu-item <?php echo ($current_page == 'statistics.php') ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i> 
            <span>Статистика</span>
        </a>

        <a href="features.php" class="menu-item <?php echo ($current_page == 'features.php') ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> 
            <span>Главные преимущества</span>
        </a>

        <a href="advantages.php" class="menu-item <?php echo ($current_page == 'advantages.php') ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> 
            <span>Преимущества</span>
        </a>

        <a href="offices.php" class="menu-item <?php echo ($current_page == 'offices.php') ? 'active' : ''; ?>">
            <i class="fas fa-building"></i> 
            <span>Офисы</span>
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
        <a href="images.php" class="menu-item <?php echo ($current_page == 'images.php') ? 'active' : ''; ?>">
            <i class="fas fa-images"></i>
            <span>Изображения</span>
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
<script src="assets/js/setPositionScroll.js"></script>