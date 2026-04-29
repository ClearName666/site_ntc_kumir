<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';
require_once __DIR__. '/config/config.php';

// подключаемся к базе 
$conn = getDBConnection();

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

// Устанавливаем мета-данные
$pageTitle = 'Вопрос-ответ - ' . getSetting($conn, 'site_title');
$pageDescription = 'Часто задаваемые вопросы и ответы по оборудованию и услугам НТЦ КУМИР.';

// Определяем пути
$headerPath = __DIR__. '/includes/header.php';
$footerPath = __DIR__. '/includes/footer.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $pageDescription ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $pageDescription ?>">
    <meta property="og:image" content="<?= getSetting($conn, 'logo_path') ?>">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" href="<?= getSetting($conn, 'favicon_path') ?>" type="image/x-icon">
    
    <!-- Стили -->
    <link rel="stylesheet" href="assets/css/faq.css?version=<?php echo $version_code; ?>">
    
</head>
<body style="background: url('/static/background.jpg') center/cover no-repeat fixed;">
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
                    
                    <button type="submit" class="submit-btn">Задать вопрос</button>
                </form>
            </section>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include $footerPath; ?>
    
    <!-- Скрипты -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/faq.js"></script>
</body>
</html>