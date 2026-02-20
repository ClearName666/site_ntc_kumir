<?php
// Подключаем database.php
require_once __DIR__ . '/../config/database.php';

// Функция для получения FAQ по категории
function getFAQ($conn, $category = null, $limit = null) {
    // $conn = getDBConnection();
    
    $sql = "SELECT * FROM faq WHERE is_active = 1";
    
    if ($category) {
        $sql .= " AND category = '" . $conn->real_escape_string($category) . "'";
    }
    
    $sql .= " ORDER BY sort_order";
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $conn->query($sql);
    $faq = [];
    
    while ($row = $result->fetch_assoc()) {
        $faq[] = $row;
    }
    
    return $faq;
}

// Функция для получения категорий FAQ
function getFAQCategories($conn) {
    // $conn = getDBConnection();
    $result = $conn->query("SELECT DISTINCT category FROM faq WHERE category IS NOT NULL AND is_active = 1 ORDER BY category");
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
    
    return $categories;
}

function addFAQQuestion($conn, $name, $email, $category, $question) {
    // $conn = getDBConnection();
    
    // Формируем текст вопроса, включая имя и почту, 
    // чтобы админ знал, кому отвечать (так как в faq нет полей name/email)
    $fullQuestion = "От: $name ($email)\nВопрос: $question";
    $isActive = 0; // Не отображаем на сайте, пока нет ответа
    $sortOrder = 0;

    $stmt = $conn->prepare("INSERT INTO faq (question, category, sort_order, is_active, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssii", $fullQuestion, $category, $sortOrder, $isActive);
    
    return $stmt->execute();
}

?>