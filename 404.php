<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';
require_once __DIR__. '/config/config.php';

// подключаемся к базе 
$conn = getDBConnection();


// Определяем пути
$headerPath = __DIR__. '/includes/header.php';
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
            background: url('/static/background.jpg') center/cover no-repeat fixed;
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
    </style>
</head>
<body>
    <!-- Header -->
    <?php include $headerPath; ?>

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
    <?php include $footerPath; ?>
</body>
</html>