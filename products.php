<?php

// Подключаем функции
require_once __DIR__ . '/includes/functions.php';

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && isset($_POST['phone'])) {
    header('Content-Type: application/json');
    
    if (addProductRequest($_POST)) {
        echo json_encode(['status' => 'success', 'message' => 'Заявка успешно отправлена! Менеджер свяжется с вами.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка при сохранении заявки.']);
    }
    exit;
}

// Проверяем параметры URL
$category = isset($_GET['category']) ? $_GET['category'] : null;
$product = isset($_GET['product']) ? $_GET['product'] : null;

// Получаем данные в зависимости от параметров
$categoryData = null;
$productData = null;
$productsList = [];

if ($product) {
    // Режим просмотра товара
    $productData = getProductBySlug($product);
    if ($productData) {
        $categoryData = getCategoryBySlug($productData['category_slug']);
        $relatedProducts = getRelatedProducts($productData['id'], $productData['category_id'], 3);
    }
} elseif ($category) {
    // Режим просмотра категории
    $categoryData = getCategoryBySlug($category);
    if ($categoryData) {
        $productsList = getProductsByCategory($categoryData['id']);
    }
} else {
    // Режим просмотра всех категорий
    $allCategories = getProductCategories();
}

// Устанавливаем мета-данные страницы
if ($productData) {
    $pageTitle = htmlspecialchars($productData['name']) . ' - ' . getSetting('site_title');
    $pageDescription = htmlspecialchars(strip_tags($productData['description']));
    $pageImage = !empty($productData['image_path']) ? $productData['image_path'] : getSetting('logo_path');
} elseif ($categoryData) {
    $pageTitle = htmlspecialchars($categoryData['name']) . ' - Продукция - ' . getSetting('site_title');
    $pageDescription = htmlspecialchars($categoryData['description'] ?? 'Оборудование категории ' . $categoryData['name']);
    $pageImage = !empty($categoryData['image_path']) ? $categoryData['image_path'] : getSetting('logo_path');
} else {
    $pageTitle = 'Продукция - ' . getSetting('site_title');
    $pageDescription = 'Оборудование и решения для автоматизации учета энергоресурсов';
    $pageImage = getSetting('logo_path');
}

// Определяем пути
$headerPath = 'includes/header.php';
$footerPath = 'includes/footer.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $pageDescription ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $pageDescription ?>">
    <meta property="og:image" content="<?= $pageImage ?>">
    <meta property="og:type" content="website">
    
    <!-- Favicon -->
    <link rel="icon" href="<?= getSetting('favicon_path') ?>" type="image/x-icon">
    
    <!-- Стили -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/products.css">

