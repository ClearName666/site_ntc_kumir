<?php


require_once __DIR__. '/includes/functions.php';
require_once __DIR__. '/config/config.php';


// подключаемся к базе 
$conn = getDBConnection();
$mainTitle = getContentBlock($conn, 'main_title');
//$mainBgStart = getImage($conn, 'main_background');
$heroImage = getImage($conn, 'hero_foreground');
$mapLocation = getMapLocation($conn);
$videoId = getSetting($conn, 'video_id');
// $videoThumb = getImage($conn, 'video_thumbnail'); 
$mainBg = getImage($conn, 'image_background_all');

// Получение настроек отображения 
$for_whom_view = (getSetting($conn, 'for_whom_view') == 1);
$our_products_view = (getSetting($conn, 'our_products_view') == 1);
$advantages_of_our_system_view = (getSetting($conn, 'advantages_of_our_system_view') == 1);
$about_the_company_view = (getSetting($conn, 'about_the_company_view') == 1);
$geography_of_application_view = (getSetting($conn, 'geography_of_application_view') == 1);
$news_artcles_view = (getSetting($conn, 'news_artcles_view') == 1);
$site_new_view = (getSetting($conn, 'site_new_view') == 1);


//  Настройки дизайна секций
$hero_background = getSetting($conn, 'hero_background');
$for_whom_background = getSetting($conn, 'for_whom_background');
$our_products_background = getSetting($conn, 'our_products_background');
$advantages_of_our_system_background = getSetting($conn, 'advantages_of_our_system_background');
$about_the_company_background = getSetting($conn, 'about_the_company_background');
$geography_of_application_background = getSetting($conn, 'geography_of_application_background');
$news_artcles_background = getSetting($conn, 'news_artcles_background');



// --- ДОПОЛНИТЕЛЬНАЯ ПОДГОТОВКА ДЛЯ SEO И СОЦСЕТЕЙ ---
$siteTitle = getSetting($conn, 'site_title');
$pageTitle = $siteTitle;
$pageDescription = 'НТЦ КУМИР — разработка и производство систем автоматизированного учета ресурсов (АСКУЭ), промышленных модемов серии M32 и программного обеспечения для ЖКХ и промышленности в Иркутске.';
$pageKeywords = 'кумир, ntc kumir, аскуэ, мониторинг ресурсов, модем m32, учет тепла, автоматизация жкх, телеметрия, радиомодем, рм81, сбор показаний';

$defaultSocialImage = getSetting($conn, 'social_default_image');
$ogImage = !empty($defaultSocialImage) ? $defaultSocialImage : ($heroImage['image_path'] ?? getSetting($conn, 'logo_path'));
$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, interactive-widget=resizes-content">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <!-- SEO -->
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeywords) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <meta name="author" content="НТЦ КУМИР">
    <meta name="copyright" content="<?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?>">
    <link rel="canonical" href="<?= $currentUrl ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteTitle) ?>">
    <meta property="og:url" content="<?= $currentUrl ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:image" content="<?= $ogImage ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:type" content="website">

    <!-- Мобильный вид -->
    <meta name="theme-color" content="#ffffff">

    <!-- Favicon и RSS -->
    <link rel="icon" href="<?= getSetting($conn, 'favicon_path') ?>" type="image/x-icon">
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($siteTitle) ?> – новости и статьи" href="/rss.xml">

    <!-- Стили -->
    <link rel="stylesheet" href="/assets/css/style.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/responsive.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/header.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/audience.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/mapMain.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/newMain.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/newsArticlesMain.css?version=<?php echo $version_code; ?>">
</head>
<body style="background: url('<?php echo $mainBg['image_path']; ?>') center/cover no-repeat fixed;">

