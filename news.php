<?php
// Подключаем функции
require_once __DIR__. '/includes/functions.php';
require_once __DIR__. '/config/config.php';

// подключаемся к базе 
$conn = getDBConnection();

// Настройки пагинации
$newsPerPage = 6;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $newsPerPage;

// Проверяем, запрошена ли конкретная новость 
$newsItem = null;
if (isset($_GET['news']) && !empty($_GET['news'])) {
    $newsItem = getNewsBySlug($conn, $_GET['news']);

    // 1. Увеличиваем просмотры в БД (тихий запрос)
    incrementNewsViews($conn, $newsItem["id"]);

    // 2. Получаем СВЕЖЕЕ число просмотров, игнорируя то, что в кэше $newsItem
    $currentViews = getActualNewsViews($conn, $newsItem['id']);
}

// Устанавливаем мета-данные страницы
if ($newsItem) {
    $pageTitle = htmlspecialchars($newsItem['title']) . ' - Новости - ' . getSetting($conn, 'site_title');
    
    // Используем безопасную функцию для обрезания описания
    $pageDescription = truncateDescription($newsItem['excerpt'] ?? $newsItem['content'], 160);
    
    $pageImage = !empty($newsItem['image_path']) ? $newsItem['image_path'] : getSetting($conn, 'logo_path');
} else {
    $pageTitle = 'Новости - ' . getSetting($conn, 'site_title');
    $pageDescription = 'Актуальные новости и события компании НТЦ КУМИР';
    $pageImage = getSetting($conn, 'logo_path');
}

// Получаем данные для списка новостей
if (!$newsItem) {
    $newsList = getNews($conn, $newsPerPage, $offset);
    $totalNews = getNewsCount($conn);
    $totalPages = ceil($totalNews / $newsPerPage);
}

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
    <?php if ($newsItem): ?>
    <meta property="og:title" content="<?= htmlspecialchars($newsItem['title']) ?>">
    <meta property="og:description" content="<?= $pageDescription ?>">
    <meta property="og:image" content="<?= $pageImage ?>">
    <meta property="og:type" content="article">
    <meta property="article:published_time" content="<?= $newsItem['published_at'] ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= getSetting($conn, 'favicon_path') ?>" type="image/x-icon">
    
    <!-- Стили для страницы новостей -->
    <link rel="stylesheet" href="assets/css/news.css?version=<?php echo $version_code; ?>">

</head>
<body style="<?php if (!empty($newsItem['image_path'])): ?>
    background: url('<?= htmlspecialchars($newsItem['image_path']) ?>') center/cover no-repeat fixed;
