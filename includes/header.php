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
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
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
    <div class="mobile-menu" style="background: black;">
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
        // Добавляем проверку авторизации администратора
        document.addEventListener('DOMContentLoaded', function() {
            // Проверяем, авторизован ли администратор
            const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
            if (isAdmin) {
                // 1. Меняем кнопку в обычном меню
                const adminBtn = document.querySelector('.btn-admin');
                if (adminBtn) {
                    adminBtn.innerHTML = '<span class="admin-icon">👑</span><span class="admin-text">Админка</span>';
                    adminBtn.href = '/admin/index.php'; // Путь к главной странице админки
                }
                
                // 2. Меняем кнопку в мобильном меню
                const mobileAdminBtn = document.querySelector('.mobile-admin-btn .btn-admin');
                if (mobileAdminBtn) {
                    mobileAdminBtn.innerHTML = '<span class="admin-icon">👑</span><span class="admin-text">Админка</span>';
                    mobileAdminBtn.href = '/admin/index.php';
                }
            }
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const mobileMenu = document.querySelector('.mobile-menu');
            
            if (mobileMenuBtn && mobileMenu) {
                // 1. Открытие/Закрытие по клику на кнопку
                mobileMenuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    mobileMenu.classList.toggle('active');
                    this.textContent = mobileMenu.classList.contains('active') ? '✕' : '☰';
                });
                // 2. Закрытие меню при клике вне его области
                document.addEventListener('click', function(event) {
                    if (!event.target.closest('.mobile-menu') && 
                        !event.target.closest('.mobile-menu-btn')) {
                        if (mobileMenu.classList.contains('active')) {
                            mobileMenu.classList.remove('active');
                            mobileMenuBtn.textContent = '☰';
                        }
                    }
                });
                // 3. Закрытие меню при клике на любую ссылку внутри
                mobileMenu.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', function() {
                        mobileMenu.classList.remove('active');
                        mobileMenuBtn.textContent = '☰';
                    });
                });
            }
        });

    </script>
</body>
</html>