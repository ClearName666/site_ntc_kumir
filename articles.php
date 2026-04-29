<?php
// Подключаем функции с правильным путем
require_once __DIR__. '/includes/functions.php';
require_once __DIR__. '/config/config.php';

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
    <link rel="stylesheet" href="assets/css/articles.css?version=<?php echo $version_code; ?>">
    
</head>
</head>
<body style="<?php if (!empty($article['image_path'])): ?>
    background: url('<?= htmlspecialchars($article['image_path']) ?>') center/cover no-repeat fixed;
<?php else: ?>background: url('/static/background.jpg') center/cover no-repeat fixed;<?php endif; ?>">
    <!-- Header -->
    <?php include $headerPath; ?>
    
    <!-- Основной контент -->
    <?php if ($article): ?>
        <style>
        /* плавное исчезновение skeleton */
        .skeleton-hide {
            animation: skeletonFadeOut 0.6s ease forwards;
        }

        @keyframes skeletonFadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }

        /* контент появляется мягче */
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

        /* ===== Skeleton ===== */
        .skeleton-container {
            max-width: 900px;
            margin: 0 auto;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            background-color: var(--bg-white-transparent);
            backdrop-filter: blur(10px);
        }

        /* header */
        .skeleton-header {
            padding: 3rem 3rem 2rem;
            border-bottom: var(--border-light);
            text-align: center;
        }

        /* body */
        .skeleton-body {
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
            height: 36px;
            width: 60%;
            margin: 0 auto 20px;
        }

        .skeleton-meta {
            height: 14px;
            width: 40%;
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

        /* плавное появление */
        .fade-in {
            animation: fadeIn 0.4s ease forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hidden {
            display: none;
        }

        #skeleton {
            position: absolute;
            inset: 0;
            z-index: 2;
        }
        </style>


        <section class="article-detail">
            <div class="container"  style="position: relative;">

                <!-- ===== Skeleton ===== -->
                <div id="skeleton" class="skeleton-container">

                    <div class="skeleton-header">
                        <div class="skeleton-item skeleton-title"></div>
                        <div class="skeleton-item skeleton-meta"></div>
                    </div>

                    <div class="skeleton-item skeleton-image"></div>

                    <div class="skeleton-body">
                        <div class="skeleton-item skeleton-text"></div>
                        <div class="skeleton-item skeleton-text"></div>
                        <div class="skeleton-item skeleton-text short"></div>
                    </div>

                </div>


                <!-- ===== Реальный контент ===== -->
                <article id="realContent" class="article-container hidden">
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
                        <?php echo $article['content']; ?>
                    </div>

                    <div class="back-link-container">
                        <a href="articles.php" class="back-link">
                            ← Назад к статьям
                        </a>
                    </div>
                </article>

            </div>
        </section>


        <script>
            window.addEventListener("load", () => {
                const skeleton = document.getElementById("skeleton");
                const content = document.getElementById("realContent");

                // сначала показываем контент (но прозрачный)
                content.classList.remove("hidden");
                content.style.opacity = "0";

                requestAnimationFrame(() => {
                    content.classList.add("content-show");
                });

                // через небольшую задержку убираем skeleton
                setTimeout(() => {
                    skeleton.classList.add("skeleton-hide");

                    setTimeout(() => {
                        skeleton.remove();
                    }, 600);
                }, 200);
            });
        </script>
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