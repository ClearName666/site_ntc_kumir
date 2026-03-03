<?php


require_once __DIR__. '/includes/functions.php';


// подключаемся к базе 
$conn = getDBConnection();

$mainTitle = getContentBlock($conn, 'main_title');
$mainBg = getImage($conn, 'main_background');
$heroImage = getImage($conn, 'hero_foreground');
$mapLocation = getMapLocation($conn);

require_once __DIR__. '/includes/header.php';

?>

<main>
    <!-- Hero Section - обновляем верхний отступ -->
    <section class="hero">
        <div class="hero-background">
            <img src="<?php echo $mainBg['image_path']; ?>" alt="<?php echo $mainBg['alt_text']; ?>">
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
                    
                    <div class="hero-visual">
                        <div class="hero-image-container">
                            <img src="<?php echo $heroImage['image_path']; ?>" alt="<?php echo $heroImage['alt_text']; ?>" class="hero-image">
                            <div class="map-overlay">
                                <div class="map-content">
                                    <div class="map-icon">
                                        📍
                                    </div>
                                    <div class="map-text map-text-location">
                                        <h3>«НТЦ «КУМИР» на карте</h3>
                                        <p>№<?php echo $mapLocation['latitude']; ?>, E<?php echo $mapLocation['longitude']; ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    <?php $videoThumb = getImage($conn, 'video_thumbnail'); ?>
                    <div class="video-container">
                        <img src="<?php echo $videoThumb['image_path']; ?>" alt="<?php echo $videoThumb['alt_text']; ?>" class="video-thumbnail">
                        <div class="play-button">
                            <span>▶</span>
                        </div>
                        <div class="video-title">
                            <?php echo getContentBlock($conn, 'video_button_text')['content']; ?>
                        </div>
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

<?php require_once __DIR__. '/includes/footer.php'; ?>