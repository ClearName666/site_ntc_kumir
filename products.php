<?php
// Подключаем функции
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/config.php';

$conn = getDBConnection();
$showForm = (getSetting($conn, 'form_view') == 1);
$showPrice = (getSetting($conn, 'price_view') == 1);
$mainBg = getImage($conn, 'image_background_all');

if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && isset($_POST['phone'])) {
    header('Content-Type: application/json');
    if (addProductRequest($conn, $_POST)) {
        echo json_encode(['status' => 'success', 'message' => 'Заявка успешно отправлена! Менеджер свяжется с вами.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка при сохранении заявки.']);
    }
    exit;
}

$category = $_GET['category'] ?? null;
$product = $_GET['product'] ?? null;

$categoryData = null;
$productData = null;
$productsList = [];

if ($product) {
    $productData = getProductBySlug($conn, $product);
    if ($productData) {
        $categoryData = getCategoryBySlug($conn, $productData['category_slug']);
        $relatedProducts = getRelatedProducts($conn, $productData['id'], $productData['category_id'], 3);
    }
} elseif ($category) {
    $categoryData = getCategoryBySlug($conn, $category);
    if ($categoryData) {
        $productsList = getProductsByCategory($conn, $categoryData['id']);
    }
} else {
    $allCategories = getProductCategories($conn);
}

// SEO и мета-теги
$siteTitle = getSetting($conn, 'site_title');
$defaultSocialImage = getSetting($conn, 'social_default_image');
$currentUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

if ($productData) {
    $pageTitle = htmlspecialchars($productData['name']) . ' - ' . $siteTitle;
    $rawDescription = strip_tags($productData['description'] ?? $productData['full_description'] ?? '');
    $pageDescription = truncateDescription($rawDescription, 160);
    $pageKeyword = htmlspecialchars(strip_tags($productData['name']));
    $pageImage = !empty($productData['image_path']) ? $productData['image_path'] : ($defaultSocialImage ?: getSetting($conn, 'logo_path'));
    $ogType = 'product';
    $canonicalUrl = $currentUrl;
    $robotsDirective = 'index, follow, max-image-preview:large, max-snippet:-1';
    $ogPrice = !empty($productData['price']) ? number_format($productData['price'], 0, '.', '') : null;
    $ogAvailability = $productData['is_available'] ? 'in stock' : 'out of stock';
} elseif ($categoryData) {
    $pageTitle = htmlspecialchars($categoryData['name']) . ' - Продукция - ' . $siteTitle;
    $pageDescription = truncateDescription($categoryData['description'] ?? 'Оборудование категории ' . $categoryData['name'], 160);
    $pageKeyword = htmlspecialchars($categoryData['description'] ?? 'Оборудование категории ' . $categoryData['name']);
    $pageImage = !empty($categoryData['image_path']) ? $categoryData['image_path'] : ($defaultSocialImage ?: getSetting($conn, 'logo_path'));
    $ogType = 'website';
    $canonicalUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/products.php?category=' . urlencode($categoryData['slug']);
    $robotsDirective = 'index, follow, max-image-preview:large, max-snippet:-1';
} else {
    $pageTitle = 'Продукция - ' . $siteTitle;
    $pageDescription = 'Оборудование и решения для автоматизации учета энергоресурсов';
    $pageKeyword = 'продукция НТЦ КУМИР, оборудование, аскуэ, модемы, счетчики';
    $pageImage = $defaultSocialImage ?: getSetting($conn, 'logo_path');
    $ogType = 'website';
    $canonicalUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/products.php';
    $robotsDirective = 'index, follow, max-image-preview:large, max-snippet:-1';
}

if (empty($pageDescription)) {
    $pageDescription = 'Оборудование НТЦ КУМИР для автоматизации учёта энергоресурсов. Полные характеристики и цены.';
}