<?php require_once __DIR__. '/includes/header.php';?>
<main>
    <!-- Hero Section - обновляем верхний отступ -->
    <section class="hero" style="<?= $hero_background ?>">
        <div class="hero-background">
        </div>
        
        <?php if ($site_new_view): ?>
            <div class="container-main">
                <div class="hero-new-content">
                    
                    <!-- Логотип компании -->
                    <div class="hero-new-logo">
                        <img src="/assets/images/static/new/001 - logo.svg" alt="НТЦ КУМИР" class="hero-logo-img">
                    </div>

                    <!-- Главный заголовок из базы данных -->
                    <h1 class="hero-new-title"><?php echo $siteTitle; ?></h1>
                    
                    <!-- Подзаголовок -->
                    <p class="hero-new-subtitle">Высокотехнологичные ИТ-решения для сложных задач и устойчивого роста</p>

                    <!-- Блок с кнопками навигации -->
                    <div class="hero-new-buttons">
                        <a href="/products.php" class="btn-hero btn-primary">Продукция <span class="arrow">›</span></a>
                        <a href="/articles.php" class="btn-hero btn-secondary">Статьи <span class="arrow">›</span></a>
                        <a href="/contacts.php" class="btn-hero btn-secondary">Контакты <span class="arrow">›</span></a>
                    </div>

                    <!-- Нижняя полупрозрачная панель преимуществ -->
                    <div class="hero-features-panel">
                        
                        <div class="panel-item">
                            <div class="panel-icon">
                                <!-- Иконка Разработка -->
                                <img src="/assets/images/static/new/chip-component-svgrepo-com.svg" alt="">
                            </div>
                            <h3>Разработка и интеграция</h3>
                            <p>Создаём надёжные программные решения и системы</p>
                        </div>

                        <div class="panel-item">
                            <div class="panel-icon">
                                <!-- Иконка Безопасность -->
                                <img src="/assets/images/static/new/security-svgrepo-com.svg" alt="">
                            </div>
                            <h3>Информационная безопасность</h3>
                            <p>Защищаем данные и обеспечиваем соответствие стандартам</p>
                        </div>

                        <div class="panel-item">
                            <div class="panel-icon">
                                <!-- Иконка Аналитика -->
                                <img src="/assets/images/static/new/analytics-reference-svgrepo-com.svg" alt="">
                            </div>
                            <h3>Аналитика и данные</h3>
                            <p>Превращаем данные в полезные инсайты для бизнеса</p>
                        </div>

                        <div class="panel-item">
                            <div class="panel-icon">
                                <!-- Иконка Облако -->
                                <img src="/assets/images/static/new/cloud-svgrepo-com.svg" alt="">
                            </div>
                            <h3>Инфраструктура и облака</h3>
                            <p>Проектируем и развиваем отказоустойчивую ИТ-инфраструктуру</p>
                        </div>

                    </div>

                </div>
            </div>
        <?php else: ?>
            <div class="container-main">
                <div class="hero-content">
                    <div class="hero-main">
                        <div class="hero-text">
                            <h1 class="main-title"><?php echo $mainTitle['content']; ?></h1>
                            
                            <div class="features-list">
                                <?php renderFeatures($conn); ?>
                                <a href="contacts.php#map-location" class="hero-link-wrapper" style="text-decoration: none; color: inherit; display: block;">
                                    <div class="feature-item">
                                        <div class="map-content">
                                            <div class="map-icon">
                                                <img src="/assets/images/static/map.svg" alt="Карта" class="map-svg-icon">
                                            </div>
                                            <div class="map-text map-text-location">
                                                <h3>«НТЦ «КУМИР» на карте</h3>
                                                <p>N<?php echo $mapLocation['latitude']; ?>, E<?php echo $mapLocation['longitude']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- <div class="contacts-section">
                                <div class="analytics-badge">
                                    <span>📊</span>
                                    <span>Дистанционный контроль и управление</span>
                                </div>
                            </div> -->

                            <!-- Кнопка для перехода к системе -->
                            <!-- <div class="contacts-section">
                                <a href="https://v4.ntckumir.ru/" class="analytics-badge" target="_blank" rel="noopener noreferrer">
                                    <span>📊</span>
                                    <span>Дистанционный контроль и управление</span>
                                </a>
                            </div> -->
                        </div>
                        <div class="hero-visual">
                            <div class="hero-image-container">
                                <img src="<?php echo $heroImage['image_path']; ?>" alt="<?php echo $heroImage['alt_text']; ?>" class="hero-image">
                            </div>
                            
                            <div class="hero-card card-energy">
                                <div class="card-pattern-box">
                                    <img src="/assets/images/static/firstDisplay--3.png" alt="" class="pattern-img"> 
                                </div>
                                <div class="card-title">Учет энергоресурсов</div>
                            </div>

                            <div class="hero-card card-analytics">
                                <div class="card-pattern-box">
                                    <img src="/assets/images/static/firstDisplay--1.png" alt="" class="pattern-img">
                                </div>
                                <div class="card-title">Аналитика</div>
                            </div>

                            <div class="hero-card card-control">
                                <div class="card-pattern-box">
                                    <img src="/assets/images/static/firstDisplay--2.png" alt="" class="pattern-img">
                                </div>
                                <div class="card-title">Дистанционный контроль и управление</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </section>

    <?php if ($for_whom_view): ?>
        <section class="for-whom-section" style="<?= $for_whom_background ?>">
            <div class="container-main">
                
                <h2 class="section-title mobile-only-title">Для кого</h2>
                
                <div class="for-whom-wrapper">
                    
                    <div class="for-whom-background">
                        <img src="/assets/images/static/category_background.svg" alt="Линии распределения" class="lines-img">
                        
                        <div class="center-desktop-circle">
                            <span>Для кого</span>
                        </div>
                    </div>
                    <div class="target-item pos-top-1">
                        <div class="icon-box"><img src="/assets/images/static/thumb__166_0_0_0_auto.png" alt=""></div>
                        <p>ЖКХ: управляющим компаниям и ТСЖ</p>
                    </div>
                    <div class="target-item pos-top-2">
                        <div class="icon-box"><img src="/assets/images/static/thumb__166_0_0_0_auto(1).png" alt=""></div>
                        <p>Энергосбытовым и сетевым компаниям</p>
                    </div>
                    <div class="target-item pos-top-3">
                        <div class="icon-box"><img src="/assets/images/static/thumb__166_0_0_0_auto(2).png" alt=""></div>
                        <p>Муниципальным и государственным учреждениям</p>
                    </div>
                    <div class="target-item pos-top-4">
                        <div class="icon-box"><img src="/assets/images/static/thumb__166_0_0_0_auto(3).png" alt=""></div>
                        <p>Эксплуатантам теплоисточников и магистральных сетей</p>
                    </div>
                    <div class="target-item pos-top-5">
                        <div class="icon-box"><img src="/assets/images/static/thumb__166_0_0_0_auto(4).png" alt=""></div>
                        <p>Садовым товариществам и коттеджным поселкам</p>
                    </div>
                    <div class="target-item pos-top-6">
                        <div class="icon-box"><img src="/assets/images/static/thumb__166_0_0_0_auto(5).png" alt=""></div>
                        <p>Застройщикам</p>
                    </div>

                    <div class="target-item pos-bottom-1">
                        <div class="icon-box"><img src="/assets/images/static/thumb__166_0_0_0_auto(6).png" alt=""></div>
                        <p>Умный дом</p>
                    </div>
                    <div class="target-item pos-bottom-2">
                        <div class="icon-box"><img src="/assets/images/static/thumb__166_0_0_0_auto(7).png" alt=""></div>
                        <p>Промышленности и <br>сельскому хозяйству</p>
                    </div>
                    <div class="target-item pos-bottom-3">
                        <div class="icon-box"><img src="/assets/images/static/thumb__166_0_0_0_auto(8).png" alt=""></div>
                        <p>Экология</p>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Products/Cards Section -->
    <?php if ($our_products_view): ?>
        <section class="cards-section" style="<?= $our_products_background ?>">
            <div class="container">
                <h2 class="section-title">Наша продукция</h2>
                <div class="cards-container">
                    <?php renderCards($conn); ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Advantages Section -->
     <?php if ($advantages_of_our_system_view): ?>
        <section class="advantages-section" style="<?= $advantages_of_our_system_background ?>">
            <div class="container">
                <h2 class="section-title"><?php echo getContentBlock($conn, 'advantages_title')['content']; ?></h2>
                <?php renderAdvantages($conn); ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- About Section -->
     <?php if ($about_the_company_view): ?>
        <section class="about-section" style="<?= $about_the_company_background ?>">
            <div class="container about-container">
                <div class="about-stats">
                    <?php renderStatistics($conn); ?>
                </div>
                
                <div class="about-content">
                <div class="video-section">
                    <div class="video-container" id="video-wrapper">
                        <?php if (!empty($videoId)): ?>
                            <iframe 
                                width="100%" 
                                height="360" 
                                src="https://rutube.ru/play/embed/<?php echo $videoId; ?>/" 
                                frameborder="0" 
                                allow="autoplay; encrypted-media" 
                                allowfullscreen 
                                style="border-radius: 8px;">
                            </iframe>
                        <?php endif; ?>
                    </div>
                </div>
                    
                    <div class="company-info">
                        <?php 
                        $aboutContent = getContentBlock($conn, 'about_content');
                        $aboutFeatures = getContentBlock($conn, 'about_features');
                        ?>
                        <h2><?php echo getContentBlock($conn, 'about_title')['content']; ?></h2>
                        
                        <ul class="company-features">
                            <?php
                            $featuresList = explode("\n", $aboutFeatures['content']);
                            foreach ($featuresList as $feature) {
                                if (trim($feature)) {
                                    echo '<li>' . trim($feature) . '</li>';
                                }
                            }
                            ?>
                        </ul>
                        
                        <p class="company-description">
                            <?php echo $aboutContent['content']; ?>
                        </p>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>





        <?php if ($news_artcles_view): ?>
            <?php
                // Получаем по 3 самых свежих записи (функции взяты из вашей архитектуры)
                $homeNews = function_exists('getNews') ? getNews($conn, 3, 0) : [];
                $homeArticles = function_exists('getArticles') ? getArticles($conn) : [];

                // Если функция getArticles возвращает всё, то берем первые 3
                if (!empty($homeArticles) && count($homeArticles) > 3) {
                    $homeArticles = array_slice($homeArticles, 0, 3);
                }

                // Проверяем, есть ли хоть какой-то контент для отображения
                $hasNews = !empty($homeNews);
                $hasArticles = !empty($homeArticles);
                $hasAnyMedia = $hasNews || $hasArticles;
            ?>
            <section class="media-section" style="<?= $news_artcles_background ?>">
                <div class="container-main">
                    
                    <div class="media-section-header">
                        <div class="media-title-block">
                            <span class="media-subtitle">Статьи и новости</span>
                            <!--<h2 class="media-main-title">Медиа-центр</h2>-->
                        </div>
                        <?php if ($hasAnyMedia): ?>
                            <div class="slider-controls">
                                <button class="slider-btn prev-btn" aria-label="Назад" id="mediaPrev">‹</button>
                                <button class="slider-btn next-btn" aria-label="Вперед" id="mediaNext">›</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($hasAnyMedia): ?>
                        <div class="media-slider-viewport" id="mediaViewport">
                            <div class="media-slider-track" id="mediaTrack">

                                <?php if ($hasNews): ?>
                                    <?php foreach ($homeNews as $item): ?>
                                        <article class="media-card card-news">
                                            <a href="news.php?news=<?= urlencode($item['slug']) ?>" class="media-card-link">
                                                <div class="card-badge">Новость</div>
                                                <div class="media-card-image">
                                                    <img src="<?= !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : '/assets/images/static/placeholder.jpg' ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                                                </div>
                                                <div class="media-card-content">
                                                    <time class="media-card-date">📅 <?= date('d.m.Y', strtotime($item['published_at'])) ?></time>
                                                    <h3 class="media-card-title"><?= htmlspecialchars($item['title']) ?></h3>
                                                    <p class="media-card-excerpt">
                                                        <?= !empty($item['excerpt']) ? htmlspecialchars($item['excerpt']) : htmlspecialchars(strip_tags($item['content'])) ?>
                                                    </p>
                                                    <span class="media-card-more">Читать далее →</span>
                                                </div>
                                            </a>
                                        </article>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if ($hasArticles): ?>
                                    <?php foreach ($homeArticles as $item): ?>
                                        <article class="media-card card-article">
                                            <a href="articles.php?article=<?= urlencode($item['slug']) ?>" class="media-card-link">
                                                <div class="card-badge">Статья</div>
                                                <div class="media-card-image">
                                                    <img src="<?= !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : '/assets/images/static/placeholder.jpg' ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                                                </div>
                                                <div class="media-card-content">
                                                    <time class="media-card-date">📅 <?= date('d.m.Y', strtotime($item['published_at'])) ?></time>
                                                    <h3 class="media-card-title"><?= htmlspecialchars($item['title']) ?></h3>
                                                    <p class="media-card-excerpt">
                                                        <?php 
                                                            $excerpt = !empty($item['excerpt']) ? $item['excerpt'] : strip_tags($item['content']);
                                                            echo htmlspecialchars(mb_strimwidth($excerpt, 0, 120, "..."));
                                                        ?>
                                                    </p>
                                                    <span class="media-card-more">Читать статью →</span>
                                                </div>
                                            </a>
                                        </article>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                            </div>
                        </div>
                    <?php else: ?>
                        <div class="media-empty-state">
                            <div class="empty-icon">📂</div>
                            <h3>Раздел обновляется</h3>
                            <p>Наши специалисты уже готовят свежие материалы и актуальные новости. Загляните позже!</p>
                            <div class="empty-links">
                                <a href="news.php" class="btn-empty">Все новости</a>
                                <a href="articles.php" class="btn-empty">Все статьи</a>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </section>
        <?php endif; ?>

            <?php if ($geography_of_application_view): ?>
        <section class="geography-section" style="<?= $geography_of_application_background ?>">
            <div class="container-main">
                <h2 class="section-title">География применения</h2>
                
                <div class="map-wrapper">
                    <div class="map-bg-container">
                        <img src="/assets/images/static/map_background__lines.png" alt="Карта географии поставок" class="map-img">
                    </div>

                    <div class="map-badge pos-spb">
                        <div class="badge-dot"></div>
                        <div class="badge-content">
                            <h4>Санкт-Петербург</h4>
                        </div>
                    </div>

                    <div class="map-badge pos-moscow">
                        <div class="badge-dot"></div>
                        <div class="badge-content">
                            <h4>Москва</h4>
                            <p>Владимир</p>
                            <p>Александров</p>
                            <p>Ковров</p>
                        </div>
                    </div>

                    <div class="map-badge pos-saratov">
                        <div class="badge-dot"></div>
                        <div class="badge-content">
                            <h4>Саратов</h4>
                        </div>
                    </div>

                    <div class="map-badge pos-tyumen">
                        <div class="badge-dot"></div>
                        <div class="badge-content">
                            <h4>Тюмень</h4>
                            <p>Тобольск</p>
                            <p>Горноправдинск</p>
                        </div>
                    </div>

                    <div class="map-badge pos-irkutsk">
                        <div class="badge-dot"></div>
                        <div class="badge-content">
                            <h4>Иркутск</h4>
                            <p>Ангарск • Шелехов</p>
                            <p>Усолье-Сибирское</p>
                            <p>Черемхово</p>
                            <p>Нижнеудинск • Братск</p>
                            <p>Железногорск-Илимский</p>
                            <p>Усть-Илимск</p>
                            <p>Слюдянка • Байкальск</p>
                        </div>
                    </div>

                    <div class="map-badge pos-ulan-ude">
                        <div class="badge-dot"></div>
                        <div class="badge-content">
                            <h4>Улан-Удэ</h4>
                            <p>Северобайкальск</p>
                            <p>Гусиноозерск</p>
                        </div>
                    </div>

                    <div class="map-badge pos-yakutsk">
                        <div class="badge-dot"></div>
                        <div class="badge-content">
                            <h4>Якутск</h4>
                            <p>Депутатский</p>
                            <p>Мирный</p>
                        </div>
                    </div>

                    <div class="map-badge pos-chita">
                        <div class="badge-dot"></div>
                        <div class="badge-content">
                            <h4>Чита</h4>
                        </div>
                    </div>

                    <div class="map-badge pos-vladivostok">
                        <div class="badge-dot"></div>
                        <div class="badge-content">
                            <h4>Владивосток</h4>
                        </div>
                    </div>

                    <div class="map-badge pos-kamchatka">
                        <div class="badge-dot"></div>
                        <div class="badge-content">
                            <h4>Петропавловск-Камчатский</h4>
                        </div>
                    </div>
                        <div class="map-badge pos-logo-brand company-about-fullwidth">
                            <div class="map-about-box-white">
                                <?php 
                                $aboutContent = getContentBlock($conn, 'about_content');
                                $aboutFeatures = getContentBlock($conn, 'about_features');
                                $aboutTitle = getContentBlock($conn, 'about_title');
                                ?>
                                
                                <div class="about-white-logo">
                                    <a href="/index.php" class="about-logo-link">
                                        <img src="/<?php echo getSetting($conn, 'logo_path'); ?>" alt="<?php echo getSetting($conn, 'company_name'); ?>">
                                    </a>
                                </div>

                                <div class="about-white-left">
                                    
                                    <ul class="about-white-features">
                                        <?php
                                        if (!empty($aboutFeatures['content'])) {
                                            $featuresList = explode("\n", $aboutFeatures['content']);
                                            foreach ($featuresList as $feature) {
                                                $trimmed = trim($feature);
                                                if ($trimmed) { 
                                                    echo '<li>' . $trimmed . '</li>';
                                                }
                                            }
                                        }
                                        ?>
                                    </ul>
                                </div>
                                
                                <div class="about-white-right">
                                    <p class="about-white-desc">
                                        <?php echo $aboutContent['content']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

        <script>
            window.addEventListener('load', function() {
                window.scrollBy(0, 1);
            });
        </script>
        <script src="/assets/js/audience.js"></script>
        <script src="/assets/js/newsArticlesMain.js"></script>
</main>

<?php require_once __DIR__. '/includes/footer.php'; ?></body>
</html>