</head>
<body>
    <!-- Header -->
    <?php include $headerPath; ?>
    
    <section class="products-page">
        <div class="container">
            <!-- Навигационные крошки -->
            <div class="breadcrumbs">
                <a href="products.php">Продукция</a>
                <?php if ($categoryData): ?>
                    <span>→</span>
                    <a href="products.php?category=<?= $categoryData['slug'] ?>">
                        <?= htmlspecialchars($categoryData['name']) ?>
                    </a>
                <?php endif; ?>
                <?php if ($productData): ?>
                    <span>→</span>
                    <span><?= htmlspecialchars($productData['name']) ?></span>
                <?php endif; ?>
            </div>
            
            <?php if ($productData): ?>
                <!-- Страница товара -->
                <a href="products.php?category=<?= $productData['category_slug'] ?>" class="back-button">
                    ← Назад к категории
                </a>
                
                <article class="product-detail-container">
                    <div class="product-detail-header">
                        <h1 class="product-detail-title"><?= htmlspecialchars($productData['name']) ?></h1>
                        <div class="product-category">
                            <?= htmlspecialchars($productData['category_name']) ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($productData['image_path'])): ?>
                        <img src="<?= $productData['image_path'] ?>" 
                             alt="<?= htmlspecialchars($productData['name']) ?>" 
                             class="product-main-image"
                             loading="lazy">
                    <?php endif; ?>
                    
                    <div class="product-detail-content">
                        <div class="product-info-grid">
                            <!-- Блок цены -->
                            <div class="product-price-block">
                                <div class="product-price-value">
                                    <?= number_format($productData['price'], 0, '.', ' ') ?> ₽
                                </div>
                                <div class="product-availability">
                                    <?= $productData['is_available'] ? 'В наличии' : 'Под заказ' ?>
                                </div>
                                <button class="request-button open-kp-modal" data-product="<?= htmlspecialchars($productData['name']) ?>">
                                    Запросить КП →
                                </button>
                            </div>
                            
                            <!-- Спецификации -->
                            <?php if (!empty($productData['specifications_array'])): ?>
                                <div class="product-specifications">
                                    <h3 class="specifications-title">Характеристики</h3>
                                    <ul class="specifications-list">
                                        <?php foreach ($productData['specifications_array'] as $key => $value): ?>
                                            <li>
                                                <span class="spec-label"><?= htmlspecialchars($key) ?></span>
                                                <span class="spec-value"><?= htmlspecialchars($value) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Полное описание -->
                        <?php if (!empty($productData['full_description'])): ?>
                            <div class="product-full-description">
                                <h3 class="full-description-title">Описание</h3>
                                <div class="description-content">
                                    <?= $productData['full_description'] ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        

                    </div>
                </article>
                
            <?php elseif ($categoryData): ?>
                <!-- Страница категории -->
                <header class="page-header">
                    <h1 class="page-title"><?= htmlspecialchars($categoryData['name']) ?></h1>
                    <?php if (!empty($categoryData['description'])): ?>
                        <p class="page-description"><?= htmlspecialchars($categoryData['description']) ?></p>
                    <?php endif; ?>
                </header>
                
                <?php if (!empty($productsList)): ?>
                    <div class="products-grid" id="productsGrid">
                        <?php foreach ($productsList as $index => $productItem): ?>
                            <div class="product-card" data-index="<?= $index ?>">
                                <a href="products.php?product=<?= urlencode($productItem['slug']) ?>" class="product-link">
                                    <?php if (!empty($productItem['image_path'])): ?>
                                        <div class="product-image">
                                            <img src="<?= $productItem['image_path'] ?>" 
                                                 alt="<?= htmlspecialchars($productItem['name']) ?>"
                                                 loading="lazy">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="product-content">
                                        <h3 class="product-name">
                                            <?= htmlspecialchars($productItem['name']) ?>
                                        </h3>
                                        
                                        <?php if (!empty($productItem['description'])): ?>
                                            <p class="product-description">
                                                <?= htmlspecialchars($productItem['description']) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($productItem['price'])): ?>
                                            <div class="product-price">
                                                <?= number_format($productItem['price'], 0, '.', ' ') ?> ₽
                                            </div>
                                        <?php endif; ?>
                                        
                                        <span class="product-action">Подробнее →</span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem 1rem;">
                        <p style="font-size: 1.125rem; color: #7f8c8d;">Товары в этой категории скоро будут добавлены.</p>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Главная страница продукции (все категории) -->
                <header class="page-header">
                    <h1 class="page-title">Продукция</h1>
                    <p class="page-description">Оборудование и решения для автоматизации учета энергоресурсов</p>
                </header>
                
                <?php if (!empty($allCategories)): ?>
                    <div class="categories-grid">
                        <?php foreach ($allCategories as $categoryItem): ?>
                            <div class="category-card" data-category="<?= $categoryItem['slug'] ?>">
                                <a href="products.php?category=<?= urlencode($categoryItem['slug']) ?>" class="category-link">
                                    <?php if (!empty($categoryItem['image_path'])): ?>
                                        <div class="category-image">
                                            <img src="<?= $categoryItem['image_path'] ?>" 
                                                 alt="<?= htmlspecialchars($categoryItem['name']) ?>"
                                                 loading="lazy">
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="category-content">
                                        <h3 class="category-name">
                                            <?= htmlspecialchars($categoryItem['name']) ?>
                                        </h3>
                                        
                                        <?php if (!empty($categoryItem['description'])): ?>
                                            <p class="category-description">
                                                <?= htmlspecialchars($categoryItem['description']) ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <span class="category-action">Смотреть товары →</span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem 1rem;">
                        <p style="font-size: 1.125rem; color: #7f8c8d;">Категории продукции скоро будут добавлены.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include $footerPath; ?>
    
    <!-- Скрипты -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/products.js"></script>
</body>

<div id="kpModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div class="modal-header">
            <h3>Запрос коммерческого предложения</h3>
            <p id="modalProductName">Товар: <?= htmlspecialchars($productData['name'] ?? '') ?></p>
        </div>
        <form id="kpForm" class="modal-form">
            <input type="hidden" name="product_name" id="kpProductInput" value="<?= htmlspecialchars($productData['name'] ?? '') ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="kp_name">Ваше имя *</label>
                    <input type="text" id="kp_name" name="name" placeholder="Иван Иванов" required>
                </div>
                <div class="form-group">
                    <label for="kp_phone">Телефон *</label>
                    <input type="tel" id="kp_phone" name="phone" placeholder="+7 (___) ___-__-__" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="kp_email">Email</label>
                    <input type="email" id="kp_email" name="email" placeholder="example@mail.ru">
                </div>
                <div class="form-group">
                    <label for="kp_quantity">Количество (шт.)</label>
                    <input type="number" id="kp_quantity" name="quantity" value="1" min="1">
                </div>
            </div>

            <div class="form-group">
                <label for="kp_message">Комментарий к заказу</label>
                <textarea id="kp_message" name="message" rows="3" placeholder="Укажите дополнительные пожелания или реквизиты организации"></textarea>
            </div>

            <button type="submit" class="submit-kp-btn">Отправить запрос</button>
            <p class="form-note">* — поля, обязательные для заполнения</p>
        </form>
    </div>
</div>

</html>