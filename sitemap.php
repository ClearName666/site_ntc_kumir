<?php
/**
 * УНИВЕРСАЛЬНЫЙ SITEMAP ГЕНЕРАТОР
 * Проект: KumirRessurce (Дипломная работа)
 */

// 1. ПОДКЛЮЧЕНИЕ ВСЕХ НЕОБХОДИМЫХ ФУНКЦИЙ
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/news-functions.php';
require_once __DIR__ . '/includes/product-functions.php';
require_once __DIR__ . '/includes/article-functions.php';
require_once __DIR__ . '/includes/faq-functions.php';
require_once __DIR__ . '/includes/contact-functions.php';

// 2. УСТАНОВКА ЗАГОЛОВКОВ XML
header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

// Базовый URL (измени на актуальный, если нужно)
$baseUrl = "https://pavelsite-n-t-c-kumir.ru";

/**
 * Вспомогательная функция для вывода блока <url>
 */
function renderUrl($loc, $priority = '0.5', $changefreq = 'monthly', $lastmod = null) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($loc) . "</loc>\n";
    if ($lastmod) {
        $formattedDate = date('Y-m-d', strtotime($lastmod));
        echo "    <lastmod>{$formattedDate}</lastmod>\n";
    }
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

// --- 1. ГЛАВНЫЕ СТАТИЧЕСКИЕ СТРАНИЦЫ ---
renderUrl($baseUrl . '/', '1.0', 'daily');
renderUrl($baseUrl . '/news.php', '0.8', 'daily');
renderUrl($baseUrl . '/products.php', '0.8', 'daily');
renderUrl($baseUrl . '/contacts.php', '0.7', 'monthly');
renderUrl($baseUrl . '/faq.php', '0.7', 'weekly');
renderUrl($baseUrl . '/privacy.php', '0.3', 'yearly');

// --- 2. НОВОСТИ (используем getNews) ---
$allNews = getNews($conn); // Берем все новости через твою функцию
if ($allNews) {
    foreach ($allNews as $item) {
        $url = $baseUrl . '/news.php?news=' . urlencode($item['slug']);
        renderUrl($url, '0.7', 'monthly', $item['published_at']);
    }
}

// --- 3. СТАТЬИ (используем getArticles) ---
$allArticles = getArticles($conn);
if ($allArticles) {
    foreach ($allArticles as $article) {
        $url = $baseUrl . '/pages/articles.php?article=' . urlencode($article['slug']);
        renderUrl($url, '0.7', 'monthly', $article['published_at']);
    }
}

// --- 4. КАТЕГОРИИ ТОВАРОВ (используем getProductCategories) ---
$categories = getProductCategories($conn);
if ($categories) {
    foreach ($categories as $cat) {
        $url = $baseUrl . '/products.php?category=' . urlencode($cat['slug']);
        renderUrl($url, '0.8', 'weekly');
    }
}

// --- 5. КОНКРЕТНЫЕ ТОВАРЫ ---
$resProducts = $conn->query("SELECT slug, updated_at FROM products WHERE is_active = 1");
if ($resProducts) {
    while ($product = $resProducts->fetch_assoc()) {
        $url = $baseUrl . '/products.php?product=' . urlencode($product['slug']);
        // Если есть колонка updated_at, используем её, иначе null
        $lastmod = isset($product['updated_at']) ? $product['updated_at'] : null;
        renderUrl($url, '0.9', 'weekly', $lastmod);
    }
}

// --- 6. КАТЕГОРИИ FAQ (используем getFAQCategories) ---
$faqCats = getFAQCategories($conn);
if ($faqCats) {
    foreach ($faqCats as $fCat) {
        $url = $baseUrl . '/faq.php?category=' . urlencode($fCat);
        renderUrl($url, '0.5', 'monthly');
    }
}

echo '</urlset>';