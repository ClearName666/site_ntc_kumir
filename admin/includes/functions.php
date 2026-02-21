<?php
session_start();

require_once __DIR__ . '/../../Cache.php'; // Убедитесь, что файл класса подключен

$cache = new Cache(); // Вот эта строчка создает объект

// Определяем корень сайта относительно этого файла
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(__DIR__ . '/../../'));
}

// Подключаем базу данных уже через константу
require_once BASE_PATH . '/config/database.php';

// Проверка авторизации администратора
function requireAdminAuth($conn) {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_token'])) {
        header('Location: login.php');
        exit();
    }
    
    // Проверка валидности токена
    // $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM admins WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
}

// Получение информации о текущем администраторе
function getCurrentAdmin($conn) {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    // $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Проверка прав администратора
function hasPermission($conn, $requiredRole = 'admin') {
    $admin = getCurrentAdmin($conn);
    
    if (!$admin) {
        return false;
    }
    
    $roles = ['editor' => 1, 'admin' => 2, 'superadmin' => 3];
    
    if (!isset($roles[$admin['role']]) || !isset($roles[$requiredRole])) {
        return false;
    }
    
    return $roles[$admin['role']] >= $roles[$requiredRole];
}

// Функция для отображения уведомлений
function displayNotification() {
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        $type = $notification['type'] ?? 'info';
        $message = $notification['message'] ?? '';
        
        echo "<script>showNotification('$message', '$type');</script>";
        
        unset($_SESSION['notification']);
    }
}

// Функция для установки уведомления
function setNotification($message, $type = 'info') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Функция для редиректа с уведомлением
function redirectWithNotification($url, $message, $type = 'info') {
    setNotification($message, $type);
    header("Location: $url");
    exit();
}

// Функция для получения пагинации
function getPagination($conn, $table, $perPage = 10, $where = '') {
    // $conn = getDBConnection();
    
    if ($where) {
        $countQuery = "SELECT COUNT(*) as total FROM $table WHERE $where";
    } else {
        $countQuery = "SELECT COUNT(*) as total FROM $table";
    }
    
    $result = $conn->query($countQuery);
    $row = $result->fetch_assoc();
    $totalItems = $row['total'];
    
    $totalPages = ceil($totalItems / $perPage);
    $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'totalItems' => $totalItems,
        'totalPages' => $totalPages,
        'currentPage' => $currentPage,
        'perPage' => $perPage,
        'offset' => $offset
    ];
}

// Функция для генерации ссылок пагинации
function generatePaginationLinks($pagination, $urlParams = '') {
    $currentPage = $pagination['currentPage'];
    $totalPages = $pagination['totalPages'];
    
    if ($totalPages <= 1) {
        return '';
    }
    
    $links = '<div class="pagination">';
    
    // Предыдущая страница
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $links .= '<a href="?page=' . $prevPage . $urlParams . '" class="page-link"><i class="fas fa-chevron-left"></i></a>';
    }
    
    // Номера страниц
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $activeClass = ($i == $currentPage) ? ' active' : '';
        $links .= '<a href="?page=' . $i . $urlParams . '" class="page-link' . $activeClass . '">' . $i . '</a>';
    }
    
    // Следующая страница
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $links .= '<a href="?page=' . $nextPage . $urlParams . '" class="page-link"><i class="fas fa-chevron-right"></i></a>';
    }
    
    $links .= '</div>';
    
    return $links;
}

// Функция для логирования действий администратора
function logAdminAction($conn, $action, $description = null, $adminId = null) {
    // $conn = getDBConnection();
    
    if ($adminId === null && isset($_SESSION['admin_id'])) {
        $adminId = $_SESSION['admin_id'];
    }
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Подготовка описания для UTF-8
    $description = $description ?? '';
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        error_log("Ошибка подготовки запроса: " . $conn->error);
        return;
    }
    
    $stmt->bind_param("isss", $adminId, $action, $description, $ipAddress);
    
    if (!$stmt->execute()) {
        error_log("Ошибка выполнения лога: " . $stmt->error);
    }
    
    $stmt->close();
}

