<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSetting('site_title'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="icon" href="<?php echo getSetting('favicon_path'); ?>" type="image/x-icon">
    <style>

    </style>
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <!-- Логотип -->
                <a href="/index.php" class="logo">
                    <img src="<?php echo getSetting('logo_path'); ?>" alt="<?php echo getSetting('company_name'); ?>">
                </a>
                
                <!-- Основная навигация -->
                <nav class="main-nav">
                    <?php
                    // Получаем все пункты меню из БД
                    $menuItems = getMenuItems();
                    
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
                    <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', getSetting('phone')); ?>" class="header-phone">
                        <span class="phone-icon">📞</span>
                        <span><?php echo getSetting('phone'); ?></span>
                    </a>
                    
                    <!-- Личный кабинет -->
                    <a href="https://v4.ntckumir.ru/" class="btn-personal">
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
            $allMenuItems = getNavigationMenu();
            foreach ($allMenuItems as $item) {
                echo '<a href="' . $item['url'] . '" class="nav-link">' . $item['title'] . '</a>';
            }
            ?>
        </nav>
        
        <div class="mobile-contact">
            <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', getSetting('phone')); ?>" class="header-phone">
                <span class="phone-icon">📞</span>
                <span><?php echo getSetting('phone'); ?></span>
            </a>
            
            <a href="https://v4.ntckumir.ru/" class="btn-personal">
                <span class="btn-icon">👤</span>
                <span class="btn-text">Личный кабинет</span>
            </a>
            
            <!-- Кнопка входа в админку для мобильной версии -->
            <div class="mobile-admin-btn">
                <a href="/ntc-kumir/admin/login.php" class="btn-admin">
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
            fetch('/ntc-kumir/admin/check-auth.php')
                .then(response => response.json())
                .then(data => {
                    if (data.isAdmin) {
                        // Меняем кнопку "Вход" на "Админка"
                        const adminBtn = document.querySelector('.btn-admin');
                        if (adminBtn) {
                            adminBtn.innerHTML = '<span class="admin-icon">👑</span><span class="admin-text">Админка</span>';
                            adminBtn.href = '/ntc-kumir/admin/';
                        }
                        
                        // Для мобильной версии
                        const mobileAdminBtn = document.querySelector('.mobile-admin-btn .btn-admin');
                        if (mobileAdminBtn) {
                            mobileAdminBtn.innerHTML = '<span class="admin-icon">👑</span><span class="admin-text">Админка</span>';
                            mobileAdminBtn.href = '/ntc-kumir/admin/';
                        }
                    }
                })
                .catch(error => {
                    console.log('Не удалось проверить авторизацию администратора');
                });
        });
    </script>
</body>
</html>