$headerPath = 'includes/header.php';
$footerPath = 'includes/footer.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, interactive-widget=resizes-content">
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeyword) ?>">
    <meta name="robots" content="<?= $robotsDirective ?>">
    <meta name="author" content="НТЦ КУМИР">
    <meta name="copyright" content="<?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?>">
    <link rel="canonical" href="<?= $canonicalUrl ?>">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteTitle) ?>">
    <meta property="og:url" content="<?= $currentUrl ?>">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:image" content="<?= $pageImage ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:type" content="<?= $ogType ?>">
    <?php if ($productData && isset($ogPrice) && $ogPrice): ?>
    <meta property="product:price:amount" content="<?= $ogPrice ?>">
    <meta property="product:price:currency" content="RUB">
    <meta property="product:availability" content="<?= $ogAvailability ?>">
    <?php if (!empty($productData['sku'])): ?>
        <meta property="product:retailer_item_id" content="<?= htmlspecialchars($productData['sku']) ?>">
    <?php endif; ?>
    <?php endif; ?>
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="<?= getSetting($conn, 'favicon_path') ?>" type="image/x-icon">
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($siteTitle) ?> – продукция" href="/rss.xml">
    <link rel="stylesheet" href="assets/css/products.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/style.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/responsive.css?version=<?php echo $version_code; ?>">
    <link rel="stylesheet" href="/assets/css/header.css?version=<?php echo $version_code; ?>">
</head>
<body style="background: url('<?php echo $mainBg['image_path']; ?>') center/cover no-repeat fixed;">
    <?php include $headerPath; ?>
    <section class="products-page">
        <div class="container">
            <?php if ($productData): ?>
                <!-- ========== СТРАНИЦА ТОВАРА ========== -->
                <div class="breadcrumbs-card">
                    <div class="breadcrumbs">
                        <a href="products.php">Продукция</a>
                        <span>→</span>
                        <a href="products.php?category=<?= $productData['category_slug'] ?>"><?= htmlspecialchars($productData['category_name']) ?></a>
                        <span>→</span>
                        <span><?= htmlspecialchars($productData['name']) ?></span>
                    </div>
                </div>

                <article class="product-detail-container">
                    <div class="product-detail-header">
                        <h1 class="product-detail-title"><?= htmlspecialchars($productData['name']) ?></h1>
                        <div class="product-category"><?= htmlspecialchars($productData['category_name']) ?></div>
                    </div>

                    <div class="product-detail-layout">
                        <div class="product-detail-image">
                            <?php if (!empty($productData['image_path'])): ?>
                                <img src="<?= $productData['image_path'] ?>" alt="<?= htmlspecialchars($productData['name']) ?>" loading="lazy">
                            <?php endif; ?>
                        </div>

                    </div>
                        <div class="product-sidebar-card">
                            <div class="product-price-value"><?php if ($showPrice) echo number_format($productData['price'], 0, '.', ' ') . ' ₽'; ?></div>
                            <div class="product-availability"><?= $productData['is_available'] ? 'В наличии' : 'Под заказ' ?></div>
                            <?php if ($showForm): ?>
                                <button class="request-button open-kp-modal" data-product="<?= htmlspecialchars($productData['name']) ?>">Запросить КП →</button>
                            <?php endif; ?>
                        </div>
                    <?php if (!empty($productData['specifications_array'])): ?>
                        <div class="product-specifications">
                            <h3 class="specifications-title">Характеристики</h3>
                            <ul class="specifications-list">
                                <?php foreach ($productData['specifications_array'] as $key => $value): ?>
                                    <li><span class="spec-label"><?= htmlspecialchars($key) ?></span><span class="spec-value"><?= htmlspecialchars($value) ?></span></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($productData['full_description'])): ?>
                        <div class="product-full-description">
                            <h3 class="full-description-title">Описание</h3>
                            <div class="description-content"><?= $productData['full_description'] ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="offer-disclaimer-card">
                        <div class="offer-disclaimer">© Не является публичной офертой</div>
                    </div>

                    <div class="back-button-wrapper">
                        <a href="products.php?category=<?= $productData['category_slug'] ?>" class="back-button">← Назад к категории</a>
                    </div>
                </article>

            <?php elseif ($categoryData): ?>
                <!-- Хлебные крошки для категории -->
                <div class="breadcrumbs-card">
                    <div class="breadcrumbs">
                        <a href="products.php">Продукция</a>
                        <span>→</span>
                        <span><?= htmlspecialchars($categoryData['name']) ?></span>
                    </div>
                </div>

                <!-- СТРАНИЦА КАТЕГОРИИ (список товаров) -->
                <header class="page-header">
                    <h1 class="page-title"><?= htmlspecialchars($categoryData['name']) ?></h1>
                    <?php if (!empty($categoryData['description'])): ?>
                        <p class="page-description"><?= htmlspecialchars($categoryData['description']) ?></p>
                    <?php endif; ?>
                </header>
                <?php if (!empty($productsList)): ?>
                    <div class="products-grid categories-grid" id="productsGrid">
                        <?php foreach ($productsList as $index => $productItem): ?>
                            <div class="product-card category-card" data-index="<?= $index ?>">
                                <a href="products.php?product=<?= urlencode($productItem['slug']) ?>" class="product-link category-link">
                                    <div class="product-image-wrapper category-image">
                                        <?php if (!empty($productItem['image_path'])): ?>
                                            <img src="<?= $productItem['image_path'] ?>" alt="<?= htmlspecialchars($productItem['name']) ?>" class="product-image" loading="lazy">
                                        <?php endif; ?>
                                        <div class="product-overlay-info">
                                            <?php if ($showPrice && !empty($productItem['price'])): ?>
                                                <div class="product-price-overlay"><?= number_format($productItem['price'], 0, '.', ' ') ?> ₽</div>
                                            <?php endif; ?>
                                            <div class="product-availability-overlay"><?= $productItem['is_available'] ? 'В наличии' : 'Под заказ' ?></div>
                                            <?php if ($showForm): ?>
                                                <button class="request-button-overlay open-kp-modal" data-product="<?= htmlspecialchars($productItem['name']) ?>">Запросить КП</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="product-content category-content">
                                        <h3 class="product-name category-name"><?= htmlspecialchars($productItem['name']) ?></h3>
                                        <?php if (!empty($productItem['description'])): ?>
                                            <p class="product-description category-description"><?= htmlspecialchars($productItem['description']) ?></p>
                                        <?php endif; ?>
                                        <span class="product-action category-action">Подробнее →</span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><p>Товары в этой категории скоро будут добавлены.</p></div>
                <?php endif; ?>

            <?php else: ?>
                <!-- ГЛАВНАЯ СТРАНИЦА ПРОДУКЦИИ (все категории) -->
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
                                        <div class="category-image"><img src="<?= $categoryItem['image_path'] ?>" alt="<?= htmlspecialchars($categoryItem['name']) ?>" loading="lazy"></div>
                                    <?php endif; ?>
                                    <div class="category-content">
                                        <h3 class="category-name"><?= htmlspecialchars($categoryItem['name']) ?></h3>
                                        <?php if (!empty($categoryItem['description'])): ?>
                                            <p class="category-description"><?= htmlspecialchars($categoryItem['description']) ?></p>
                                        <?php endif; ?>
                                        <span class="category-action">Смотреть товары →</span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><p>Категории продукции скоро будут добавлены.</p></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
    <?php include $footerPath; ?>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/products.js"></script>
    <script>
        window.addEventListener('load', function() {
            window.scrollBy(0, 1);
        });
    </script>
