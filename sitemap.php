<?php
/**
 * УНИВЕРСАЛЬНЫЙ SITEMAP ГЕНЕРАТОР (Версия 1.0 - на твоих функциях)
 */

// 1. ПОДКЛЮЧЕНИЕ БАЗЫ И КЭША
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/Cache.php'; 

// Инициализируем переменные, которые твои функции ждут как global
$conn = getDBConnection(); // Создаем соединение через твою функцию
$cache = new Cache();      // Создаем объект кэша

// 2. ПОДКЛЮЧЕНИЕ ТВОИХ ФУНКЦИЙ
require_once __DIR__ . '/includes/news-functions.php';
require_once __DIR__ . '/includes/product-functions.php';
require_once __DIR__ . '/includes/article-functions.php';
require_once __DIR__ . '/includes/faq-functions.php';

// Блокируем ошибки, чтобы не портить XML
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

$baseUrl = "https://pavelsite-n-t-c-kumir.ru";

/**
 * Вспомогательная функция вывода URL
 */
function renderSitemapUrl($loc, $priority = '0.5', $changefreq = 'monthly', $lastmod = null) {
    echo "  <url>" . PHP_EOL;
    echo "    <loc>" . htmlspecialchars($loc) . "</loc>" . PHP_EOL;
    if ($lastmod && $lastmod !== '0000-00-00 00:00:00') {
        $date = date('Y-m-d', strtotime($lastmod));
        echo "    <lastmod>{$date}</lastmod>" . PHP_EOL;
    }
    echo "    <changefreq>{$changefreq}</changefreq>" . PHP_EOL;
    echo "    <priority>{$priority}</priority>" . PHP_EOL;
    echo "  </url>" . PHP_EOL;
}

// --- СТАТИЧЕСКИЕ СТРАНИЦЫ ---
renderSitemapUrl($baseUrl . '/', '1.0', 'daily');
renderSitemapUrl($baseUrl . '/news.php', '0.8', 'daily');
renderSitemapUrl($baseUrl . '/products.php', '0.8', 'daily');
renderSitemapUrl($baseUrl . '/contacts.php', '0.7', 'monthly');
renderSitemapUrl($baseUrl . '/faq.php', '0.7', 'weekly');

// --- ДИНАМИКА (через твои функции) ---

// Новости
$allNews = getNews($conn); 
if ($allNews) {
    foreach ($allNews as $item) {
        renderSitemapUrl($baseUrl . '/news.php?news=' . urlencode($item['slug']), '0.7', 'monthly', $item['published_at']);
    }
}

// Статьи
$allArticles = getArticles($conn);
if ($allArticles) {
    foreach ($allArticles as $article) {
        renderSitemapUrl($baseUrl . '/pages/articles.php?article=' . urlencode($article['slug']), '0.7', 'monthly', $article['published_at']);
    }
}

// Категории товаров
$categories = getProductCategories($conn);
if ($categories) {
    foreach ($categories as $cat) {
        renderSitemapUrl($baseUrl . '/products.php?category=' . urlencode($cat['slug']), '0.8', 'weekly');
    }
}

// Сами товары (через прямой запрос, так как функции getProducts часто требуют category_id)
$resProducts = $conn->query("SELECT slug, created_at FROM products WHERE is_active = 1");
if ($resProducts) {
    while ($product = $resProducts->fetch_assoc()) {
        renderSitemapUrl($baseUrl . '/products.php?product=' . urlencode($product['slug']), '0.9', 'weekly', $product['created_at']);
    }
}

// FAQ Категории
$faqCats = getFAQCategories($conn);
if ($faqCats) {
    foreach ($faqCats as $fCat) {
        renderSitemapUrl($baseUrl . '/faq.php?category=' . urlencode($fCat), '0.5', 'monthly');
    }
}

echo '</urlset>';