// Функция для очистки входных данных
function cleanInput($data) {
    // Проверяем, что данные не null
    if ($data === null) {
        return '';
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Функция для создания слага (URL-friendly строка)
function createSlug($string) {
    // Приводим к нижнему регистру
    if (function_exists('mb_strtolower')) {
        $string = mb_strtolower($string, 'UTF-8');
    } else {
        $string = strtolower($string);
    }
    
    // Транслитерация русских букв
    $ru = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
           'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
           'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
           'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'];
    $en = ['a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p',
           'r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya',
           'a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p',
           'r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya'];
    
    $string = str_replace($ru, $en, $string);
    
    // Заменяем все не-латинские буквы, цифры и дефисы
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    
    // Удаляем повторяющиеся дефисы
    $string = preg_replace('/-+/', '-', $string);
    
    // Удаляем дефисы в начале и конце
    $string = trim($string, '-');
    
    return $string;
}

// Функция для проверки уникальности слага
function isSlugUnique($conn, $table, $slug, $excludeId = null) {
    // $conn = getDBConnection();
    
    if ($excludeId) {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE slug = ? AND id != ?");
        $stmt->bind_param("si", $slug, $excludeId);
    } else {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE slug = ?");
        $stmt->bind_param("s", $slug);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] == 0;
}

// Функция для загрузки изображения (исправленная версия)
function uploadImage($file, $targetDir = '../assets/images/uploads/') {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'Файл не выбран'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Файл превышает допустимый размер (upload_max_filesize в php.ini)',
            UPLOAD_ERR_FORM_SIZE => 'Файл превышает размер указанный в форме',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен только частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION => 'Загрузка файла остановлена расширением PHP'
        ];
        
        $errorCode = $file['error'];
        $errorMessage = $errorMessages[$errorCode] ?? "Неизвестная ошибка (код: $errorCode)";
        return ['success' => false, 'error' => 'Ошибка загрузки: ' . $file['error']];
    }
    
    // 1. РАЗРЕШАЕМ SVG И ЛЮБОЙ РЕГИСТР
    $fileName = $file['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['success' => false, 'error' => 'Недопустимый тип: ' . $fileExtension];
    }
    
    // 2. ОБРАБОТКА SVG (getimagesize для них не работает)
    $isSvg = ($fileExtension === 'svg');
    $imageInfo = [0, 0, 'mime' => 'image/svg+xml']; // Значения по умолчанию для SVG

    if (!$isSvg) {
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            return ['success' => false, 'error' => 'Файл не является корректным изображением'];
        }
        
        $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($imageInfo['mime'], $allowedMimeTypes)) {
            return ['success' => false, 'error' => 'Недопустимый MIME тип: ' . $imageInfo['mime']];
        }
    } else {
        // Простая проверка безопасности для SVG (что это действительно XML/SVG)
        $svgContent = file_get_contents($file['tmp_name']);
        if (strpos($svgContent, '<svg') === false) {
            return ['success' => false, 'error' => 'Файл SVG поврежден или не валиден'];
        }
    }

    // 3. ОПРЕДЕЛЯЕМ ПУТИ (используем твою логику с BASE_PATH)
    $projectRoot = defined('BASE_PATH') ? BASE_PATH : realpath(__DIR__ . '/../../');
    $absolutePath = $projectRoot . '/assets/images/uploads/';

    if (!file_exists($absolutePath)) {
        mkdir($absolutePath, 0755, true);
    }
    
    // 4. ГЕНЕРАЦИЯ ИМЕНИ
    $safeFileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
    $newFileName = uniqid() . '_' . time() . '_' . $safeFileName;
    $targetPath = $absolutePath . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        chmod($targetPath, 0644);
        $relativePathForDb = 'assets/images/uploads/' . $newFileName;
        
        return [
            'success' => true, 
            'path' => $relativePathForDb,
            'width' => $imageInfo[0] ?? 0,
            'height' => $imageInfo[1] ?? 0,
            'mime' => $isSvg ? 'image/svg+xml' : $imageInfo['mime']
        ];
    }
    
    return ['success' => false, 'error' => 'Не удалось сохранить файл на сервере'];
}

// Функция для безопасного обрезания строки с поддержкой UTF-8
function safeSubstr($string, $start, $length = null) {
    if (empty($string)) {
        return '';
    }
    
    if (function_exists('mb_substr')) {
        if ($length === null) {
            return mb_substr($string, $start, null, 'UTF-8');
        }
        return mb_substr($string, $start, $length, 'UTF-8');
    } else {
        if ($length === null) {
            return substr($string, $start);
        }
        return substr($string, $start, $length);
    }
}