</body>
<?php if ($showForm): ?>
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
                <div class="form-group"><label for="kp_name">Ваше имя *</label><input type="text" id="kp_name" name="name" placeholder="Иван Иванов" required></div>
                <div class="form-group"><label for="kp_phone">Телефон *</label><input type="tel" id="kp_phone" name="phone" placeholder="+7 (___) ___-__-__" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label for="kp_email">Email</label><input type="email" id="kp_email" name="email" placeholder="example@mail.ru"></div>
                <div class="form-group"><label for="kp_quantity">Количество (шт.)</label><input type="number" id="kp_quantity" name="quantity" value="1" min="1"></div>
            </div>
            <div class="form-group"><label for="kp_message">Комментарий к заказу</label><textarea id="kp_message" name="message" rows="3" placeholder="Укажите дополнительные пожелания или реквизиты организации"></textarea></div>
            <div class="custom-checkbox-wrapper">
                <label class="custom-checkbox-label">
                    <input type="checkbox" name="agreement" required>
                    <span class="checkmark"></span>
                    <span class="label-text">Я согласен с <a href="/privacy.php" target="_blank">политикой конфиденциальности</a> *</span>
                </label>
            </div>
            <button type="submit" class="submit-kp-btn">Отправить запрос</button>
            <p class="form-note">* — поля, обязательные для заполнения</p>
        </form>
    </div>
</div>
<?php endif; ?>

</html>