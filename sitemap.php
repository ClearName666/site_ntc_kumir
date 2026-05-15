<?php
// 1. Подключаем только базу данных
require_once __DIR__ . '/config/database.php';

// Отключаем вывод ошибок в XML, но включаем логирование в файл
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

// --- 2. НОВОСТИ (прямой запрос) ---
$resNews = $conn->query("SELECT slug, published_at FROM news WHERE is_published = 1");
if ($resNews) {
    while ($item = $resNews->fetch_assoc()) {
        renderUrl($baseUrl . '/news.php?news=' . urlencode($item['slug']), '0.7', 'monthly', $item['published_at']);
    }
}

// --- 3. СТАТЬИ (прямой запрос) ---
$resArticles = $conn->query("SELECT slug, published_at FROM articles WHERE is_published = 1");
if ($resArticles) {
    while ($article = $resArticles->fetch_assoc()) {
        renderUrl($baseUrl . '/pages/articles.php?article=' . urlencode($article['slug']), '0.7', 'monthly', $article['published_at']);
    }
}

// --- 4. КАТЕГОРИИ И ТОВАРЫ (прямой запрос) ---
$resCats = $conn->query("SELECT slug FROM product_categories WHERE is_active = 1");
if ($resCats) {
    while ($cat = $resCats->fetch_assoc()) {
        renderUrl($baseUrl . '/products.php?category=' . urlencode($cat['slug']), '0.8', 'weekly');
    }
}

$resProducts = $conn->query("SELECT slug FROM products WHERE is_active = 1");
if ($resProducts) {
    while ($product = $resProducts->fetch_assoc()) {
        renderUrl($baseUrl . '/products.php?product=' . urlencode($product['slug']), '0.9', 'weekly');
    }
}

echo '</urlset>';