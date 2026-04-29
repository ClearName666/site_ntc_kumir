<?php
// Если сессия еще не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Простая проверка: если id админа есть в сессии, значит он вошел
$isAdmin = isset($_SESSION['admin_id']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSetting($conn, 'site_title'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="assets/css/responsive.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="assets/css/header.css?version=<?php echo $version_code; ?>">
    <link rel="icon" href="<?php echo getSetting($conn, 'favicon_path'); ?>" type="image/x-icon">
    <style>

    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <!-- Логотип -->
                <a href="/index.php" class="logo">
                    <img src="<?php echo getSetting($conn, 'logo_path'); ?>" alt="<?php echo getSetting($conn, 'company_name'); ?>">
                </a>
                
                <!-- Основная навигация -->
                <nav class="main-nav">
                    <?php
                    // Получаем все пункты меню из БД
                    $menuItems = getMenuItems($conn);
                    
                    // Первый пункт меню (О компании) отображаем отдельно
                    $firstItem = array_shift($menuItems);
                    
                    // Остальные пункты помещаем в выпадающее меню
                    if (!empty($menuItems)) {
                        echo '<div class="dropdown">';
                        echo '<a href="#" class="nav-link dropdown-toggle">Оставшиеся позиции</a>';
                        echo '<div class="dropdown-menu">';
                        
                        foreach ($menuItems as $item) {
                            echo '<a href="' . $item['url'] . '" class="dropdown-item">' . $item['title'] . '</a>';
                        }
                        
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    // Отображаем первый пункт меню
                    if ($firstItem) {
                        echo '<a href="' . $firstItem['url'] . '" class="nav-link">' . $firstItem['title'] . '</a>';
                    }
                    ?>
                </nav>
                
                <!-- Правая часть -->
                <div class="header-right">
                    <!-- Телефон -->
                    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', getSetting($conn, 'phone')); ?>" class="header-phone">
                        <span class="phone-icon">📞</span>
                        <span><?php echo getSetting($conn, 'phone'); ?></span>
                    </a>
                    
                    <!-- Личный кабинет https://v4.ntckumir.ru/ -->
                    <a href="/userKumir/index.php" class="btn-personal">
                        <span class="btn-icon">👤</span>
                        <span class="btn-text">Личный кабинет</span>
                    </a>
                    
                    <!-- Кнопка входа в админ-панель -->
                    <a href="/admin/login.php" class="btn-admin">
                        <span class="admin-icon">🔐</span>
                        <span class="admin-text">Вход</span>
                    </a>
                    
                    <!-- Кнопка мобильного меню -->
                    <button class="mobile-menu-btn">☰</button>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Мобильное меню -->
    <div class="mobile-menu">
        <nav class="mobile-nav">
            <?php
            // Получаем все пункты меню для мобильной версии
            $allMenuItems = getNavigationMenu($conn);
            foreach ($allMenuItems as $item) {
                echo '<a href="' . $item['url'] . '" class="nav-link">' . $item['title'] . '</a>';
            }
            ?>
        </nav>
        
        <div class="mobile-contact">
            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', getSetting($conn, 'phone')); ?>" class="header-phone">
                <span class="phone-icon">📞</span>
                <span><?php echo getSetting($conn, 'phone'); ?></span>
            </a>
            
            <a href="https://v4.ntckumir.ru/" class="btn-personal">
                <span class="btn-icon">👤</span>
                <span class="btn-text">Личный кабинет</span>
            </a>
            
            <!-- Кнопка входа в админку для мобильной версии -->
            <div class="mobile-admin-btn">
                <a href="/admin/login.php" class="btn-admin">
                    <span class="admin-icon">🔐</span>
                    <span class="admin-text">Вход в админку</span>
                </a>
            </div>
        </div>
    </div>
    
<script>
document.addEventListener('DOMContentLoaded', function() {
    const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
    
    // Обновление кнопки админа
    if (isAdmin) {
        const adminBtn = document.querySelector('.btn-admin');
        if (adminBtn) {
            adminBtn.innerHTML = '<span class="admin-icon">👑</span><span class="admin-text">Админка</span>';
            adminBtn.href = '/admin/index.php';
        }
        
        const mobileAdminBtn = document.querySelector('.mobile-admin-btn .btn-admin');
        if (mobileAdminBtn) {
            mobileAdminBtn.innerHTML = '<span class="admin-icon">👑</span><span class="admin-text">Админка</span>';
            mobileAdminBtn.href = '/admin/index.php';
        }
    }
    
    const header = document.querySelector('.main-header');
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');

    // Логика скролла
    window.addEventListener('scroll', function() {
        if (window.scrollY > 100) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Логика мобильного меню
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('active');
            this.textContent = mobileMenu.classList.contains('active') ? '✕' : '☰';
        });
    }
});
</script>
</body>
</html>