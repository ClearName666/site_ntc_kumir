<?php


require_once __DIR__. '/includes/functions.php';
require_once __DIR__. '/config/config.php';


// подключаемся к базе 
$conn = getDBConnection();
$mainTitle = getContentBlock($conn, 'main_title');
$mainBgStart = getImage($conn, 'main_background');
$heroImage = getImage($conn, 'hero_foreground');
$mapLocation = getMapLocation($conn);
$videoId = getSetting($conn, 'video_id');
// $videoThumb = getImage($conn, 'video_thumbnail'); 
$mainBg = getImage($conn, 'image_background_all');

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta name="description" content="НТЦ КУМИР — разработка и производство систем автоматизированного учета ресурсов (АСКУЭ), промышленных модемов серии M32 и программного обеспечения для ЖКХ и промышленности в Иркутске.">
    <meta name="keywords" content="кумир, ntc kumir, аскуэ, мониторинг ресурсов, модем m32, учет тепла, автоматизация жкх, телеметрия">
    <meta property="og:title" content="НТЦ КУМИР — Системы автоматизации и мониторинга">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?php echo $heroImage['image_path']; ?>">


    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getSetting($conn, 'site_title'); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/responsive.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/header.css?version=<?php echo $version_code; ?>">
    <link rel="icon" href="<?php echo getSetting($conn, 'favicon_path'); ?>" type="image/x-icon">
</head>
<body style="background: url('<?php echo $mainBg['image_path']; ?>') center/cover no-repeat fixed;">

<?php require_once __DIR__. '/includes/header.php';?>
<main>
    <!-- Hero Section - обновляем верхний отступ -->
    <section class="hero">
        <div class="hero-background">
            <img src="<?php echo $mainBgStart['image_path']; ?>" alt="<?php echo $mainBgStart['alt_text']; ?>">
        </div>
        
        <div class="container">
            <div class="hero-content">
                <div class="hero-main">
                    <div class="hero-text">
                        <h1 class="main-title"><?php echo $mainTitle['content']; ?></h1>
                        
                        <div class="features-list">
                            <?php renderFeatures($conn); ?>
                        </div>
                        
                        <!-- <div class="contacts-section">
                            <div class="analytics-badge">
                                <span>📊</span>
                                <span>Дистанционный контроль и управление</span>
                            </div>
                        </div> -->

                        <!-- Кнопка для перехода к системе -->
                        <div class="contacts-section">
                            <a href="https://v4.ntckumir.ru/" class="analytics-badge" target="_blank" rel="noopener noreferrer">
                                <span>📊</span>
                                <span>Дистанционный контроль и управление</span>
                            </a>
                        </div>
                    </div>
                    
                    <a href="contacts.php#map-location" class="hero-link-wrapper" style="text-decoration: none; color: inherit; display: block;">
                        <div class="hero-visual">
                            <div class="hero-image-container">
                                <img src="<?php echo $heroImage['image_path']; ?>" alt="<?php echo $heroImage['alt_text']; ?>" class="hero-image">
                                <div class="map-overlay">
                                    <div class="map-content">
                                        <div class="map-icon">📍</div>
                                        <div class="map-text map-text-location">
                                            <h3>«НТЦ «КУМИР» на карте</h3>
                                            <p>N<?php echo $mapLocation['latitude']; ?>, E<?php echo $mapLocation['longitude']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Products/Cards Section -->
    <section class="cards-section">
        <div class="container">
            <h2 class="section-title">Наша продукция</h2>
            <div class="cards-container">
                <?php renderCards($conn); ?>
            </div>
        </div>
    </section>

    <!-- Advantages Section -->
    <section class="advantages-section">
        <div class="container">
            <h2 class="section-title"><?php echo getContentBlock($conn, 'advantages_title')['content']; ?></h2>
            <?php renderAdvantages($conn); ?>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
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

    
</main>

<?php require_once __DIR__. '/includes/footer.php'; ?></body>
</html>