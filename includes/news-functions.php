<?php
// Подключаем database.php
require_once __DIR__ . '/../config/database.php';

// Функция для получения всех опубликованных новостей
// function getNews($conn, $limit = null, $offset = 0) {
//     // $conn = getDBConnection();
    
//     $sql = "SELECT * FROM news WHERE is_published = 1 ORDER BY published_at DESC";
    
//     if ($limit) {
//         $sql .= " LIMIT " . intval($offset) . ", " . intval($limit);
//     }
    
//     $result = $conn->query($sql);
//     $news = [];
    
//     while ($row = $result->fetch_assoc()) {
//         $news[] = $row;
//     }
    
//     return $news;
// }
function getNews($conn, $limit = null, $offset = 0) {
    global $cache;
    // Создаем уникальный ключ для каждой страницы пагинации
    $cacheKey = "news_list_l" . intval($limit) . "_o" . intval($offset);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $sql = "SELECT * FROM news WHERE is_published = 1 ORDER BY published_at DESC";
    if ($limit !== null) {
        $sql .= " LIMIT " . intval($offset) . ", " . intval($limit);
    }
    
    $result = $conn->query($sql);
    $news = [];
    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
    
    $cache->set($cacheKey, $news);
    return $news;
}

// Функция для получения одной новости по slug
// function getNewsBySlug($conn, $slug) {
//     // $conn = getDBConnection();
//     $stmt = $conn->prepare("SELECT * FROM news WHERE slug = ? AND is_published = 1");
//     $stmt->bind_param("s", $slug);
//     $stmt->execute();
//     $result = $stmt->get_result();
    
//     if ($row = $result->fetch_assoc()) {
//         // Увеличиваем счетчик просмотров
//         $conn->query("UPDATE news SET views = views + 1 WHERE id = " . $row['id']);
//         return $row;
//     }
    
//     return null;
// }
// Получение одной новости по slug
function getNewsBySlug($conn, $slug) {
    global $cache;
    $cacheKey = "news_single_" . md5($slug);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM news WHERE slug = ? AND is_published = 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    if ($data) $cache->set($cacheKey, $data);
    return $data;
}

// Функция для получения количества новостей
function getNewsCount($conn) {
    // $conn = getDBConnection();
    $result = $conn->query("SELECT COUNT(*) as count FROM news WHERE is_published = 1");
    $row = $result->fetch_assoc();
    return $row['count'];
}

// Функция для получения последних новостей
// function getLatestNews($conn, $limit = 3) {
//     // $conn = getDBConnection();
//     $stmt = $conn->prepare("SELECT * FROM news WHERE is_published = 1 ORDER BY published_at DESC LIMIT ?");
//     $stmt->bind_param("i", $limit);
//     $stmt->execute();
//     $result = $stmt->get_result();
    
//     $news = [];
//     while ($row = $result->fetch_assoc()) {
//         $news[] = $row;
//     }
    
//     return $news;
// }
// Получение последних новостей (например, для главной)
function getLatestNews($conn, $limit = 3) {
    global $cache;
    $cacheKey = "news_latest_" . $limit;

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM news WHERE is_published = 1 ORDER BY published_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

// Функция для увеличения счетчика просмотров
function incrementNewsViews($conn, $articleId) {
    // $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE news SET views = views + 1 WHERE id = ?");
    $stmt->bind_param("i", $articleId);
    $stmt->execute();
}

// Функция для отображения карточек новостей
function renderNewsCards($newsItems, $columns = 3, $stacked = true) {
    $className = $stacked ? 'news-card-stack' : 'news-card-grid';
    
    echo '<div class="news-grid" style="grid-template-columns: repeat(' . $columns . ', 1fr);">';
    
    foreach ($newsItems as $index => $item) {
        echo '<article class="' . $className . '" data-index="' . $index . '">';
        echo '<a href="?news=' . urlencode($item['slug']) . '" class="news-link" aria-label="Читать новость: ' . htmlspecialchars($item['title']) . '">';
        
        // Изображение новости
        if (!empty($item['image_path'])) {
            echo '<div class="news-image-container">';
            echo '<img src="' . $item['image_path'] . '" alt="' . htmlspecialchars($item['title']) . '" loading="lazy">';
            echo '</div>';
        }
        
        // Контент карточки
        echo '<div class="news-content">';
        
        // Дата новости (важный элемент для новостей)
        if (!empty($item['published_at'])) {
            $date = date('d.m.Y', strtotime($item['published_at']));
            echo '<div class="news-date">';
            echo '<span class="date-icon">📅</span>';
            echo '<time datetime="' . date('Y-m-d', strtotime($item['published_at'])) . '">' . $date . '</time>';
            echo '</div>';
        }
        
        echo '<h3 class="news-title">' . htmlspecialchars($item['title']) . '</h3>';
        
        if (!empty($item['excerpt'])) {
            echo '<p class="news-excerpt">' . htmlspecialchars($item['excerpt']) . '</p>';
        }
        
        echo '<div class="news-meta">';
        if (!empty($item['author'])) {
            echo '<span class="news-author">' . htmlspecialchars($item['author']) . '</span>';
        }
        echo '<span class="read-more">Подробнее →</span>';
        echo '</div>';
        
        echo '</div>';
        echo '</a>';
        echo '</article>';
    }
    
    echo '</div>';
}

// Функция для отображения пагинации
function renderNewsPagination($currentPage, $totalPages, $baseUrl = 'news.php') {
    if ($totalPages <= 1) return;
    
    echo '<div class="pagination">';
    
    // Кнопка "Назад"
    if ($currentPage > 1) {
        echo '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="page-link prev">← Назад</a>';
    }
    
    // Номера страниц
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        echo '<a href="' . $baseUrl . '?page=1" class="page-link">1</a>';
        if ($start > 2) echo '<span class="page-dots">...</span>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $activeClass = ($i == $currentPage) ? ' active' : '';
        echo '<a href="' . $baseUrl . '?page=' . $i . '" class="page-link' . $activeClass . '">' . $i . '</a>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) echo '<span class="page-dots">...</span>';
        echo '<a href="' . $baseUrl . '?page=' . $totalPages . '" class="page-link">' . $totalPages . '</a>';
    }
    
    // Кнопка "Вперед"
    if ($currentPage < $totalPages) {
        echo '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="page-link next">Вперед →</a>';
    }
    
    echo '</div>';
}


function getActualNewsViews($conn, $newsId) {
    $stmt = $conn->prepare("SELECT views FROM news WHERE id = ?");
    $stmt->bind_param("i", $newsId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res ? $res['views'] : 0;
}
?>