// Получить все обращения
function getAllFeedback($conn) {
    // $conn = getDBConnection();
    // Сортируем: сначала новые (непрочитанные), затем по дате
    $result = $conn->query("SELECT * FROM feedback ORDER BY is_read ASC, created_at DESC");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

// Пометить как прочитанное
function markFeedbackAsRead($conn, $id) {
    // $conn = getDBConnection();
    $id = intval($id);
    return $conn->query("UPDATE feedback SET is_read = 1 WHERE id = $id");
}

// Удалить обращение
function deleteFeedback($conn, $id) {
    // $conn = getDBConnection();
    $id = intval($id);
    return $conn->query("DELETE FROM feedback WHERE id = $id");
}

/**
 * Получает все запросы на КП из базы данных
 */
function getProductRequests($conn, $perPage, $offset) {
    $stmt = $conn->prepare("SELECT * FROM product_requests ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Удаляет заявку на КП и записывает действие в лог
 */
function deleteProductRequest($conn, $id) {
    // $conn = getDBConnection();
    
    // 1. Получаем данные для лога перед удалением
    $stmt = $conn->prepare("SELECT product_name, name FROM product_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $requestData = $result->fetch_assoc();
    
    if (!$requestData) {
        return false; // Заявка уже удалена или не существует
    }
    
    // 2. Удаляем запись
    $stmt = $conn->prepare("DELETE FROM product_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // 3. Логируем действие, как в твоем FAQ
        logAdminAction($conn, 'request_delete', "Удалена заявка на " . $requestData['product_name'] . " от " . $requestData['name']);
        return true;
    }
    
    return false;
}







// SETTINGS

/**
 * Получить все настройки из базы в виде ассоциативного массива
 */
function getAllSettings($conn) {
    $result = $conn->query("SELECT setting_key, setting_value FROM settings ORDER BY setting_key");
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

/**
 * Сохранить или обновить текстовую настройку
 */
function updateOrInsertSetting($conn, $key, $value) {
    $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $updateStmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
        $updateStmt->bind_param("ss", $value, $key);
        return $updateStmt->execute();
    } else {
        $insertStmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $insertStmt->bind_param("ss", $key, $value);
        return $insertStmt->execute();
    }
}

/**
 * Специальная функция для обновления путей к изображениям (в две таблицы сразу)
 */
function updateImageSettings($conn, $imageKey, $settingKey, $path) {
    global $cache;

    // 1. Обновляем таблицу settings
    $stmt1 = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
    $stmt1->bind_param("ss", $path, $settingKey);
    $stmt1->execute();

    // 2. Обновляем или вставляем в таблицу images
    $checkStmt = $conn->prepare("SELECT id FROM images WHERE image_key = ?");
    $checkStmt->bind_param("s", $imageKey);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        $stmt2 = $conn->prepare("UPDATE images SET image_path = ?, created_at = NOW() WHERE image_key = ?");
        $stmt2->bind_param("ss", $path, $imageKey);
    } else {
        $altText = ucfirst($imageKey);
        $stmt2 = $conn->prepare("INSERT INTO images (image_key, image_path, alt_text, category, sort_order, created_at) VALUES (?, ?, ?, 'content', 0, NOW())");
        $stmt2->bind_param("sss", $imageKey, $path, $altText);
    }

    $cache->deleteByPrefix("image_key_");   

    return $stmt2->execute();

     
}





// PROFILE

/**
 * Проверка уникальности email для администратора
 */
function isEmailTaken($conn, $email, $excludeId) {
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $excludeId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Обновление основных данных профиля
 */
function updateAdminProfile($conn, $id, $fullName, $email) {
    $stmt = $conn->prepare("UPDATE admins SET full_name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $fullName, $email, $id);
    return $stmt->execute();
}

/**
 * Обновление пароля администратора
 */
function updateAdminPassword($conn, $id, $newPassword) {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $id);
    return $stmt->execute();
}

/**
 * Получение расширенной статистики администратора
 */
function getAdminStats($conn, $adminId) {
    return [
        'logins' => $conn->query("SELECT COUNT(*) as count FROM admin_logs WHERE admin_id = $adminId AND action LIKE '%login%'")->fetch_assoc()['count'],
        'actions' => $conn->query("SELECT COUNT(*) as count FROM admin_logs WHERE admin_id = $adminId")->fetch_assoc()['count'],
        'last_login' => $conn->query("SELECT created_at FROM admin_logs WHERE admin_id = $adminId AND action = 'login' ORDER BY created_at DESC LIMIT 1")->fetch_assoc()['created_at'] ?? null,
    ];
}




// PRODUCTS


/**
 * Получить список активных категорий
 */
function getActiveCategories($conn) {
    return $conn->query("SELECT id, name FROM product_categories WHERE is_active = 1 ORDER BY sort_order")->fetch_all(MYSQLI_ASSOC);
}

/**
 * Получить один товар по ID
 */
// function getProductById($conn, $id) {
//     $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     $stmt->execute();
//     return $stmt->get_result()->fetch_assoc();
// }
function getProductById($conn, $id) {
    global $cache;
    $cacheKey = "product_id_" . intval($id);
    
    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    if ($data) $cache->set($cacheKey, $data);
    return $data;
}

/**
 * Получить список товаров с пагинацией и названием категории
 */
// function getProductsList($conn, $limit, $offset) {
//     $stmt = $conn->prepare("
//         SELECT p.*, pc.name as category_name 
//         FROM products p 
//         LEFT JOIN product_categories pc ON p.category_id = pc.id 
//         ORDER BY p.sort_order, p.created_at DESC 
//         LIMIT ? OFFSET ?
//     ");
//     $stmt->bind_param("ii", $limit, $offset);
//     $stmt->execute();
//     return $stmt->get_result();
// }
function getProductsList($conn, $limit, $offset) {
    global $cache;
    $cacheKey = "admin_product_list_l{$limit}_o{$offset}";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("
        SELECT p.*, pc.name as category_name 
        FROM products p 
        LEFT JOIN product_categories pc ON p.category_id = pc.id 
        ORDER BY p.sort_order, p.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // Сразу в массив!
    
    $cache->set($cacheKey, $data);
    return $data;
}

/**
 * Добавить новый товар
 */
// function addProduct($conn, $data) {
//     $stmt = $conn->prepare("INSERT INTO products (category_id, name, slug, description, full_description, image_path, price, specifications, is_available, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
//     $stmt->bind_param("isssssdsiii", 
//         $data['category_id'], $data['name'], $data['slug'], 
//         $data['description'], $data['full_description'], 
//         $data['image_path'], $data['price'], $data['specifications'], 
//         $data['is_available'], $data['sort_order'], $data['is_active']
//     );
//     return $stmt->execute() ? $conn->insert_id : false;
// }
function addProduct($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO products (category_id, name, slug, description, full_description, image_path, price, specifications, is_available, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssdsiii", 
        $data['category_id'], $data['name'], $data['slug'], 
        $data['description'], $data['full_description'], 
        $data['image_path'], $data['price'], $data['specifications'], 
        $data['is_available'], $data['sort_order'], $data['is_active']
    );
    
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("product_");
        $cache->deleteByPrefix("admin_product_");
        $cache->deleteByPrefix("products_cat_");
        return $conn->insert_id;
    }
    return false;
}

/**
 * Обновить существующий товар
 */
// function updateProduct($conn, $id, $data) {
//     $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, full_description = ?, image_path = ?, price = ?, specifications = ?, is_available = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
//     $stmt->bind_param("isssssdsiiii", 
//         $data['category_id'], $data['name'], $data['slug'], 
//         $data['description'], $data['full_description'], 
//         $data['image_path'], $data['price'], $data['specifications'], 
//         $data['is_available'], $data['sort_order'], $data['is_active'], $id
//     );
//     return $stmt->execute();
// }
function updateProduct($conn, $id, $data) {
    global $cache;
    $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, full_description = ?, image_path = ?, price = ?, specifications = ?, is_available = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("isssssdsiiii", 
        $data['category_id'], $data['name'], $data['slug'], 
        $data['description'], $data['full_description'], 
        $data['image_path'], $data['price'], $data['specifications'], 
        $data['is_available'], $data['sort_order'], $data['is_active'], $id
    );
    
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("product_");
        $cache->deleteByPrefix("admin_product_");
        $cache->deleteByPrefix("products_cat_");
        $cache->delete("product_id_" . $id);
    }
    return $res;
}

/**
 * Удалить товар
 */
// function deleteProduct($conn, $id) {
//     $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     return $stmt->execute();
// }
function deleteProduct($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("product_");
        $cache->deleteByPrefix("admin_product_");
        $cache->deleteByPrefix("products_cat_");
        $cache->delete("product_id_" . $id);
    }
    return $res;
}


// NEWS



/**
 * Получить список новостей с пагинацией
 */
// function getNewsList($conn, $perPage, $offset) {
//     $stmt = $conn->prepare("SELECT * FROM news ORDER BY created_at DESC LIMIT ? OFFSET ?");
//     $stmt->bind_param("ii", $perPage, $offset);
//     $stmt->execute();
//     return $stmt->get_result();
// }
function getNewsList($conn, $perPage, $offset) {
    global $cache;
    $cacheKey = "admin_news_list_p" . $perPage . "_o" . $offset;

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM news ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    
    // ВАЖНО: Кэшируем результат как массив, а не объект mysqli_result
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

/**
 * Получить одну новость по ID
 */
// function getNewsById($conn, $id) {
//     $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     $stmt->execute();
//     return $stmt->get_result()->fetch_assoc();
// }
function getNewsById($conn, $id) {
    global $cache;
    $cacheKey = "news_id_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) $cache->set($cacheKey, $data);
    return $data;
}

/**
 * Добавить новую новость
 */
// function addNews($conn, $data) {
//     $stmt = $conn->prepare("INSERT INTO news (title, slug, excerpt, content, author, image_path, is_published, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
//     $stmt->bind_param("ssssssis", 
//         $data['title'], 
//         $data['slug'], 
//         $data['excerpt'], 
//         $data['content'], 
//         $data['author'], 
//         $data['image_path'], 
//         $data['is_published'], 
//         $data['published_at']
//     );
//     return $stmt->execute();
// }
function addNews($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO news (title, slug, excerpt, content, author, image_path, is_published, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssis", 
        $data['title'], 
        $data['slug'], 
        $data['excerpt'], 
        $data['content'], 
        $data['author'], 
        $data['image_path'], 
        $data['is_published'], 
        $data['published_at']
    );
    $res = $stmt->execute();
    if ($res) {
        // Очищаем кэш и для фронтенда, и для админки
        $cache->deleteByPrefix("news_"); 
        $cache->deleteByPrefix("admin_news_"); 
    }
    return $res;
}

/**
 * Обновить существующую новость
 */
// function updateNews($conn, $id, $data) {
//     $stmt = $conn->prepare("UPDATE news SET title = ?, slug = ?, excerpt = ?, content = ?, author = ?, image_path = ?, is_published = ?, published_at = ?, updated_at = NOW() WHERE id = ?");
//     $stmt->bind_param("ssssssisi", 
//         $data['title'], 
//         $data['slug'], 
//         $data['excerpt'], 
//         $data['content'], 
//         $data['author'], 
//         $data['image_path'], 
//         $data['is_published'], 
//         $data['published_at'], 
//         $id
//     );
//     return $stmt->execute();
// }
function updateNews($conn, $id, $data) {
    global $cache;
    $stmt = $conn->prepare("UPDATE news SET title = ?, slug = ?, excerpt = ?, content = ?, author = ?, image_path = ?, is_published = ?, published_at = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssssssisi", 
        $data['title'], 
        $data['slug'], 
        $data['excerpt'], 
        $data['content'], 
        $data['author'], 
        $data['image_path'], 
        $data['is_published'], 
        $data['published_at'], 
        $id
    );
    $res = $stmt->execute();
    if ($res) {
        // Сбрасываем всё: списки, саму новость по ID и админ-панель
        $cache->deleteByPrefix("news_"); 
        $cache->deleteByPrefix("admin_news_");
        $cache->delete("news_id_" . $id);
    }
    return $res;
}

/**
 * Удалить новость
 */
// function deleteNews($conn, $id) {
//     $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     return $stmt->execute();
// }
function deleteNews($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("news_"); 
        $cache->deleteByPrefix("admin_news_");
        $cache->delete("news_id_" . $id);
    }
    return $res;
}



// MENU

/**
 * Получить все пункты меню из базы
 */
// function getAllMenuItems($conn) {
//     $result = $conn->query("SELECT * FROM menu_items ORDER BY parent_id, sort_order, title");
//     return $result->fetch_all(MYSQLI_ASSOC);
// }
function getAllMenuItems($conn) {
    global $cache;
    $cacheKey = "admin_menu_all";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $result = $conn->query("SELECT * FROM menu_items ORDER BY sort_order");
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

/**
 * Получить данные одного пункта меню
 */
// function getMenuItemById($conn, $id) {
//     $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     $stmt->execute();
//     return $stmt->get_result()->fetch_assoc();
// }
function getMenuItemById($conn, $id) {
    global $cache;
    $cacheKey = "menu_item_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc(); // Важно: fetch_assoc()

    if ($data) $cache->set($cacheKey, $data);
    return $data;
}

/**
 * Добавить новый пункт
 */
// function addMenuItem($conn, $data) {
//     $stmt = $conn->prepare("INSERT INTO menu_items (title, url, parent_id, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
//     $stmt->bind_param("ssiii", $data['title'], $data['url'], $data['parent_id'], $data['sort_order'], $data['is_active']);
//     return $stmt->execute() ? $conn->insert_id : false;
// }
function addMenuItem($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO menu_items (title, url, sort_order, is_active) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $data['title'], $data['url'], $data['sort_order'], $data['is_active']);
    
    $success = $stmt->execute();
    if ($success) {
        // Очищаем всё, что связано с меню
        $cache->deleteByPrefix("system_menu"); // кэш для пользователей
        $cache->deleteByPrefix("admin_menu");  // кэш для админки
    }
    return $success;
}

/**
 * Обновить существующий пункт
 */
// function updateMenuItem($conn, $id, $data) {
//     $stmt = $conn->prepare("UPDATE menu_items SET title = ?, url = ?, parent_id = ?, sort_order = ?, is_active = ? WHERE id = ?");
//     $stmt->bind_param("ssiiii", $data['title'], $data['url'], $data['parent_id'], $data['sort_order'], $data['is_active'], $id);
//     return $stmt->execute();
// }
function updateMenuItem($conn, $id, $data) {
    global $cache;
    $stmt = $conn->prepare("UPDATE menu_items SET title = ?, url = ?, sort_order = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssiii", $data['title'], $data['url'], $data['sort_order'], $data['is_active'], $id);
    
    $success = $stmt->execute();
    if ($success) {
        $cache->delete("menu_item_" . $id);
        $cache->deleteByPrefix("system_menu");
        $cache->deleteByPrefix("admin_menu");
    }
    return $success;
}

/**
 * Удалить пункт меню
 */
// function deleteMenuItem($conn, $id) {
//     $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     return $stmt->execute();
// }
function deleteMenuItem($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $success = $stmt->execute();
    if ($success) {
        $cache->delete("menu_item_" . $id);
        $cache->deleteByPrefix("system_menu");
        $cache->deleteByPrefix("admin_menu");
    }
    return $success;
}

/**
 * Проверить наличие подпунктов перед удалением
 */
function hasChildMenu($conn, $id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM menu_items WHERE parent_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['count'] > 0;
}

/**
 * Рекурсивное построение дерева меню
 */
function buildMenuTree($items, $parentId = 0) {
    $tree = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $children = buildMenuTree($items, $item['id']);
            if ($children) {
                $item['children'] = $children;
            }
            $tree[] = $item;
        }
    }
    return $tree;
}

