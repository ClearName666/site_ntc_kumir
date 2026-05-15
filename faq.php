<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';
require_once __DIR__. '/config/config.php';

// подключаемся к базе 
$conn = getDBConnection();
$showForm = (getSetting($conn, 'form_view') == 1);

// --- БЛОК ОБРАБОТКИ AJAX ЗАПРОСА ---
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    
    $name = isset($_POST['name']) ? cleanInput($_POST['name']) : '';
    $email = isset($_POST['email']) ? cleanInput($_POST['email']) : '';
    $category = isset($_POST['category']) ? cleanInput($_POST['category']) : 'Общее';
    $question = isset($_POST['question']) ? cleanInput($_POST['question']) : '';

    if (empty($name) || empty($email) || empty($question)) {
        echo json_encode(['status' => 'error', 'message' => 'Заполните все поля!']);
        exit;
    }

    // Используем функцию, которую мы добавили в functions.php
    if (addFAQQuestion($conn, $name, $email, $category, $question)) {
        echo json_encode(['status' => 'success', 'message' => 'Вопрос отправлен и появится после модерации!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных.']);
    }
    exit; // Важно! Прерываем выполнение, чтобы не грузить HTML в ответ на AJAX
}

// Получаем данные
$categories = getFAQCategories($conn);
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
$faqItems = getFAQ($conn, $selectedCategory);
$mainBg = getImage($conn, 'image_background_all');

// Устанавливаем мета-данные
$pageTitle = 'Вопрос-ответ - ' . getSetting($conn, 'site_title');
$pageDescription = 'Часто задаваемые вопросы и ответы по оборудованию и услугам НТЦ КУМИР.';
$pageKeyword = "помощь НТЦ КУМИР, поддержка АСКУЭ, настройка модема M32, вопросы по телеметрии, инструкция кумир, как подключить счетчик, технические вопросы ЖКХ, база знаний НТЦ КУМИР, обслуживание подстанций вопросы, FAQ автоматизация";

// --- ДОПОЛНИТЕЛЬНАЯ ПОДГОТОВКА ДЛЯ SEO И СОЦСЕТЕЙ ---
$defaultSocialImage = getSetting($conn, 'social_default_image');
$ogImage = !empty($defaultSocialImage) ? $defaultSocialImage : getSetting($conn, 'logo_path');

if (empty($pageDescription)) {
    $pageDescription = 'Ответы на часто задаваемые вопросы. Задайте свой вопрос специалистам НТЦ КУМИР.';
}

$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Определяем пути
$headerPath = __DIR__. '/includes/header.php';
$footerPath = __DIR__. '/includes/footer.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, interactive-widget=resizes-content">
    <title><?= $pageTitle ?></title>

    <!-- SEO -->
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeyword) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <meta name="author" content="НТЦ КУМИР">
    <meta name="copyright" content="<?= date('Y') ?> <?= htmlspecialchars(getSetting($conn, 'site_title')) ?>">
    <link rel="canonical" href="<?= $currentUrl ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="<?= htmlspecialchars(getSetting($conn, 'site_title')) ?>">
    <meta property="og:url" content="<?= $currentUrl ?>">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:image" content="<?= $ogImage ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:type" content="website">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $pageTitle ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="twitter:image" content="<?= $ogImage ?>">
    <?php if (getSetting($conn, 'twitter_site')): ?>
    <meta name="twitter:site" content="<?= htmlspecialchars(getSetting($conn, 'twitter_site')) ?>">
    <?php endif; ?>

    <!-- Мобильный вид -->
    <meta name="theme-color" content="#ffffff">

    <!-- Favicon и RSS -->
    <link rel="icon" href="<?= getSetting($conn, 'favicon_path') ?>" type="image/x-icon">
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars(getSetting($conn, 'site_title')) ?> – Часто задаваемые вопросы" href="/rss.xml">

    <!-- Стили -->
    <link rel="stylesheet" href="assets/css/faq.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/style.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/responsive.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/header.css?version=<?php echo $version_code; ?>">
</head>
<body style="background: url('<?php echo $mainBg['image_path']; ?>') center/cover no-repeat fixed;">
    <!-- Header -->
    <?php include $headerPath; ?>
    
    <section class="faq-page">
        <div class="container">
            <!-- Шапка страницы -->
            <header class="page-header">
                <h1 class="page-title">Вопрос-ответ</h1>
                <p class="page-description">Ответы на часто задаваемые вопросы по оборудованию и услугам</p>
            </header>
            
            <!-- Категории -->
            <?php if (!empty($categories)): ?>
                <section class="categories-section">
                    <h2 class="categories-title">Категории вопросов</h2>
                    <div class="categories-grid">
                        <a href="faq.php" class="category-btn all <?= !$selectedCategory ? 'active' : '' ?>">
                            Все вопросы
                        </a>
                        <?php foreach ($categories as $category): ?>
                            <a href="faq.php?category=<?= urlencode($category) ?>" 
                               class="category-btn <?= $selectedCategory === $category ? 'active' : '' ?>">
                                <?= htmlspecialchars($category) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
            
            <!-- FAQ список -->
            <section class="faq-section">
                <?php if (!empty($faqItems)): ?>
                    <?php renderFAQ($faqItems, true); ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem 1rem;">
                        <p style="font-size: 1.125rem; color: #7f8c8d;">Вопросы в выбранной категории скоро будут добавлены.</p>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- Форма своего вопроса -->
              <?php if ($showForm): ?>
            <section class="question-form-section">
                <h2 class="form-title">Задайте свой вопрос</h2>
                <form class="question-form" id="questionForm">
                    <div class="form-group">
                        <label class="form-label" for="question_name">Ваше имя *</label>
                        <input type="text" id="question_name" name="name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="question_email">Email *</label>
                        <input type="email" id="question_email" name="email" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="question_category">Категория вопроса</label>
                        <select id="question_category" name="category" class="form-input">
                            <option value="">Выберите категорию</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category) ?>">
                                    <?= htmlspecialchars($category) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="question_text">Ваш вопрос *</label>
                        <textarea id="question_text" name="question" class="form-textarea" required></textarea>
                    </div>

                    <div class="custom-checkbox-wrapper">
                        <label class="custom-checkbox-label">
                            <input type="checkbox" name="agreement" required>
                            <span class="checkmark"></span>
                            <span class="label-text">
                                Я согласен с <a href="/privacy.php" target="_blank"">политикой конфиденциальности</a> *
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" class="submit-btn">Задать вопрос</button>
                </form>
            </section>
             <?php endif; ?>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include $footerPath; ?>
    
    <!-- Скрипты -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/faq.js"></script>
</body>
</html>