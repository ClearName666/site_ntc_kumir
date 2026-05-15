<?php
// 1. Подключаем базу
require_once __DIR__ . '/config/database.php';

// Блокируем вывод ошибок, чтобы не ломать XML структуру
ini_set('display_errors', 0);
error_reporting(0);

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

$baseUrl = "https://pavelsite-n-t-c-kumir.ru";

function renderUrl($loc, $priority = '0.5', $changefreq = 'monthly', $lastmod = null) {
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

// --- СТАТИКА ---
renderUrl($baseUrl . '/', '1.0', 'daily');
renderUrl($baseUrl . '/news.php', '0.8', 'daily');
renderUrl($baseUrl . '/products.php', '0.8', 'daily');
renderUrl($baseUrl . '/contacts.php', '0.7', 'monthly');
renderUrl($baseUrl . '/faq.php', '0.7', 'weekly');
renderUrl($baseUrl . '/privacy.php', '0.3', 'yearly');

// --- ДИНАМИКА (с проверкой существования колонок) ---

// 1. Новости (пробуем забрать всё, что есть)
$res = $conn->query("SELECT slug, created_at FROM news");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        renderUrl($baseUrl . '/news.php?news=' . urlencode($row['slug']), '0.7', 'monthly', $row['created_at']);
    }
}

// 2. Статьи
$res = $conn->query("SELECT slug, created_at FROM articles");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        renderUrl($baseUrl . '/pages/articles.php?article=' . urlencode($row['slug']), '0.7', 'monthly', $row['created_at']);
    }
}

// 3. Категории
$res = $conn->query("SELECT slug FROM product_categories");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        renderUrl($baseUrl . '/products.php?category=' . urlencode($row['slug']), '0.8', 'weekly');
    }
}

// 4. Товары
$res = $conn->query("SELECT slug FROM products");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        renderUrl($baseUrl . '/products.php?product=' . urlencode($row['slug']), '0.9', 'weekly');
    }
}

echo '</urlset>';