<?php
// Подключаем database.php
require_once __DIR__ . '/../config/database.php';

// Функция для получения FAQ по категории
function getFAQ($conn, $category = null, $limit = null) {
    global $cache;
    $cacheKey = "faq_public_" . ($category ? md5($category) : 'all') . "_lim" . intval($limit);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $sql = "SELECT * FROM faq WHERE is_active = 1";
    if ($category) {
        $sql .= " AND category = '" . $conn->real_escape_string($category) . "'";
    }
    $sql .= " ORDER BY sort_order";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $conn->query($sql);
    $faq = $result->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $faq);
    return $faq;
}

// Функция для получения категорий FAQ
function getFAQCategories($conn) {
    global $cache;
    $cacheKey = "faq_categories_list";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $result = $conn->query("SELECT DISTINCT category FROM faq WHERE category IS NOT NULL AND is_active = 1 ORDER BY category");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    
    $cache->set($cacheKey, $categories);
    return $categories;
}

function addFAQQuestion($conn, $name, $email, $category, $question) {
    global $cache;
    
    $fullQuestion = "От: $name ($email)\nВопрос: $question";
    $isActive = 0; 
    $sortOrder = 0;

    $stmt = $conn->prepare("INSERT INTO faq (question, category, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssii", $fullQuestion, $category, $sortOrder, $isActive);
    
    $res = $stmt->execute();
    
    if ($res) {
        // ОЧИСТКА: сбрасываем списки в админке, чтобы админ увидел новый вопрос
        $cache->deleteByPrefix("admin_faq_");
        // На всякий случай сбросим и публичные списки, если они зависят от категорий
        $cache->deleteByPrefix("faq_public_");
    }
    
    return $res;
}

?>