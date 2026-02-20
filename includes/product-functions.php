<?php
// Подключаем database.php
require_once __DIR__ . '/../config/database.php';

// Функция для получения всех активных категорий
// function getProductCategories($conn) {
//     // $conn = getDBConnection();
//     $result = $conn->query("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY sort_order");
//     $categories = [];
    
//     while ($row = $result->fetch_assoc()) {
//         $categories[] = $row;
//     }
    
//     return $categories;
// }
function getProductCategories($conn) {
    global $cache;
    $cacheKey = "categories_public_all";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $result = $conn->query("SELECT * FROM product_categories WHERE is_active = 1 ORDER BY sort_order");
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

// Функция для получения категории по slug
function getCategoryBySlug($conn, $slug) {
    // $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM product_categories WHERE slug = ? AND is_active = 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return null;
}

// Функция для получения товаров категории
// function getProductsByCategory($conn, $categoryId, $limit = null) {
//     // $conn = getDBConnection();
    
//     $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
//             FROM products p 
//             JOIN product_categories c ON p.category_id = c.id 
//             WHERE p.category_id = ? AND p.is_active = 1 AND c.is_active = 1 
//             ORDER BY p.sort_order";
    
//     if ($limit) {
//         $sql .= " LIMIT " . intval($limit);
//     }
    
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("i", $categoryId);
//     $stmt->execute();
//     $result = $stmt->get_result();
    
//     $products = [];
//     while ($row = $result->fetch_assoc()) {
//         $products[] = $row;
//     }
    
//     return $products;
// }
function getProductsByCategory($conn, $categoryId, $limit = null) {
    global $cache;
    $cacheKey = "products_cat_{$categoryId}_lim_{$limit}";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
            FROM products p 
            JOIN product_categories c ON p.category_id = c.id 
            WHERE p.category_id = ? AND p.is_active = 1 AND c.is_active = 1 
            ORDER BY p.sort_order";
    
    if ($limit) $sql .= " LIMIT " . intval($limit);
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

// Функция для получения товара по slug
function getProductBySlug($conn, $slug) {
    // $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug 
                           FROM products p 
                           JOIN product_categories c ON p.category_id = c.id 
                           WHERE p.slug = ? AND p.is_active = 1 AND c.is_active = 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Парсим спецификации из JSON
        if (!empty($row['specifications'])) {
            $row['specifications_array'] = json_decode($row['specifications'], true);
        }
        return $row;
    }
    
    return null;
}

// Функция для отображения категорий
function renderCategoriesGrid($categories) {
    echo '<div class="categories-grid">';
    
    foreach ($categories as $category) {
        echo '<div class="category-card" data-category="' . $category['slug'] . '">';
        echo '<a href="products.php?category=' . $category['slug'] . '" class="category-link">';
        
        // Изображение категории
        if (!empty($category['image_path'])) {
            echo '<div class="category-image">';
            echo '<img src="' . $category['image_path'] . '" alt="' . htmlspecialchars($category['name']) . '" loading="lazy">';
            echo '</div>';
        }
        
        // Контент категории
        echo '<div class="category-content">';
        echo '<h3 class="category-name">' . htmlspecialchars($category['name']) . '</h3>';
        
        if (!empty($category['description'])) {
            echo '<p class="category-description">' . htmlspecialchars($category['description']) . '</p>';
        }
        
        echo '<span class="category-action">Смотреть товары →</span>';
        echo '</div>';
        echo '</a>';
        echo '</div>';
    }
    
    echo '</div>';
}

// Функция для отображения товаров
function renderProductsGrid($products, $columns = 3) {
    echo '<div class="products-grid" style="grid-template-columns: repeat(' . $columns . ', 1fr);">';
    
    foreach ($products as $product) {
        echo '<div class="product-card" data-product="' . $product['slug'] . '">';
        echo '<a href="products.php?product=' . $product['slug'] . '" class="product-link">';
        
        // Изображение товара
        if (!empty($product['image_path'])) {
            echo '<div class="product-image">';
            echo '<img src="' . $product['image_path'] . '" alt="' . htmlspecialchars($product['name']) . '" loading="lazy">';
            echo '</div>';
        }
        
        // Контент товара
        echo '<div class="product-content">';
        echo '<h3 class="product-name">' . htmlspecialchars($product['name']) . '</h3>';
        
        if (!empty($product['description'])) {
            echo '<p class="product-description">' . htmlspecialchars($product['description']) . '</p>';
        }
        
        // Цена
        if (!empty($product['price'])) {
            echo '<div class="product-price">';
            echo number_format($product['price'], 0, '.', ' ') . ' ₽';
            echo '</div>';
        }
        
        echo '<span class="product-action">Подробнее →</span>';
        echo '</div>';
        echo '</a>';
        echo '</div>';
    }
    
    echo '</div>';
}

// Функция для получения похожих товаров
function getRelatedProducts($conn, $productId, $categoryId, $limit = 3) {
    // $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM products 
                           WHERE category_id = ? AND id != ? AND is_active = 1 
                           ORDER BY sort_order LIMIT ?");
    $stmt->bind_param("iii", $categoryId, $productId, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

function addProductRequest($conn, $data) {
    // $conn = getDBConnection();
    
    $product_name = cleanInput($data['product_name']);
    $name = cleanInput($data['name']);
    $phone = cleanInput($data['phone']);
    $email = cleanInput($data['email'] ?? '');
    $quantity = intval($data['quantity'] ?? 1);
    $message = cleanInput($data['message'] ?? '');

    $stmt = $conn->prepare("INSERT INTO product_requests (product_name, name, phone, email, quantity, message) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssis", $product_name, $name, $phone, $email, $quantity, $message);
    
    return $stmt->execute();
}
?>