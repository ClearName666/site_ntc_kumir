<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';
require_once __DIR__. '/config/config.php';

// подключаемся к базе 
$conn = getDBConnection();
$mainBg = getImage($conn, 'image_background_all');

// Определяем пути
$footerPath = __DIR__. '/includes/footer.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Страница ушла на кофе</title>
    <!-- стили -->
    <link rel="stylesheet" href="/assets/css/faq.css?version=<?php echo $version_code; ?>">
    <style>
        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            overflow-x: hidden;
            position: relative;
            z-index: 0;
            min-height: 100vh;
        }
        /* Дополнительные стили для деловой креативной 404 */
        .error-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            position: relative;
            z-index: 2;
            padding: 20px;
        }
        .creative-card {
            background: var(--bg-white-card);
            backdrop-filter: blur(12px);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-card);
            border: 1px solid rgba(255,255,255,0.3);
            max-width: 550px;
            width: 100%;
            padding: 50px 40px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .creative-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-card-hover);
        }
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            display: inline-block;
            animation: wave 2s infinite ease-in-out;
        }
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(15deg); }
            75% { transform: rotate(-10deg); }
        }
        .error-code {
            font-size: 110px;
            font-weight: 800;
            line-height: 1;
            color: var(--primary-color);
            letter-spacing: 8px;
            margin-bottom: 15px;
            text-shadow: 0 4px 15px rgba(0,123,255,0.2);
        }
        .error-title {
            font-size: 28px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 15px;
        }
        .funny-text {
            font-size: 18px;
            color: var(--text-gray);
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .coffee-break {
            display: inline-block;
            background: var(--bg-white-semi);
            padding: 5px 15px;
            border-radius: 40px;
            font-size: 14px;
            color: var(--primary-dark);
            margin-bottom: 30px;
            font-weight: 500;
        }
        .home-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: var(--primary-color);
            color: white;
            padding: 12px 30px;
            border-radius: var(--radius-badge);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }
        .home-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,123,255,0.4);
        }
        /* Маленький робот-следопыт (деловой юмор) */
        .robot-trace {
            margin-top: 30px;
            font-size: 13px;
            color: var(--text-light);
            border-top: 1px dashed rgba(0,0,0,0.1);
            padding-top: 20px;
            display: flex;
            justify-content: center;
            gap: 8px;
        }
        .robot-trace span {
            animation: blink 1.5s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        /* ========================================
        БАЗОВЫЙ HEADER (Состояние на самом верху)
        ======================================== */
        .main-header {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            color: white;

            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            z-index: 1000;

            border-radius: 0;
            border: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            
            /* Плавность трансформаций */
            transition: all 0.5s cubic-bezier(0.165, 0.84, 0.44, 1);
        }

        /* ========================================
        СОСТОЯНИЕ ПРИ СКРОЛЛЕ (Анимация выезда сверху)
        ======================================== */
        .main-header.scrolled {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(0); /* Финальная точка */
            
            width: calc(100% - 40px);
            max-width: 1400px;
            
            background: rgba(0, 0, 0, 0.4);
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            
            /* Эффект появления сверху вниз при добавлении класса */
            animation: slideDown 0.5s ease forwards;
        }

        @keyframes slideDown {
            from {
                transform: translateX(-50%) translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        }

        /* Контент внутри хедера */
        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 30px;
        }

        /* ========================================
        МОБИЛЬНОЕ МЕНЮ (Выезд сверху)
        ======================================== */
        .mobile-menu {
            display: none; /* Скрыто по умолчанию */
            position: fixed;
            top: 90px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 40px);
            max-width: 1400px;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(20px);
            border-radius: 32px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            z-index: 999;
            overflow: hidden;
        }

        /* Класс активации мобильного меню */
        .mobile-menu.active {
            display: block;
            animation: mobileSlideDown 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @keyframes mobileSlideDown {
            from {
                transform: translateX(-50%) translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        }

        /* Изменяем отступ мобильного меню, когда основной хедер скроллится */
        .main-header.scrolled ~ .mobile-menu {
            top: 85px;
        }

        /* ========================================
        КОНТЕНТ (Игнорирует хедер)
        ======================================== */
        main, .hero, .products-page {
            padding-top: 0 !important;
        }

        /* ========================================
        АДАПТИВНОСТЬ
        ======================================== */
        @media (max-width: 768px) {
            .main-header.scrolled {
                top: 10px;
                width: calc(100% - 20px);
            }
            .mobile-menu {
                top: 75px;
                width: calc(100% - 20px);
            }
        }


        /* ========================================
        FOOTER
        ======================================== */
        .main-footer {
            background: var(--bg-dark);
            color: white;
            padding: 60px 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-column h3 {
            margin-bottom: 25px;
            font-size: 20px;
            font-weight: 600;
            color: white;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 12px;
        }

        .footer-links a {
            color: var(--text-footer);
            text-decoration: none;
            transition: color 0.3s;
            font-size: 16px;
        }

        .footer-links a:hover {
            color: white;
        }

        .contact-info {
            color: var(--text-footer);
            line-height: 1.8;
        }

        .contact-info p {
            margin-bottom: 15px;
            font-size: 16px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: var(--border-footer);
            color: var(--text-light);
            font-size: 14px;
        }

        .footer-bottom p {
            margin-bottom: 10px;
        }
        .custom-checkbox-wrapper {
            margin-bottom: 10px;
        }

        /* Контейнер */
        .custom-checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
            user-select: none;
        }

        /* Скрываем стандартный checkbox */
        .custom-checkbox-label input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        /* Кастомный чекбокс */
        .checkmark {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            margin-right: 12px;

            background: linear-gradient(145deg, #f0f0f0, #e6e6e6);
            border: 1px solid #cfcfcf;
            border-radius: 6px;

            position: relative;
            transition: all 0.25s ease;

            box-shadow: 2px 2px 6px rgba(0,0,0,0.08),
                        -2px -2px 6px rgba(255,255,255,0.8);
        }

        /* Hover */
        .custom-checkbox-label:hover .checkmark {
            background: #eaeaea;
            transform: scale(1.02);
        }

        /* Активное состояние */
        .custom-checkbox-label input:checked + .checkmark {
            background: linear-gradient(145deg, #42a5f5, #1e88e5);
            border-color: #1e88e5;
            box-shadow: 0 0 8px rgba(33,150,243,0.5);
        }

        /* Галочка */
        .checkmark::after {
            content: "";
            position: absolute;
            left: 6px;
            top: 2px;

            width: 6px;
            height: 11px;

            border: solid white;
            border-width: 0 2px 2px 0;

            transform: rotate(45deg) scale(0);
            transition: transform 0.2s ease;
        }

        /* Показ галочки */
        .custom-checkbox-label input:checked + .checkmark::after {
            transform: rotate(45deg) scale(1);
        }

        /* Текст */
        .label-text {
            font-size: 14px;
            color: #333;
            line-height: 1.4;
        }

        /* Ссылка */
        .label-text a {
            color: #1e88e5;
            text-decoration: none;
        }

        .label-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body style="background: url('<?php echo $mainBg['image_path']; ?>') center/cover no-repeat fixed;">
    <!-- Header -->
    <?php
    // Если сессия еще не запущена
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Простая проверка: если id админа есть в сессии, значит он вошел
    $isAdmin = isset($_SESSION['admin_id']);
    ?>

        <header class="main-header">
            <div class="container">
                <div class="header-content">
                    <!-- Логотип -->
                    <a href="/index.php" class="logo">
                        <img src="/<?php echo getSetting($conn, 'logo_path'); ?>" alt="<?php echo getSetting($conn, 'company_name'); ?>">
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
                        <!-- <a href="/admin/login.php" class="btn-admin">
                            <span class="admin-icon">🔐</span>
                            <span class="admin-text">Вход</span>
                        </a> -->
                        
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
                <!-- <div class="mobile-admin-btn">
                    <a href="/admin/login.php" class="btn-admin">
                        <span class="admin-icon">🔐</span>
                        <span class="admin-text">Вход в админку</span>
                    </a>
                </div>-->
            </div>
        </div>
        
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        
        // Обновление кнопки админа
        // if (isAdmin) {
        //     const adminBtn = document.querySelector('.btn-admin');
        //     if (adminBtn) {
        //         adminBtn.innerHTML = '<span class="admin-icon">👑</span><span class="admin-text">Админка</span>';
        //         adminBtn.href = '/admin/index.php';
        //     }
            
        //     const mobileAdminBtn = document.querySelector('.mobile-admin-btn .btn-admin');
        //     if (mobileAdminBtn) {
        //         mobileAdminBtn.innerHTML = '<span class="admin-icon">👑</span><span class="admin-text">Админка</span>';
        //         mobileAdminBtn.href = '/admin/index.php';
        //     }
        // }
        
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

    <div class="error-page">
        <div class="creative-card">
            <!-- Забавная иконка "документ убежал" -->
            <div class="error-icon">📄🏃‍♂️</div>
            <div class="error-code">404</div>
            <h1 class="error-title">Страница не найдена</h1>
            <div class="funny-text">
                Похоже, страница, которую вы ищете, <br>
                ушла на незапланированный перерыв.
            </div>
            <div class="coffee-break">
                ☕ Возможно, пьет кофе с нашим сервером
            </div>
            <a href="/" class="home-button">
                <span>🏠</span> Вернуться на главную
            </a>
            <div class="robot-trace">
                <span>🔍</span> Поисковый робот уже на месте <span>⚙️</span>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Быстрые ссылки</h3>
                    <ul class="footer-links">
                        <?php
                        //$footerLinks = explode("\n", getContentBlock($conn, 'footer_links')['content']);
                        $menuLinks = getMenuItems($conn);
                        foreach ($menuLinks as $row) {
                            echo '<li><a href="' . $row['url'] . '">' . trim($row['title']) . '</a></li>';
                        }
                        ?>
                        <li><a href="/../privacy.php">Политика обработки персональных данных</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h3>Контакты</h3>
                    <div class="contact-info">
                        <p><?php echo getSetting($conn, 'company_address'); ?></p>
                        <p>Телефон: <?php echo getSetting($conn, 'phone'); ?></p>
                        <p>Email: <?php echo getSetting($conn, 'company_email'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p><?php echo getSetting($conn, 'copyright_text'); ?> | <?php echo getSetting($conn, 'developer_text'); ?></p>
            </div>
        </div>
    </footer>
</body>
</html>