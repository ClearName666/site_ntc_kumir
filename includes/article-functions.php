<?php
require_once __DIR__ . '/../config/database.php';

// Функция для получения всех статей
function getArticles($conn, $limit = null, $offset = 0) {
    // $conn = getDBConnection();
    
    $sql = "SELECT * FROM articles WHERE is_published = 1 ";
    $sql .= "AND (published_at IS NULL OR published_at <= NOW()) ";
    $sql .= "ORDER BY published_at DESC, created_at DESC";
    
    if ($limit !== null) {
        $sql .= " LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
    
    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    
    return $articles;
}


// Функция для получения статьи по слагу
function getArticleBySlug($conn, $slug) {
    // $conn = getDBConnection();
    
    // Увеличиваем счетчик просмотров
    $conn->query("UPDATE articles SET views = views + 1 WHERE slug = '$slug'");
    
    $stmt = $conn->prepare("SELECT * FROM articles WHERE slug = ? AND is_published = 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}


// Функция для получения количества статей
function getArticlesCount($conn) {
    // $conn = getDBConnection();
    
    $result = $conn->query("SELECT COUNT(*) as count FROM articles WHERE is_published = 1");
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

// Функция для получения популярных статей
function getPopularArticles($conn, $limit = 3) {
    // $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM articles WHERE is_published = 1 ORDER BY views DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    
    return $articles;
}

// Функция для отображения карточек статей
function renderArticleCards($articles, $columns = 3) {
    echo '<div class="articles-grid" style="grid-template-columns: repeat(' . $columns . ', 1fr);">';
    
    foreach ($articles as $article) {
        echo '<article class="article-card">';
        echo '<a href="/pages/articles.php?article=' . $article['slug'] . '" class="article-link">';
        
        // Изображение статьи
        if (!empty($article['image_path'])) {
            echo '<div class="article-image">';
            echo '<img src="' . $article['image_path'] . '" alt="' . htmlspecialchars($article['title']) . '">';
            echo '</div>';
        }
        
        // Контент карточки
        echo '<div class="article-content">';
        echo '<h3 class="article-title">' . htmlspecialchars($article['title']) . '</h3>';
        
        if (!empty($article['excerpt'])) {
            echo '<p class="article-excerpt">' . htmlspecialchars($article['excerpt']) . '</p>';
        }
        
        echo '<div class="article-meta">';
        if (!empty($article['author'])) {
            echo '<span class="article-author">' . htmlspecialchars($article['author']) . '</span>';
        }
        if (!empty($article['published_at'])) {
            $date = date('d.m.Y', strtotime($article['published_at']));
            echo '<span class="article-date">' . $date . '</span>';
        }
        echo '</div>';
        
        echo '</div>';
        echo '</a>';
        echo '</article>';
    }
    
    echo '</div>';
}

// Функция для увеличения счетчика просмотров
function incrementArticleViews($conn, $articleId) {
    // $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
    $stmt->bind_param("i", $articleId);
    $stmt->execute();
}

// Функция для проверки существования статьи
function articleExists($conn, $slug) {
    // $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM articles WHERE slug = ? AND is_published = 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] > 0;
}
?>