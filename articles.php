<?php
// Подключаем функции с правильным путем
require_once __DIR__. '/includes/functions.php';

// подключаемся к базе 
$conn = getDBConnection();

// Проверяем, запрошена ли конкретная статья
$article = null;
if (isset($_GET['article']) && !empty($_GET['article'])) {
    $article = getArticleBySlug($conn, $_GET['article']);
    
    if ($article) {
        // 1. Сначала плюсуем в базе
        incrementArticleViews($conn, $article['id']);
        
        // 2. Сразу подменяем значение в текущем массиве на актуальное из БД
        // Таким образом, на текущей странице сразу будет +1
        $article['views'] = getActualViews($conn, $article['id']);
    }
}

// Устанавливаем мета-данные страницы
if ($article) {
    $pageTitle = htmlspecialchars($article['title']) . ' - ' . getSetting($conn, 'site_title');
    $pageDescription = htmlspecialchars(strip_tags($article['excerpt'] ?? $article['content']));
    $pageDescription = safeSubstr($pageDescription, 0, 160);
    $pageImage = !empty($article['image_path']) ? $article['image_path'] : getSetting($conn, 'logo_path');
} else {
    $pageTitle = 'Статьи - ' . getSetting($conn, 'site_title');
    $pageDescription = 'Полезные материалы и новости о современных технологиях учета энергоресурсов';
    $pageImage = getSetting($conn, 'logo_path');
}

// Подготавливаем данные для страницы со статьями
if (!$article) {
    $articles = getArticles($conn);
}

// Определяем путь к header и footer
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
    
    <!-- Open Graph для соцсетей -->
    <?php if ($article): ?>
    <meta property="og:title" content="<?= htmlspecialchars($article['title']) ?>">
    <meta property="og:description" content="<?= $pageDescription ?>">
    <meta property="og:image" content="<?= $pageImage ?>">
    <meta property="og:type" content="article">
    <meta property="article:published_time" content="<?= $article['published_at'] ?>">
    <meta property="article:author" content="<?= htmlspecialchars($article['author'] ?? 'НТЦ КУМИР') ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= getSetting($conn, 'favicon_path') ?>" type="image/x-icon">
    
    <!-- Стили -->
    <link rel="stylesheet" href="assets/css/articles.css">
    
</head>
<body>
    <!-- Header -->
    <?php include $headerPath; ?>
    
    <!-- Основной контент -->
    <?php if ($article): ?>
        <!-- Страница отдельной статьи -->
        <section class="article-detail">
            <div class="container">
                <article class="article-container">
                    <header class="article-header">
                        <h1 class="page-title"><?= htmlspecialchars($article['title']) ?></h1>
                        <div class="article-meta-stack">
                            <?php if (!empty($article['author'])): ?>
                                <span class="article-author">👤 <?= htmlspecialchars($article['author']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($article['published_at'])): ?>
                                <span class="article-date">
                                    📅 <?= date('d.m.Y', strtotime($article['published_at'])) ?>
                                </span>
                            <?php endif; ?>
                            <span class="article-views">👁 <?= $article['views'] ?> просмотров</span>
                        </div>
                    </header>
                    
                    <?php if (!empty($article['image_path'])): ?>
                        <img src="<?= htmlspecialchars($article['image_path']) ?>" 
                             alt="<?= htmlspecialchars($article['title']) ?>" 
                             class="article-detail-image"
                             loading="lazy">
                    <?php endif; ?>
                    
                    <div class="article-body">
                        <?= nl2br(htmlspecialchars($article['content'])) ?>
                    </div>
                    
                    <div class="back-link-container">
                        <a href="articles.php" class="back-link" aria-label="Вернуться к списку статей">
                            ← Назад к статьям
                        </a>
                    </div>
                </article>
            </div>
        </section>
    <?php else: ?>
        <!-- Список всех статей -->
        <section class="articles-page">
            <div class="container">
                <header class="page-header">
                    <h1 class="page-title">Статьи</h1>
                    <p class="page-description">Полезные материалы и новости о современных технологиях учета энергоресурсов</p>
                </header>
                
                <?php 
                // Проверяем, есть ли статьи
                if (isset($articles) && !empty($articles)): 
                ?>
                    <div class="articles-stack">
                        <?php foreach ($articles as $index => $item): ?>
                            <article class="article-card-stack" data-index="<?= $index ?>">
                                <a href="?article=<?= urlencode($item['slug']) ?>" 
                                   class="article-link-stack"
                                   aria-label="Читать статью: <?= htmlspecialchars($item['title']) ?>">
                                   
                                    <?php if (!empty($item['image_path'])): ?>
                                        <div class="article-image-container">
                                            <img src="<?= htmlspecialchars($item['image_path']) ?>" 
                                                 alt="<?= htmlspecialchars($item['title']) ?>"
                                                 loading="lazy">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="article-content-stack">
                                        <h2 class="article-title-stack">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </h2>
                                        
                                        <div class="article-excerpt-stack">
                                            <?php 
                                            $excerpt = !empty($item['excerpt']) 
                                                ? $item['excerpt']
                                                : strip_tags($item['content']);
                                            echo htmlspecialchars(safeSubstr($excerpt, 0, 200) . '...');
                                            ?>
                                        </div>
                                        
                                        <div class="article-meta-stack">
                                            <div class="meta-info">
                                                <?php if (!empty($item['author'])): ?>
                                                    <span class="article-author">👤 <?= htmlspecialchars($item['author']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['published_at'])): ?>
                                                    <span class="article-date">
                                                        📅 <?= date('d.m.Y', strtotime($item['published_at'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="read-more">Читать далее</span>
                                        </div>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-articles">
                        <p>Статьи скоро будут добавлены.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Footer -->
    <?php include $footerPath; ?>
    
    <!-- Скрипты -->
    <script src="assets/js/articles.js"></script>
</body>
</html>