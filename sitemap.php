<?php

require_once __DIR__ . '/config/database.php';

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

$baseUrl = "https://pavelsite-n-t-c-kumir.ru";

function renderUrl($loc, $priority = '0.5', $changefreq = 'monthly', $lastmod = null)
{
    echo "<url>\n";

    echo "<loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";

    if (!empty($lastmod) && $lastmod !== '0000-00-00 00:00:00') {
        $timestamp = strtotime($lastmod);

        if ($timestamp !== false) {
            echo "<lastmod>" . date('Y-m-d', $timestamp) . "</lastmod>\n";
        }
    }

    echo "<changefreq>{$changefreq}</changefreq>\n";
    echo "<priority>{$priority}</priority>\n";

    echo "</url>\n";
}

try {

    // Статические страницы
    renderUrl($baseUrl . '/', '1.0', 'daily');
    renderUrl($baseUrl . '/news.php', '0.8', 'daily');
    renderUrl($baseUrl . '/products.php', '0.8', 'daily');
    renderUrl($baseUrl . '/contacts.php', '0.7', 'monthly');
    renderUrl($baseUrl . '/faq.php', '0.7', 'weekly');

    // Новости
    $res = $conn->query("
        SELECT slug, published_at
        FROM news
        WHERE is_published = 1
    ");

    if ($res) {
        while ($row = $res->fetch_assoc()) {

            renderUrl(
                $baseUrl . '/news.php?news=' . urlencode($row['slug']),
                '0.7',
                'monthly',
                $row['published_at']
            );
        }
    }

    // Статьи
    $res = $conn->query("
        SELECT slug
        FROM articles
        WHERE is_published = 1
    ");

    if ($res) {
        while ($row = $res->fetch_assoc()) {

            renderUrl(
                $baseUrl . '/articles.php?article=' . urlencode($row['slug']),
                '0.7',
                'monthly'
            );
        }
    }

    // Категории
    $res = $conn->query("
        SELECT slug, created_at
        FROM product_categories
        WHERE is_active = 1
    ");

    if ($res) {
        while ($row = $res->fetch_assoc()) {

            renderUrl(
                $baseUrl . '/products.php?category=' . urlencode($row['slug']),
                '0.8',
                'weekly',
                $row['created_at']
            );
        }
    }

    // Товары
    $res = $conn->query("
        SELECT slug, created_at
        FROM products
        WHERE is_active = 1
    ");

    if ($res) {
        while ($row = $res->fetch_assoc()) {

            renderUrl(
                $baseUrl . '/products.php?product=' . urlencode($row['slug']),
                '0.9',
                'weekly',
                $row['created_at']
            );
        }
    }

} catch (Throwable $e) {

    echo "<!-- ERROR: " . htmlspecialchars($e->getMessage()) . " -->";
}

echo '</urlset>';