/**
 * Получить список потенциальных родителей (только верхний уровень)
 * Исключаем текущий ID, чтобы не зациклить дерево
 */
function getPotentialParents($conn, $excludeId = 0) {
    $excludeId = intval($excludeId);
    $sql = "SELECT id, title FROM menu_items WHERE parent_id = 0";
    if ($excludeId > 0) {
        $sql .= " AND id != $excludeId";
    }
    $sql .= " ORDER BY title";
    
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}



// LOGS


/**
 * Получить список логов с учетом фильтров
 */
function getAdminLogs($conn, $filters = [], $limit = 100) {
    $where = "1=1";
    $params = [];
    $types = "";

    if (!empty($filters['admin_id'])) {
        $where .= " AND al.admin_id = ?";
        $params[] = intval($filters['admin_id']);
        $types .= "i";
    }

    if (!empty($filters['action'])) {
        $where .= " AND al.action LIKE ?";
        $params[] = "%" . $filters['action'] . "%";
        $types .= "s";
    }

    if (!empty($filters['date_from'])) {
        $where .= " AND DATE(al.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types .= "s";
    }

    if (!empty($filters['date_to'])) {
        $where .= " AND DATE(al.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types .= "s";
    }

    $query = "SELECT al.*, a.username 
              FROM admin_logs al 
              LEFT JOIN admins a ON al.admin_id = a.id 
              WHERE $where 
              ORDER BY al.created_at DESC 
              LIMIT ?";
    
    $params[] = intval($limit);
    $types .= "i";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Очистить старые логи
 */
function clearOldLogs($conn, $days) {
    $days = intval($days);
    $date = date('Y-m-d H:i:s', strtotime("-$days days"));
    $stmt = $conn->prepare("DELETE FROM admin_logs WHERE created_at < ?");
    $stmt->bind_param("s", $date);
    return $stmt->execute();
}

/**
 * Список всех админов для выпадающего списка
 */
function getAdminsList($conn) {
    return $conn->query("SELECT id, username FROM admins ORDER BY username")->fetch_all(MYSQLI_ASSOC);
}






// INDEX 


/**
 * Получить общую статистику сайта для дашборда
 */
function getDashboardStats($conn) {
    // Вспомогательная функция внутри для сокращения кода
    $getCount = function($table, $where = "") use ($conn) {
        $sql = "SELECT COUNT(*) as count FROM " . $table . ($where ? " WHERE $where" : "");
        $result = $conn->query($sql);
        return $result ? $result->fetch_assoc()['count'] : 0;
    };

    return [
        'articles'     => $getCount('articles'),
        'news'         => $getCount('news'),
        'products'     => $getCount('products'),
        'faq'          => $getCount('faq'),
        'categories'   => $getCount('product_categories'),
        'admins'       => $getCount('admins', 'is_active = 1'),
        'feedback'     => $getCount('feedback'),
        'feedback_new' => $getCount('feedback', 'is_read = 0'),
        'requests'     => $getCount('product_requests'),
        'requests_new' => $getCount('product_requests', "status = 'new'"),
    ];
}

/**
 * Получить список последних действий администраторов
 */
function getRecentAdminLogs($conn, $limit = 10) {
    $limit = intval($limit);
    $sql = "SELECT al.*, a.username 
            FROM admin_logs al 
            LEFT JOIN admins a ON al.admin_id = a.id 
            ORDER BY al.created_at DESC 
            LIMIT $limit";
    
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Определяет иконку для типа действия (для верстки)
 */
function getLogIcon($action) {
    if (strpos($action, 'login') !== false) return 'fa-sign-in-alt';
    if (strpos($action, 'logout') !== false) return 'fa-sign-out-alt';
    if (strpos($action, 'add') !== false) return 'fa-plus';
    if (strpos($action, 'edit') !== false) return 'fa-edit';
    if (strpos($action, 'delete') !== false) return 'fa-trash';
    return 'fa-user';
}



// FAQ


/**
 * Общая функция для сброса всего кэша FAQ
 */
function clearFaqCache() {
    global $cache;
    $cache->deleteByPrefix("faq_");
    $cache->deleteByPrefix("admin_faq_");
}


/**
 * Получить список FAQ с пагинацией
 */
// function getFaqList($conn, $limit, $offset) {
//     $stmt = $conn->prepare("SELECT * FROM faq ORDER BY category, sort_order, id DESC LIMIT ? OFFSET ?");
//     $stmt->bind_param("ii", $limit, $offset);
//     $stmt->execute();
//     return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// }
function getFaqList($conn, $limit, $offset) {
    global $cache;
    $cacheKey = "admin_faq_list_l{$limit}_o{$offset}";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM faq ORDER BY category, sort_order, id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

/**
 * Получить один вопрос по ID
 */
// function getFaqById($conn, $id) {
//     $stmt = $conn->prepare("SELECT * FROM faq WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     $stmt->execute();
//     return $stmt->get_result()->fetch_assoc();
// }
function getFaqById($conn, $id) {
    global $cache;
    $cacheKey = "faq_item_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM faq WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) {
        $cache->set($cacheKey, $data);
    }
    return $data;
}

/**
 * Добавить новый вопрос
 */
function addFaq($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO faq (question, answer, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $data['question'], $data['answer'], $data['category'], $data['sort_order'], $data['is_active']);
    $res = $stmt->execute();
    if ($res) clearFaqCache();
    return $res;
}

/**
 * Обновить существующий вопрос
 */
function updateFaq($conn, $id, $data) {
    $stmt = $conn->prepare("UPDATE faq SET question = ?, answer = ?, category = ?, sort_order = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("sssiii", $data['question'], $data['answer'], $data['category'], $data['sort_order'], $data['is_active'], $id);
    $res = $stmt->execute();
    if ($res) {
        clearFaqCache();
        global $cache;
        $cache->delete("faq_item_" . $id);
    }
    return $res;
}

/**
 * Удалить вопрос
 */
function deleteFaq($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM faq WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    if ($res) clearFaqCache();
    return $res;
}




// CONTANT

/**
 * Получить все контент-блоки
 */
function getAllContentBlocks($conn) {
    $result = $conn->query("SELECT * FROM content_blocks ORDER BY block_key");
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Получить конкретный блок по ID
 */
function getContentBlockById($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM content_blocks WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Обновить контент-блок
 */
function updateContentBlock($conn, $id, $title, $content) {
    $stmt = $conn->prepare("UPDATE content_blocks SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $title, $content, $id);
    return $stmt->execute();
}

/**
 * Вспомогательная функция для обрезки текста (превью)
 */
function getContentPreview($text, $limit = 100) {
    $text = htmlspecialchars($text);
    if (function_exists('mb_substr')) {
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text;
    }
    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}




// CONTACTS


/**
 * Получить список всех контактов с учетом пагинации
 */
// function getContactsList($conn, $perPage, $offset) {
//     $stmt = $conn->prepare("SELECT * FROM contacts ORDER BY contact_type, sort_order, title LIMIT ? OFFSET ?");
//     $stmt->bind_param("ii", $perPage, $offset);
//     $stmt->execute();
//     return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
// }
// Кэширование 
function getContactsList($conn, $perPage, $offset) {
    global $cache;
    $cacheKey = "contacts_list_p{$perPage}_o{$offset}";
    
    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM contacts ORDER BY contact_type, sort_order, title LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

/**
 * Получить данные одного контакта
 */
// function getContactById($conn, $id) {
//     $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     $stmt->execute();
//     return $stmt->get_result()->fetch_assoc();
// }
// Кэширование
function getContactById($conn, $id) {
    global $cache;
    $cacheKey = "contact_item_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) {
        $cache->set($cacheKey, $data);
    }
    return $data;
}
/**
 * Добавить контакт
 */
// function addContact($conn, $data) {
//     $stmt = $conn->prepare("INSERT INTO contacts (contact_type, title, value, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
//     $stmt->bind_param("ssssii", 
//         $data['contact_type'], 
//         $data['title'], 
//         $data['value'], 
//         $data['icon'], 
//         $data['sort_order'], 
//         $data['is_active']
//     );
//     return $stmt->execute();
// }
function addContact($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO contacts (contact_type, title, value, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", 
        $data['contact_type'], $data['title'], $data['value'], 
        $data['icon'], $data['sort_order'], $data['is_active']
    );
    
    $result = $stmt->execute();
    if ($result) {
        // Убиваем все списки контактов (и админские, и фронтенд)
        $cache->deleteByPrefix("contacts_list");
        $cache->deleteByPrefix("contacts_type");
    }
    return $result;
}

/**
 * Обновить контакт
 */
// function updateContact($conn, $id, $data) {
//     $stmt = $conn->prepare("UPDATE contacts SET contact_type = ?, title = ?, value = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?");
//     $stmt->bind_param("ssssiii", 
//         $data['contact_type'], 
//         $data['title'], 
//         $data['value'], 
//         $data['icon'], 
//         $data['sort_order'], 
//         $data['is_active'], 
//         $id
//     );
//     return $stmt->execute();
// }
function updateContact($conn, $id, $data) {
    global $cache;
    $stmt = $conn->prepare("UPDATE contacts SET contact_type = ?, title = ?, value = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssssiii", 
        $data['contact_type'], $data['title'], $data['value'], 
        $data['icon'], $data['sort_order'], $data['is_active'], $id
    );
    
    $result = $stmt->execute();
    if ($result) {
        // Удаляем конкретный айтем
        $cache->delete("contact_item_" . $id);
        // Удаляем все списки, так как в них данные этого контакта теперь неверные
        $cache->deleteByPrefix("contacts_list");
        $cache->deleteByPrefix("contacts_type");
    }
    return $result;
}
/**
 * Удалить контакт
 */
// function deleteContact($conn, $id) {
//     $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     return $stmt->execute();
// }
function deleteContact($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $result = $stmt->execute();
    if ($result) {
        $cache->delete("contact_item_" . $id);
        $cache->deleteByPrefix("contacts_list");
        $cache->deleteByPrefix("contacts_type");
    }
    return $result;
}


// CATEGORIES

/**
 * Получить все категории с подсчетом товаров
 */
// function getAllCategoriesWithCount($conn) {
//     $sql = "SELECT pc.*, COUNT(p.id) as product_count 
//             FROM product_categories pc 
//             LEFT JOIN products p ON pc.id = p.category_id 
//             GROUP BY pc.id 
//             ORDER BY pc.sort_order, pc.name";
//     return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
// }
function getAllCategoriesWithCount($conn) {
    global $cache;
    $cacheKey = "categories_all_with_count";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $sql = "SELECT pc.*, COUNT(p.id) as product_count 
            FROM product_categories pc 
            LEFT JOIN products p ON pc.id = p.category_id 
            GROUP BY pc.id 
            ORDER BY pc.sort_order, pc.name";
    
    $data = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}


/**
 * Получить данные одной категории
 */
// function getCategoryById($conn, $id) {
//     $stmt = $conn->prepare("SELECT * FROM product_categories WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     $stmt->execute();
//     return $stmt->get_result()->fetch_assoc();
// }
function getCategoryById($conn, $id) {
    global $cache;
    $cacheKey = "categories_item_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM product_categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) {
        $cache->set($cacheKey, $data);
    }
    return $data;
}

/**
 * Добавить категорию
 */
// function addCategory($conn, $data) {
//     $stmt = $conn->prepare("INSERT INTO product_categories (name, slug, description, image_path, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
//     $stmt->bind_param("ssssii", $data['name'], $data['slug'], $data['description'], $data['image_path'], $data['sort_order'], $data['is_active']);
//     return $stmt->execute();
// }
function addCategory($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO product_categories (name, slug, description, image_path, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $data['name'], $data['slug'], $data['description'], $data['image_path'], $data['sort_order'], $data['is_active']);
    
    $success = $stmt->execute();
    if ($success) {
        // Очищаем всё, что связано с категориями
        $cache->deleteByPrefix("categories_");
    }
    return $success;
}

/**
 * Обновить категорию
 */
// function updateCategory($conn, $id, $data) {
//     $stmt = $conn->prepare("UPDATE product_categories SET name = ?, slug = ?, description = ?, image_path = ?, sort_order = ?, is_active = ? WHERE id = ?");
//     $stmt->bind_param("ssssiii", $data['name'], $data['slug'], $data['description'], $data['image_path'], $data['sort_order'], $data['is_active'], $id);
//     return $stmt->execute();
// }
function updateCategory($conn, $id, $data) {
    global $cache;
    $stmt = $conn->prepare("UPDATE product_categories SET name = ?, slug = ?, description = ?, image_path = ?, sort_order = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssssiii", $data['name'], $data['slug'], $data['description'], $data['image_path'], $data['sort_order'], $data['is_active'], $id);
    
    $success = $stmt->execute();
    if ($success) {
        // Сбрасываем кэш этой категории и всех списков
        $cache->deleteByPrefix("categories_");
        $cache->deleteByPrefix("product_");
        $cache->deleteByPrefix("admin_product_");
    }
    return $success;
}

/**
 * Проверить, есть ли товары в категории перед удалением
 */
function getCategoryProductCount($conn, $categoryId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'] ?? 0;
}

/**
 * Генерация уникального слага
 */
function generateUniqueCategorySlug($conn, $name, $currentId = 0) {
    $slug = createSlug($name);
    $originalSlug = $slug;
    $counter = 1;
    
    while (!isSlugUnique($conn, 'product_categories', $slug, $currentId)) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    return $slug;
}

/**
 * Полное удаление категории
 */
// function deleteCategory($conn, $id) {
//     $stmt = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     return $stmt->execute();
// }
function deleteCategory($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $success = $stmt->execute();
    if ($success) {
        // После удаления данные в кэше больше не актуальны
        $cache->deleteByPrefix("categories_");
    }
    return $success;
}


// ARTICLES


/**
 * Получить список статей с пагинацией
 */
// function getArticlesList($conn, $perPage, $offset) {
//     $stmt = $conn->prepare("SELECT * FROM articles ORDER BY created_at DESC LIMIT ? OFFSET ?");
//     $stmt->bind_param("ii", $perPage, $offset);
//     $stmt->execute();
//     return $stmt->get_result();
// }
function getArticlesList($conn, $perPage, $offset) {
    global $cache;
    // Ключ уникален для каждой страницы пагинации
    $cacheKey = "articles_list_p{$perPage}_o{$offset}";
    
    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM articles ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    
    // Преобразуем в массив, так как объект результата нельзя кэшировать
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

/**
 * Получить данные одной статьи
 */
// function getArticleById($conn, $id) {
//     $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     $stmt->execute();
//     return $stmt->get_result()->fetch_assoc();
// }
function getArticleById($conn, $id) {
    global $cache;
    $cacheKey = "article_item_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) {
        $cache->set($cacheKey, $data);
    }
    return $data;
}

/**
 * Добавить статью
 */
// function addArticle($conn, $data) {
//     $stmt = $conn->prepare("INSERT INTO articles (title, slug, excerpt, content, author, image_path, is_published, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
//     $stmt->bind_param("ssssssis", $data['title'], $data['slug'], $data['excerpt'], $data['content'], $data['author'], $data['image_path'], $data['is_published'], $data['published_at']);
//     return $stmt->execute();
// }
function addArticle($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO articles (title, slug, excerpt, content, author, image_path, is_published, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssis", 
        $data['title'], $data['slug'], $data['excerpt'], 
        $data['content'], $data['author'], $data['image_path'], 
        $data['is_published'], $data['published_at']
    );
    
    $success = $stmt->execute();
    if ($success) {
        // Сбрасываем все списки статей, так как появилась новая запись
        $cache->deleteByPrefix("articles_list");
        $cache->deleteByPrefix("article_slug");

    }
    return $success;
}

/**
 * Обновить статью
 */
// function updateArticle($conn, $id, $data) {
//     $stmt = $conn->prepare("UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, author = ?, image_path = ?, is_published = ?, published_at = ?, updated_at = NOW() WHERE id = ?");
//     $stmt->bind_param("ssssssisi", $data['title'], $data['slug'], $data['excerpt'], $data['content'], $data['author'], $data['image_path'], $data['is_published'], $data['published_at'], $id);
//     return $stmt->execute();
// }
function updateArticle($conn, $id, $data) {
    global $cache;
    $stmt = $conn->prepare("UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, author = ?, image_path = ?, is_published = ?, published_at = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssssssisi", 
        $data['title'], $data['slug'], $data['excerpt'], 
        $data['content'], $data['author'], $data['image_path'], 
        $data['is_published'], $data['published_at'], 
        $id
    );
    
    $success = $stmt->execute();
    if ($success) {
        // Удаляем кэш самой статьи
        $cache->delete("article_item_" . $id);
        // Сбрасываем списки, так как заголовки или анонсы в списках могли измениться
        $cache->deleteByPrefix("articles_list");
        $cache->deleteByPrefix("article_slug");
    }
    return $success;
}

/**
 * Удалить статью
 */
// function deleteArticle($conn, $id) {
//     $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     return $stmt->execute();
// }
function deleteArticle($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $success = $stmt->execute();
    if ($success) {
        // Полная очистка по префиксу (удалит и саму статью, и все списки)
        $cache->delete("article_slug_" . $id);
        $cache->deleteByPrefix("articles_list");

    }
    return $success;
}

/**
 * Генерация уникального слага для статьи
 */
function generateUniqueArticleSlug($conn, $title, $currentId = 0) {
    $slug = createSlug($title);
    $originalSlug = $slug;
    $counter = 1;
    while (!isSlugUnique($conn, 'articles', $slug, $currentId)) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }
    return $slug;
}



// ADMINS

/**
 * Получить список всех администраторов
 */
// function getAllAdmins($conn) {
//     return $conn->query("SELECT * FROM admins ORDER BY role, username")->fetch_all(MYSQLI_ASSOC);
// }
function getAllAdmins($conn) {
    global $cache;
    $cacheKey = "admins_all";

    // Проверяем кэш
    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    // Если кэша нет, идем в БД
    $result = $conn->query("SELECT * FROM admins ORDER BY role, username");
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    // Сохраняем результат
    $cache->set($cacheKey, $data);
    return $data;
}

/**
 * Получить данные одного админа
 */
// function getAdminById($conn, $id) {
//     $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     $stmt->execute();
//     return $stmt->get_result()->fetch_assoc();
// }
function getAdminById($conn, $id) {
    global $cache;
    $cacheKey = "admin_item_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) {
        $cache->set($cacheKey, $data);
    }
    return $data;
}

/**
 * Проверка уникальности username или email
 */
function isAdminUnique($conn, $username, $email, $excludeId = 0) {
    $stmt = $conn->prepare("SELECT id FROM admins WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $username, $email, $excludeId);
    $stmt->execute();
    return $stmt->get_result()->num_rows === 0;
}

/**
 * Добавить нового администратора
 */
// function addAdmin($conn, $data) {
//     $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
//     $stmt = $conn->prepare("INSERT INTO admins (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
//     $stmt->bind_param("sssssi", $data['username'], $data['email'], $passwordHash, $data['full_name'], $data['role'], $data['is_active']);
//     return $stmt->execute();
// }
function addAdmin($conn, $data) {
    global $cache;
    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admins (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $data['username'], $data['email'], $passwordHash, $data['full_name'], $data['role'], $data['is_active']);
    
    $success = $stmt->execute();
    if ($success) {
        // Очищаем весь кэш админов (списки и поиск)
        $cache->deleteByPrefix("admins_");
    }
    return $success;
}

/**
 * Обновить данные администратора
 */
// function updateAdmin($conn, $id, $data) {
//     if (!empty($data['password'])) {
//         // Если пароль меняется
//         $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
//         $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, password_hash = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
//         $stmt->bind_param("sssssii", $data['username'], $data['email'], $passwordHash, $data['full_name'], $data['role'], $data['is_active'], $id);
//     } else {
//         // Если пароль НЕ меняется
//         $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
//         $stmt->bind_param("ssssii", $data['username'], $data['email'], $data['full_name'], $data['role'], $data['is_active'], $id);
//     }
//     return $stmt->execute();
// }
function updateAdmin($conn, $id, $data) {
    global $cache;
    
    if (!empty($data['password'])) {
        // Если пароль меняется
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, password_hash = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssssii", $data['username'], $data['email'], $passwordHash, $data['full_name'], $data['role'], $data['is_active'], $id);
    } else {
        // Если пароль НЕ меняется
        $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $data['username'], $data['email'], $data['full_name'], $data['role'], $data['is_active'], $id);
    }
    
    $success = $stmt->execute();
    if ($success) {
        // Удаляем кэш конкретного админа и общие списки
        $cache->delete("admin_item_" . $id);
        $cache->deleteByPrefix("admins_");
    }
    return $success;
}

/**
 * Удалить администратора
 */
// function deleteAdmin($conn, $id) {
//     $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
//     $stmt->bind_param("i", $id);
//     return $stmt->execute();
// }
function deleteAdmin($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $success = $stmt->execute();
    if ($success) {
        // Чистим всё, что связано с админами
        $cache->delete("admin_item_" . $id);
        $cache->deleteByPrefix("admins_");
    }
    return $success;
}

?>