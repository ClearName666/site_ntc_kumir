<?php
/**
 * ИСПРАВЛЕННЫЙ SITEMAP ГЕНЕРАТОР
 */

// 1. ПОДКЛЮЧЕНИЕ БАЗЫ И КЭША (Важно!)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/Cache.php'; // Подключи свой класс Cache

// Инициализируем объект кэша, так как твои функции используют global $cache
$cache = new Cache(); 

// Подключаем функции
require_once __DIR__ . '/includes/news-functions.php';
require_once __DIR__ . '/includes/product-functions.php';
require_once __DIR__ . '/includes/article-functions.php';
require_once __DIR__ . '/includes/faq-functions.php';
require_once __DIR__ . '/includes/contact-functions.php';

// Отключаем вывод ошибок PHP в сам XML, чтобы не ломать структуру
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$baseUrl = "https://pavelsite-n-t-c-kumir.ru";

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

// --- 1. СТАТИЧЕСКИЕ СТРАНИЦЫ ---
renderUrl($baseUrl . '/', '1.0', 'daily');
renderUrl($baseUrl . '/news.php', '0.8', 'daily');
renderUrl($baseUrl . '/products.php', '0.8', 'daily');
renderUrl($baseUrl . '/contacts.php', '0.7', 'monthly');
renderUrl($baseUrl . '/faq.php', '0.7', 'weekly');
renderUrl($baseUrl . '/privacy.php', '0.3', 'yearly');

// --- БЛОК С ОБРАБОТКОЙ ОШИБОК (try-catch) ---
try {
    // 2. НОВОСТИ
    $allNews = getNews($conn); 
    if ($allNews) {
        foreach ($allNews as $item) {
            renderUrl($baseUrl . '/news.php?news=' . urlencode($item['slug']), '0.7', 'monthly', $item['published_at']);
        }
    }

    // 3. СТАТЬИ
    $allArticles = getArticles($conn);
    if ($allArticles) {
        foreach ($allArticles as $article) {
            renderUrl($baseUrl . '/pages/articles.php?article=' . urlencode($article['slug']), '0.7', 'monthly', $article['published_at']);
        }
    }

    // 4. КАТЕГОРИИ И ТОВАРЫ
    $categories = getProductCategories($conn);
    if ($categories) {
        foreach ($categories as $cat) {
            renderUrl($baseUrl . '/products.php?category=' . urlencode($cat['slug']), '0.8', 'weekly');
        }
    }

    $resProducts = $conn->query("SELECT slug FROM products WHERE is_active = 1");
    while ($product = $resProducts->fetch_assoc()) {
        renderUrl($baseUrl . '/products.php?product=' . urlencode($product['slug']), '0.9', 'weekly');
    }

    // 5. FAQ
    $faqCats = getFAQCategories($conn);
    if ($faqCats) {
        foreach ($faqCats as $fCat) {
            renderUrl($baseUrl . '/faq.php?category=' . urlencode($fCat), '0.5', 'monthly');
        }
    }

} catch (Exception $e) {
    // Если произошла ошибка, мы хотя бы закроем тег, чтобы XML был валидным
}

echo '</urlset>';