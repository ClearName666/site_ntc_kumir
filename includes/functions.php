<?php

require_once __DIR__ . '/../Cache.php';
$cache = new Cache();

require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/article-functions.php';

require_once __DIR__ . '/faq-functions.php';

require_once __DIR__ . '/news-functions.php';

require_once __DIR__ . '/product-functions.php';

require_once __DIR__ . '/contact-functions.php';

require_once __DIR__ . '/auth-functions.php';


function getImage($conn, $key) {
    global $cache;
    $cacheKey = "image_key_" . $key;

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT image_path, alt_text FROM images WHERE image_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    $result = $data ?: ['image_path' => '', 'alt_text' => ''];
    $cache->set($cacheKey, $result);
    return $result;
}

function getMapLocation($conn) {
    $result = $conn->query("SELECT latitude, longitude, zoom, marker_title FROM map_location LIMIT 1");
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return ['latitude' => 0, 'longitude' => 0, 'zoom' => 12, 'marker_title' => ''];
}

function renderAdvantages($conn) {
    $advantages = getAdvantages($conn);
    echo '<div class="advantages-grid">';
    foreach ($advantages as $advantage) {
        echo '<div class="advantage-item">';
        echo '<div class="advantage-header">';
        echo '<div class="advantage-icon">';
        
        $fileName = !empty($advantage['icon_path']) ? basename($advantage['icon_path']) : '';
        $serverPath = __DIR__ . '/../assets/images/uploads/' . $fileName;
        $browserPath = '/assets/images/uploads/' . $fileName;

        if (!empty($fileName) && file_exists($serverPath)) {
            echo '<img src="' . $browserPath . '" alt="' . htmlspecialchars($advantage['title']) . '">';
        } else {
            echo '<span>✓</span>';
        }
        
        echo '</div>';

        echo '<h3 class="advantage-title">' . htmlspecialchars($advantage['title']) . '</h3>';
        echo '</div>';
        echo '<p class="advantage-description">' . htmlspecialchars($advantage['description']) . '</p>';
        echo '</div>';
    }
    echo '</div>';
}

function renderFeatures($conn) {
    $features = getFeatures($conn);
    foreach ($features as $feature) {
        echo '<div class="feature-item"><span class="feature-dot"></span>' . $feature['title'] . '</div>';
    }
}

function renderCards($conn) {
    $cards = getCards($conn);
    foreach ($cards as $card) {
        echo '<div class="card" style="border-bottom-color: ' . $card['color'] . '">';
        echo '<div class="card-image">';
        echo '<img src="' . $card['image_path'] . '" alt="' . $card['title'] . '">';
        echo '</div>';
        echo '<div class="card-content">';
        echo '<h3>' . $card['title'] . '</h3>';
        echo '<p>' . $card['description'] . '</p>';
        echo '</div>';
        echo '</div>';
    }
}

function renderStatistics($conn) {
    $stats = getStatistics($conn);
    foreach ($stats as $stat) {
        echo '<div class="stat-item">';
        echo '<div class="stat-value">' . $stat['value'] . '</div>';
        echo '<div class="stat-title">' . $stat['title'] . '</div>';
        echo '<div class="stat-desc">' . $stat['description'] . '</div>';
        echo '</div>';
    }
}

function getNavigationMenu($conn) {
    global $cache;
    $cacheKey = "system_menu";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $result = $conn->query("SELECT * FROM menu_items WHERE is_active = 1 ORDER BY sort_order");
    $items = $result->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $items);
    return $items;
}



function safeSubstr($string, $start, $length = null) {
    if (empty($string)) {
        return '';
    }
    
    if (function_exists('mb_substr')) {
        if ($length === null) {
            return mb_substr($string, $start, null, 'UTF-8');
        }
        return mb_substr($string, $start, $length, 'UTF-8');
    } else {
        if ($length === null) {
            return substr($string, $start);
        }
        
        $result = substr($string, $start, $length);
        
        if (strlen($result) > 0) {
            while (strlen($result) > 0 && ord($result[strlen($result) - 1]) > 127) {
                $result = substr($result, 0, -1);
            }
        }
        
        return $result;
    }
}

function truncateDescription($text, $length = 160) {
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    
    if (function_exists('mb_strlen')) {
        if (mb_strlen($text, 'UTF-8') > $length) {
            $text = mb_substr($text, 0, $length, 'UTF-8') . '...';
        }
    } else {
        if (strlen($text) > $length) {
            $text = safeSubstr($text, 0, $length) . '...';
        }
    }
    
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>