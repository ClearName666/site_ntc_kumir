<?php
// 1. Подключаем базу
require_once __DIR__ . '/config/database.php';

// Блокируем вывод ошибок в XML
ini_set('display_errors', 0);
error_reporting(0);

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

$baseUrl = "https://pavelsite-n-t-c-kumir.ru";

function renderUrl($loc, $priority = '0.5', $changefreq = 'monthly', $lastmod = null) {
    echo "  <url>" . PHP_EOL;
    echo "    <loc>" . htmlspecialchars($loc) . "</loc>" . PHP_EOL;
    // Проверка на корректность даты
    if ($lastmod && $lastmod !== '0000-00-00 00:00:00') {
        $date = date('Y-m-d', strtotime($lastmod));
        echo "    <lastmod>{$date}</lastmod>" . PHP_EOL;
    }
    echo "    <changefreq>{$changefreq}</changefreq>" . PHP_EOL;
    echo "    <priority>{$priority}</priority>" . PHP_EOL;
    echo "  </url>" . PHP_EOL;
}

// --- 1. СТАТИЧЕСКИЕ СТРАНИЦЫ ---
renderUrl($baseUrl . '/', '1.0', 'daily');
renderUrl($baseUrl . '/news.php', '0.8', 'daily');
renderUrl($baseUrl . '/products.php', '0.8', 'daily');
renderUrl($baseUrl . '/contacts.php', '0.7', 'monthly');
renderUrl($baseUrl . '/faq.php', '0.7', 'weekly');

// --- 2. НОВОСТИ (по дампу: slug, published_at, is_published) ---
$res = $conn->query("SELECT slug, published_at FROM news WHERE is_published = 1");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        renderUrl($baseUrl . '/news.php?news=' . urlencode($row['slug']), '0.7', 'monthly', $row['published_at']);
    }
}

// --- 3. СТАТЬИ (по дампу: slug, is_published) ---
$res = $conn->query("SELECT slug FROM articles WHERE is_published = 1");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        renderUrl($baseUrl . '/pages/articles.php?article=' . urlencode($row['slug']), '0.7', 'monthly');
    }
}

// --- 4. КАТЕГОРИИ ТОВАРОВ (по дампу: slug, created_at, is_active) ---
$res = $conn->query("SELECT slug, created_at FROM product_categories WHERE is_active = 1");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        renderUrl($baseUrl . '/products.php?category=' . urlencode($row['slug']), '0.8', 'weekly', $row['created_at']);
    }
}

// --- 5. ТОВАРЫ (по дампу: slug, created_at, is_active) ---
$res = $conn->query("SELECT slug, created_at FROM products WHERE is_active = 1");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        renderUrl($baseUrl . '/products.php?product=' . urlencode($row['slug']), '0.9', 'weekly', $row['created_at']);
    }
}

echo '</urlset>';