<?php else: ?>background: url('/static/background.jpg') center/cover no-repeat fixed;<?php endif; ?>">
    <!-- Header -->
    <!-- Header -->
    <?php include $headerPath; ?>
    
    <?php if ($newsItem): ?>
        <style>
        /* ===== Skeleton контейнер (повторяет news-detail-container) ===== */
        .skeleton-news {
            max-width: 900px;
            margin: 0 auto;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            background-color: var(--bg-white-transparent);
            backdrop-filter: blur(10px);
            position: absolute;
            inset: 0;
            z-index: 2;
        }

        /* header */
        .skeleton-news-header {
            padding: 3rem 3rem 2rem;
            border-bottom: var(--border-light);
            text-align: center;
        }

        /* body */
        .skeleton-news-body {
            padding: 2.5rem 3rem 3rem;
        }

        /* элементы */
        .skeleton-item {
            position: relative;
            overflow: hidden;
            background: #e5e7eb;
            border-radius: 8px;
        }

        /* shimmer */
        .skeleton-item::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                100deg,
                transparent 20%,
                rgba(255,255,255,0.6) 50%,
                transparent 80%
            );
            transform: translateX(-100%);
            animation: shimmer 1.4s infinite;
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }

        /* размеры */
        .skeleton-title {
            height: 34px;
            width: 65%;
            margin: 0 auto 20px;
        }

        .skeleton-meta {
            height: 14px;
            width: 50%;
            margin: 0 auto;
        }

        .skeleton-image {
            width: 100%;
            height: 400px;
        }

        .skeleton-text {
            height: 16px;
            margin-bottom: 12px;
        }

        .skeleton-text.short {
            width: 60%;
        }

        /* ===== Анимации ===== */
        .skeleton-hide {
            animation: skeletonFadeOut 0.6s ease forwards;
        }

        @keyframes skeletonFadeOut {
            to {
                opacity: 0;
            }
        }

        .content-show {
            animation: contentFadeIn 0.6s ease forwards;
        }

        @keyframes contentFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
                filter: blur(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
                filter: blur(0);
            }
        }

        .hidden {
            display: none;
        }
        </style>


        <section class="news-detail-page">
            <div class="container" style="position: relative;">

                <!-- ===== Skeleton ===== -->
                <div id="skeleton" class="skeleton-news">

                    <div class="skeleton-news-header">
                        <div class="skeleton-item skeleton-title"></div>
                        <div class="skeleton-item skeleton-meta"></div>
                    </div>

                    <div class="skeleton-item skeleton-image"></div>

                    <div class="skeleton-news-body">
                        <div class="skeleton-item skeleton-text"></div>
                        <div class="skeleton-item skeleton-text"></div>
                        <div class="skeleton-item skeleton-text short"></div>
                    </div>

                </div>


                <!-- ===== РЕАЛЬНЫЙ КОНТЕНТ ===== -->
                <article id="realContent" class="news-detail-container hidden">

                    <header class="news-detail-header">
                        <h1 class="news-detail-title"><?= htmlspecialchars($newsItem['title']) ?></h1>
                        
                        <div class="news-detail-meta">
                            <?php if (!empty($newsItem['published_at'])): ?>
                                <div class="news-date">
                                    📅 <?= date('d.m.Y H:i', strtotime($newsItem['published_at'])) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($newsItem['author'])): ?>
                                <span class="news-author">👤 <?= htmlspecialchars($newsItem['author']) ?></span>
                            <?php endif; ?>
                            
                            <span class="news-views">👁 <?= $currentViews ?? '-' ?> просмотров</span>
                        </div>
                    </header>

                    <?php if (!empty($newsItem['image_path'])): ?>
                        <img src="<?= $newsItem['image_path'] ?>"
                            alt="<?= htmlspecialchars($newsItem['title']) ?>"
                            class="news-detail-image"
                            loading="lazy">
                    <?php endif; ?>

                    <div class="news-detail-body">
                        <?= $newsItem['content'] ?>
                    </div>

                    <div class="back-to-news">
                        <a href="news.php" class="btn">
                            ← К списку новостей
                        </a>
                    </div>

                </article>

            </div>
        </section>


        <script>
        window.addEventListener("load", () => {
            const skeleton = document.getElementById("skeleton");
            const content = document.getElementById("realContent");

            // показываем контент под skeleton
            content.classList.remove("hidden");
            content.style.opacity = "0";

            requestAnimationFrame(() => {
                content.classList.add("content-show");
            });

            // плавно убираем skeleton
            setTimeout(() => {
                skeleton.classList.add("skeleton-hide");

                setTimeout(() => {
                    skeleton.remove();
                }, 600);
            }, 200);
        });
        </script>
    <?php else: ?>
        <!-- Список всех новостей -->
        <section class="news-page">
            <div class="container">
                <header class="news-header">
                    <h1 class="news-page-title">Новости</h1>
                    <p class="news-page-description">Следите за последними событиями и обновлениями компании НТЦ КУМИР</p>
                </header>
                
                <?php if (!empty($newsList)): ?>
                    <div class="news-grid" id="newsGrid">
                        <?php foreach ($newsList as $index => $item): 
                            // Проверяем, новая ли это новость (менее 7 дней)
                            $isNew = false;
                            if (!empty($item['published_at'])) {
                                $publishDate = new DateTime($item['published_at']);
                                $now = new DateTime();
                                $diff = $now->diff($publishDate)->days;
                                $isNew = $diff < 7;
                            }
                        ?>
                            <article class="news-card-stack <?= $isNew ? 'new' : '' ?>" 
                                     data-index="<?= $index ?>"
                                     data-date="<?= $item['published_at'] ?>">
                                <a href="?news=<?= urlencode($item['slug']) ?>" class="news-link">
                                    <?php if (!empty($item['image_path'])): ?>
                                        <div class="news-image-container">
                                            <img src="<?= $item['image_path'] ?>" 
                                                 alt="<?= htmlspecialchars($item['title']) ?>"
                                                 loading="lazy">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="news-content">
                                        <?php if (!empty($item['published_at'])): ?>
                                            <div class="news-date">
                                                <span class="date-icon">📅</span>
                                                <time datetime="<?= date('Y-m-d', strtotime($item['published_at'])) ?>">
                                                    <?= date('d.m.Y', strtotime($item['published_at'])) ?>
                                                </time>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <h3 class="news-title">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </h3>
                                        
                                        <?php if (!empty($item['excerpt'])): ?>
                                            <p class="news-excerpt">
                                                <?= htmlspecialchars($item['excerpt']) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="news-meta">
                                            <?php if (!empty($item['author'])): ?>
                                                <span class="news-author"><?= htmlspecialchars($item['author']) ?></span>
                                            <?php endif; ?>
                                            <span class="read-more">Подробнее →</span>
                                        </div>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                        <?php renderNewsPagination($currentPage, $totalPages, 'news.php'); ?>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-news" style="text-align: center; padding: 4rem 1rem;">
                        <p style="font-size: 1.125rem; color: #7f8c8d;">Новости скоро будут добавлены.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
    
    <!-- Footer -->
    <?php include $footerPath; ?>
    
    <!-- Скрипты -->
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/news.js"></script>
</body>
</html>