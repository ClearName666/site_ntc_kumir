<?php
session_start();

include 'deleteFile.php';
require_once __DIR__ . '/../../Cache.php'; 

$cache = new Cache();

if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(__DIR__ . '/../../'));
}

require_once BASE_PATH . '/config/database.php';

function requireAdminAuth($conn) {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_token'])) {
        header('Location: login.php');
        exit();
    }
    
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

function getCurrentAdmin($conn) {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

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


function setNotification($message, $type = 'info') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getNotification() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notification;
    }
    
    return null;
}

function redirectWithNotification($url, $message, $type = 'info') {
    setNotification($message, $type);
    header("Location: $url");
    exit();
}

function getPagination($conn, $table, $perPage = 10, $where = '') {
    
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

function generatePaginationLinks($pagination, $urlParams = '') {
    $currentPage = $pagination['currentPage'];
    $totalPages = $pagination['totalPages'];
    
    if ($totalPages <= 1) {
        return '';
    }
    
    $links = '<div class="pagination">';
    
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        $links .= '<a href="?page=' . $prevPage . $urlParams . '" class="page-link"><i class="fas fa-chevron-left"></i></a>';
    }
    
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $activeClass = ($i == $currentPage) ? ' active' : '';
        $links .= '<a href="?page=' . $i . $urlParams . '" class="page-link' . $activeClass . '">' . $i . '</a>';
    }
    
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        $links .= '<a href="?page=' . $nextPage . $urlParams . '" class="page-link"><i class="fas fa-chevron-right"></i></a>';
    }
    
    $links .= '</div>';
    
    return $links;
}

function logAdminAction($conn, $action, $description = null, $adminId = null) {
    
    if ($adminId === null && isset($_SESSION['admin_id'])) {
        $adminId = $_SESSION['admin_id'];
    }
    
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
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

function cleanInput($data) {
    if ($data === null) {
        return '';
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function createSlug($string) {
    if (function_exists('mb_strtolower')) {
        $string = mb_strtolower($string, 'UTF-8');
    } else {
        $string = strtolower($string);
    }
    
    $ru = ['а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п',
           'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я',
           'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П',
           'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'];
    $en = ['a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p',
           'r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya',
           'a','b','v','g','d','e','e','zh','z','i','y','k','l','m','n','o','p',
           'r','s','t','u','f','h','ts','ch','sh','sch','','y','','e','yu','ya'];
    
    $string = str_replace($ru, $en, $string);
    
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    
    $string = preg_replace('/-+/', '-', $string);
    
    $string = trim($string, '-');
    
    return $string;
}

function isSlugUnique($conn, $table, $slug, $excludeId = null) {
    
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

function uploadImage($file, $targetDir = '../assets/images/uploads/', $maxSize = 1048576) {
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

    if ($file['size'] > $maxSize) {
        $sizeInMB = $maxSize / 1048576;
        $fileSizeInMB = round($file['size'] / 1048576, 2);
        return ['success' => false, 'error' => "Файл слишком большой. Максимальный размер: {$sizeInMB} МБ. Ваш файл: {$fileSizeInMB} МБ"];
    }
    
    $fileName = $file['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['success' => false, 'error' => 'Недопустимый тип: ' . $fileExtension];
    }
    
    $isSvg = ($fileExtension === 'svg');
    $imageInfo = [0, 0, 'mime' => 'image/svg+xml'];

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
        $svgContent = file_get_contents($file['tmp_name']);
        if (strpos($svgContent, '<svg') === false) {
            return ['success' => false, 'error' => 'Файл SVG поврежден или не валиден'];
        }
    }

    $projectRoot = defined('BASE_PATH') ? BASE_PATH : realpath(__DIR__ . '/../../');
    $absolutePath = $projectRoot . '/assets/images/uploads/';

    if (!file_exists($absolutePath)) {
        mkdir($absolutePath, 0755, true);
    }
    
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

function getAllFeedback($conn) {
    $result = $conn->query("SELECT * FROM feedback ORDER BY is_read ASC, created_at DESC");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

function markFeedbackAsRead($conn, $id) {
    $id = intval($id);
    return $conn->query("UPDATE feedback SET is_read = 1 WHERE id = $id");
}

function deleteFeedback($conn, $id) {
    $id = intval($id);
    return $conn->query("DELETE FROM feedback WHERE id = $id");
}

function getProductRequests($conn, $perPage, $offset) {
    $stmt = $conn->prepare("SELECT * FROM product_requests ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    return $stmt->get_result();
}

function deleteProductRequest($conn, $id) {
    
    $stmt = $conn->prepare("SELECT product_name, name FROM product_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $requestData = $result->fetch_assoc();
    
    if (!$requestData) {
        return false;
    }
    
    $stmt = $conn->prepare("DELETE FROM product_requests WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAdminAction($conn, 'request_delete', "Удалена заявка на " . $requestData['product_name'] . " от " . $requestData['name']);
        return true;
    }
    
    return false;
}








function clearCache($conn) {
    global $cache;
    
    $result = $cache->clearAll();
    
    logAdminAction($conn, "clear_cache", "Очистка кэша: " . $result['message']);
    
    if ($result['success'] && $result['errors'] === 0) {
        return [
            'success' => true, 
            'message' => "Очистка кэша прошла успешно! " . $result['message']
        ];
    } else {
        return [
            'success' => false, 
            'message' => "Ошибка при очистке кэша: " . $result['message']
        ];
    }
}


function getAllSettings($conn) {
    $result = $conn->query("SELECT setting_key, setting_value FROM settings ORDER BY setting_key");
    $settings = [];
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    return $settings;
}

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

function updateImageSettings($conn, $imageKey, $settingKey, $path) {
    global $cache;

    $oldPath = null;
    $sel = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $sel->bind_param("s", $settingKey);
    $sel->execute();
    $res = $sel->get_result();
    if ($row = $res->fetch_assoc()) {
        $oldPath = $row['setting_value'];
    }
    $sel->close();

    $stmt1 = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
    $stmt1->bind_param("ss", $path, $settingKey);
    $success = $stmt1->execute();
    $stmt1->close();

    if ($success) {
        if (!empty($oldPath) && $oldPath !== $path) {
            deleteImageFromServer($oldPath);
        }

        $cache->deleteByPrefix("image_key_");
    }

    return $success;
}




function getAllImagesFromDB($conn) {
    global $cache;
    $cacheKey = 'all_site_images';
    
    $cachedData = $cache->get($cacheKey);
    if ($cachedData !== null) {
        return $cachedData;
    }

    $result = $conn->query("SELECT * FROM images ORDER BY category, id");
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }

    $cache->set($cacheKey, $images);
    return $images;
}

function updateImageInTable($conn, $id, $newPath) {
    global $cache;

    $oldPath = null;
    $stmtGet = $conn->prepare("SELECT image_path FROM images WHERE id = ?");
    $stmtGet->bind_param("i", $id);
    $stmtGet->execute();
    $result = $stmtGet->get_result();
    if ($row = $result->fetch_assoc()) {
        $oldPath = $row['image_path'];
    }
    $stmtGet->close();

    $stmt = $conn->prepare("UPDATE images SET image_path = ? WHERE id = ?");
    $stmt->bind_param("si", $newPath, $id);
    $res = $stmt->execute();
    $stmt->close();

    if ($res) {
        if ($oldPath && $oldPath !== $newPath) {
            deleteImageFromServer($oldPath); 
        }

        $cache->delete('all_site_images');
        $cache->deleteByPrefix('settings_');
        $cache->deleteByPrefix('image_key_');


    }

    return $res;
}





function isEmailTaken($conn, $email, $excludeId) {
    $stmt = $conn->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $excludeId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function updateAdminProfile($conn, $id, $fullName, $email) {
    $stmt = $conn->prepare("UPDATE admins SET full_name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $fullName, $email, $id);
    return $stmt->execute();
}

function updateAdminPassword($conn, $id, $newPassword) {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
    $stmt->bind_param("si", $hash, $id);
    return $stmt->execute();
}

function getAdminStats($conn, $adminId) {
    return [
        'logins' => $conn->query("SELECT COUNT(*) as count FROM admin_logs WHERE admin_id = $adminId AND action LIKE '%login%'")->fetch_assoc()['count'],
        'actions' => $conn->query("SELECT COUNT(*) as count FROM admin_logs WHERE admin_id = $adminId")->fetch_assoc()['count'],
        'last_login' => $conn->query("SELECT created_at FROM admin_logs WHERE admin_id = $adminId AND action = 'login' ORDER BY created_at DESC LIMIT 1")->fetch_assoc()['created_at'] ?? null,
    ];
}






function getActiveCategories($conn) {
    return $conn->query("SELECT id, name FROM product_categories WHERE is_active = 1 ORDER BY sort_order")->fetch_all(MYSQLI_ASSOC);
}

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
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

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

function updateProduct($conn, $id, $data) {
    global $cache;

    $oldImagePath = null;
    $sel = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $oldImagePath = $row['image_path'];
    }
    $sel->close();

    $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, full_description = ?, image_path = ?, price = ?, specifications = ?, is_available = ?, sort_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("isssssdsiiii", 
        $data['category_id'], $data['name'], $data['slug'], 
        $data['description'], $data['full_description'], 
        $data['image_path'], $data['price'], $data['specifications'], 
        $data['is_available'], $data['sort_order'], $data['is_active'], $id
    );
    
    $res = $stmt->execute();
    $stmt->close();

    if ($res) {
        if (!empty($oldImagePath) && $oldImagePath !== $data['image_path']) {
            deleteImageFromServer($oldImagePath);
        }

        $cache->deleteByPrefix("product_");
        $cache->deleteByPrefix("admin_product_");
        $cache->deleteByPrefix("products_cat_");
        $cache->delete("product_id_" . $id);
    }
    
    return $res;
}

function deleteProduct($conn, $id) {
    global $cache;

    $imagePath = null;
    $sel = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $imagePath = $row['image_path'];
    }
    $sel->close();

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    $stmt->close();
    
    if ($res) {
        if (!empty($imagePath)) {
            deleteImageFromServer($imagePath);
        }

        $cache->deleteByPrefix("product_");
        $cache->deleteByPrefix("admin_product_");
        $cache->deleteByPrefix("products_cat_");
        $cache->delete("product_id_" . $id);
    }
    
    return $res;
}





function getNewsList($conn, $perPage, $offset) {
    global $cache;
    $cacheKey = "admin_news_list_p" . $perPage . "_o" . $offset;

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM news ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

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
        $cache->deleteByPrefix("news_"); 
        $cache->deleteByPrefix("admin_news_"); 
    }
    return $res;
}

function updateNews($conn, $id, $data) {
    global $cache;

    $oldImagePath = null;
    $sel = $conn->prepare("SELECT image_path FROM news WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $oldImagePath = $row['image_path'];
    }
    $sel->close();

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
    $stmt->close();

    if ($res) {
        if (!empty($oldImagePath) && $oldImagePath !== $data['image_path']) {
            deleteImageFromServer($oldImagePath);
        }

        $cache->deleteByPrefix("news_"); 
        $cache->deleteByPrefix("admin_news_");
        $cache->delete("news_id_" . $id);
    }
    
    return $res;
}

function deleteNews($conn, $id) {
    global $cache;

    $imagePath = null;
    $sel = $conn->prepare("SELECT image_path FROM news WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $imagePath = $row['image_path'];
    }
    $sel->close();

    $stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    $stmt->close();

    if ($res) {
        if (!empty($imagePath)) {
            deleteImageFromServer($imagePath);
        }

        $cache->deleteByPrefix("news_"); 
        $cache->deleteByPrefix("admin_news_");
        $cache->delete("news_id_" . $id);
    }
    
    return $res;
}




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

function getMenuItemById($conn, $id) {
    global $cache;
    $cacheKey = "menu_item_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) $cache->set($cacheKey, $data);
    return $data;
}

function addMenuItem($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO menu_items (title, url, sort_order, is_active) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $data['title'], $data['url'], $data['sort_order'], $data['is_active']);
    
    $success = $stmt->execute();
    if ($success) {
        $cache->deleteByPrefix("system_menu");
        $cache->deleteByPrefix("admin_menu");
    }
    return $success;
}

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

function hasChildMenu($conn, $id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM menu_items WHERE parent_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row['count'] > 0;
}

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

function getPotentialParents($conn, $excludeId = 0) {
    $excludeId = intval($excludeId);
    $sql = "SELECT id, title FROM menu_items WHERE parent_id = 0";
    if ($excludeId > 0) {
        $sql .= " AND id != $excludeId";
    }
    $sql .= " ORDER BY title";
    
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}





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

function clearOldLogs($conn, $days) {
    $days = intval($days);
    $date = date('Y-m-d H:i:s', strtotime("-$days days"));
    $stmt = $conn->prepare("DELETE FROM admin_logs WHERE created_at < ?");
    $stmt->bind_param("s", $date);
    return $stmt->execute();
}

function getAdminsList($conn) {
    return $conn->query("SELECT id, username FROM admins ORDER BY username")->fetch_all(MYSQLI_ASSOC);
}





function getDashboardStats($conn) {
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

function getLogIcon($action) {
    if (strpos($action, 'login') !== false) return 'fa-sign-in-alt';
    if (strpos($action, 'logout') !== false) return 'fa-sign-out-alt';
    if (strpos($action, 'add') !== false) return 'fa-plus';
    if (strpos($action, 'edit') !== false) return 'fa-edit';
    if (strpos($action, 'delete') !== false) return 'fa-trash';
    return 'fa-user';
}





function clearFaqCache() {
    global $cache;
    $cache->deleteByPrefix("faq_");
    $cache->deleteByPrefix("admin_faq_");
}


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

function addFaq($conn, $data) {
    $stmt = $conn->prepare("INSERT INTO faq (question, answer, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $data['question'], $data['answer'], $data['category'], $data['sort_order'], $data['is_active']);
    $res = $stmt->execute();
    if ($res) clearFaqCache();
    return $res;
}

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

function deleteFaq($conn, $id) {
    $stmt = $conn->prepare("DELETE FROM faq WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    if ($res) clearFaqCache();
    return $res;
}





function getAllContentBlocks($conn) {
    global $cache;
    $cacheKey = "admin_content_blocks_all";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $result = $conn->query("SELECT * FROM content_blocks ORDER BY block_key");
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

function getContentBlockById($conn, $id) {
    global $cache;
    $cacheKey = "content_block_id_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM content_blocks WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    if ($data) {
        $cache->set($cacheKey, $data);
    }
    return $data;
}

function updateContentBlock($conn, $id, $title, $content) {
    global $cache;
    
    $stmt = $conn->prepare("UPDATE content_blocks SET title = ?, content = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $title, $content, $id);
    $res = $stmt->execute();
    
    if ($res) {
        $cache->delete("content_block_id_" . $id);
        
        $cache->deleteByPrefix("content_block_");
        $cache->deleteByPrefix("admin_content_blocks_");
    }
    
    return $res;
}

function getContentPreview($text, $limit = 100) {
    $text = htmlspecialchars($text);
    if (function_exists('mb_substr')) {
        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '...' : $text;
    }
    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}






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
function addContact($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO contacts (contact_type, title, value, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", 
        $data['contact_type'], $data['title'], $data['value'], 
        $data['icon'], $data['sort_order'], $data['is_active']
    );
    
    $result = $stmt->execute();
    if ($result) {
        $cache->deleteByPrefix("contacts_list");
        $cache->deleteByPrefix("contacts_type");
    }
    return $result;
}

function updateContact($conn, $id, $data) {
    global $cache;
    $stmt = $conn->prepare("UPDATE contacts SET contact_type = ?, title = ?, value = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssssiii", 
        $data['contact_type'], $data['title'], $data['value'], 
        $data['icon'], $data['sort_order'], $data['is_active'], $id
    );
    
    $result = $stmt->execute();
    if ($result) {
        $cache->delete("contact_item_" . $id);
        $cache->deleteByPrefix("contacts_list");
        $cache->deleteByPrefix("contacts_type");
    }
    return $result;
}
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

function addCategory($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO product_categories (name, slug, description, image_path, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", $data['name'], $data['slug'], $data['description'], $data['image_path'], $data['sort_order'], $data['is_active']);
    
    $success = $stmt->execute();
    if ($success) {
        $cache->deleteByPrefix("categories_");
    }
    return $success;
}

function updateCategory($conn, $id, $data) {
    global $cache;

    $oldImagePath = null;
    $sel = $conn->prepare("SELECT image_path FROM product_categories WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $oldImagePath = $row['image_path'];
    }
    $sel->close();

    $stmt = $conn->prepare("UPDATE product_categories SET name = ?, slug = ?, description = ?, image_path = ?, sort_order = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssssiii", 
        $data['name'], 
        $data['slug'], 
        $data['description'], 
        $data['image_path'], 
        $data['sort_order'], 
        $data['is_active'], 
        $id
    );
    
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        if (!empty($oldImagePath) && $oldImagePath !== $data['image_path']) {
            deleteImageFromServer($oldImagePath);
        }

        $cache->deleteByPrefix("categories_");
        $cache->deleteByPrefix("product_");
        $cache->deleteByPrefix("admin_product_");
    }

    return $success;
}

function getCategoryProductCount($conn, $categoryId) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'] ?? 0;
}

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

function deleteCategory($conn, $id) {
    global $cache;

    $imagePath = null;
    $sel = $conn->prepare("SELECT image_path FROM product_categories WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $imagePath = $row['image_path'];
    }
    $sel->close();

    $stmt = $conn->prepare("DELETE FROM product_categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        if (!empty($imagePath)) {
            deleteImageFromServer($imagePath);
        }

        $cache->deleteByPrefix("categories_");
        $cache->deleteByPrefix("product_");
    }

    return $success;
}



function getArticlesList($conn, $perPage, $offset) {
    global $cache;
    $cacheKey = "articles_list_p{$perPage}_o{$offset}";
    
    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM articles ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

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
        $cache->deleteByPrefix("articles_list");
        $cache->deleteByPrefix("article_slug");

    }
    return $success;
}

function updateArticle($conn, $id, $data) {
    global $cache;

    $oldImagePath = null;
    $sel = $conn->prepare("SELECT image_path FROM articles WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $oldImagePath = $row['image_path'];
    }
    $sel->close();

    $stmt = $conn->prepare("UPDATE articles SET title = ?, slug = ?, excerpt = ?, content = ?, author = ?, image_path = ?, is_published = ?, published_at = ?, updated_at = NOW() WHERE id = ?");
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
    
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        if (!empty($oldImagePath) && $oldImagePath !== $data['image_path']) {
            deleteImageFromServer($oldImagePath);
        }

        $cache->delete("article_item_" . $id);
        $cache->deleteByPrefix("articles_list");
        $cache->deleteByPrefix("article_slug");
    }

    return $success;
}

function deleteArticle($conn, $id) {
    global $cache;

    $imagePath = null;
    $sel = $conn->prepare("SELECT image_path FROM articles WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $imagePath = $row['image_path'];
    }
    $sel->close();

    $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    
    if ($success) {
        if (!empty($imagePath)) {
            deleteImageFromServer($imagePath);
        }

        $cache->delete("article_slug_" . $id);
        $cache->delete("article_item_" . $id);
        $cache->deleteByPrefix("articles_list");
    }

    return $success;
}

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




function getAllAdmins($conn) {
    global $cache;
    $cacheKey = "admins_all";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $result = $conn->query("SELECT * FROM admins ORDER BY role, username");
    $data = $result->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

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

function isAdminUnique($conn, $username, $email, $excludeId = 0) {
    $stmt = $conn->prepare("SELECT id FROM admins WHERE (username = ? OR email = ?) AND id != ?");
    $stmt->bind_param("ssi", $username, $email, $excludeId);
    $stmt->execute();
    return $stmt->get_result()->num_rows === 0;
}

function addAdmin($conn, $data) {
    global $cache;
    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admins (username, email, password_hash, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $data['username'], $data['email'], $passwordHash, $data['full_name'], $data['role'], $data['is_active']);
    
    $success = $stmt->execute();
    if ($success) {
        $cache->deleteByPrefix("admins_");
    }
    return $success;
}

function updateAdmin($conn, $id, $data) {
    global $cache;
    
    if (!empty($data['password'])) {
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, password_hash = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssssii", $data['username'], $data['email'], $passwordHash, $data['full_name'], $data['role'], $data['is_active'], $id);
    } else {
        $stmt = $conn->prepare("UPDATE admins SET username = ?, email = ?, full_name = ?, role = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $data['username'], $data['email'], $data['full_name'], $data['role'], $data['is_active'], $id);
    }
    
    $success = $stmt->execute();
    if ($success) {
        $cache->delete("admin_item_" . $id);
        $cache->deleteByPrefix("admins_");
    }
    return $success;
}

function deleteAdmin($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $success = $stmt->execute();
    if ($success) {
        $cache->delete("admin_item_" . $id);
        $cache->deleteByPrefix("admins_");
    }
    return $success;
}






function getAdminFeaturesList($conn, $limit, $offset) {
    global $cache;
    $cacheKey = "admin_features_list_l{$limit}_o{$offset}";

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM features ORDER BY sort_order ASC, id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

function getFeatureById($conn, $id) {
    global $cache;
    $cacheKey = "feature_item_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM features WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) $cache->set($cacheKey, $data);
    return $data;
}

function addFeature($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO features (title, description, sort_order, is_active) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $data['title'], $data['description'], $data['sort_order'], $data['is_active']);
    
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("features_active_");
        $cache->deleteByPrefix("admin_features_");
    }
    return $res;
}

function updateFeature($conn, $id, $data) {
    global $cache;
    $stmt = $conn->prepare("UPDATE features SET title = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?");
    
    $stmt->bind_param("ssiii", 
        $data['title'], 
        $data['description'], 
        $data['sort_order'], 
        $data['is_active'], 
        $id
    );
    
    $res = $stmt->execute();
    if ($res) {
        $cache->delete("feature_item_" . $id);
        $cache->deleteByPrefix("features_active_");
        $cache->deleteByPrefix("admin_features_");
    }
    return $res;
}

function deleteFeature($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM features WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    $res = $stmt->execute();
    if ($res) {
        $cache->delete("feature_item_" . $id);
        $cache->deleteByPrefix("features_active_");
        $cache->deleteByPrefix("admin_features_");
    }
    return $res;
}







function getAdminAdvantagesList($conn, $limit, $offset) {
    global $cache;
    $cacheKey = "admin_advantages_list_l{$limit}_o{$offset}";
    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM advantages ORDER BY sort_order ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $cache->set($cacheKey, $data);
    return $data;
}

function addAdvantage($conn, $data) {
    global $cache;
    $sql = "INSERT INTO advantages (title, description, icon_path, sort_order, is_active) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssii', $data['title'], $data['description'], $data['icon_path'], $data['sort_order'], $data['is_active']);
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("advantages_active_");
        $cache->deleteByPrefix("admin_advantages_");
        $cache->deleteByPrefix("advantage_item_");
    }
    return $res;
}

function updateAdvantage($conn, $id, $data) {
    global $cache;

    $oldIconPath = null;
    $sel = $conn->prepare("SELECT icon_path FROM advantages WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $oldIconPath = $row['icon_path'];
    }
    $sel->close();

    $sql = "UPDATE advantages SET title = ?, description = ?, icon_path = ?, sort_order = ?, is_active = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssiii', 
        $data['title'], 
        $data['description'], 
        $data['icon_path'], 
        $data['sort_order'], 
        $data['is_active'], 
        $id
    );
    
    $res = $stmt->execute();
    $stmt->close();

    if ($res) {
        if (!empty($oldIconPath) && $oldIconPath !== $data['icon_path']) {
            deleteImageFromServer($oldIconPath);
        }

        $cache->deleteByPrefix("advantages_active_");
        $cache->deleteByPrefix("admin_advantages_");
        $cache->deleteByPrefix("advantage_item_");
    }

    return $res;
}
function getAdvantageById($conn, $id) {
    global $cache;
    $id = intval($id);
    $cacheKey = "advantage_item_" . $id;

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT id, title, description, icon_path, sort_order, is_active FROM advantages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) {
        $cache->set($cacheKey, $data);
    }
    return $data;
}

function deleteAdvantage($conn, $id) {
    global $cache;

    $iconPath = null;
    $sel = $conn->prepare("SELECT icon_path FROM advantages WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $iconPath = $row['icon_path'];
    }
    $sel->close();

    $stmt = $conn->prepare("DELETE FROM advantages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    $stmt->close();

    if ($res) {
        if (!empty($iconPath)) {
            deleteImageFromServer($iconPath);
        }

        $cache->deleteByPrefix("advantages_active_");
        $cache->deleteByPrefix("admin_advantages_");
        $cache->deleteByPrefix("advantage_item_");
    }

    return $res;
}

function getAdvantagesList($conn, $limit = 100, $offset = 0) {
    global $cache;
    $cacheKey = "admin_advantages_list_l{$limit}_o{$offset}";
    
    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $sql = "SELECT * FROM advantages ORDER BY sort_order ASC, id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}



function getAdminOfficesList($conn) {
    global $cache;
    $cacheKey = "admin_offices_all";
    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $result = $conn->query("SELECT * FROM offices ORDER BY is_main DESC, sort_order ASC");
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $cache->set($cacheKey, $data);
    return $data;
}

function addOffice($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO offices (city, address, phone, email, work_hours, latitude, longitude, is_main, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssddii", 
        $data['city'], $data['address'], $data['phone'], $data['email'], 
        $data['work_hours'], $data['latitude'], $data['longitude'], 
        $data['is_main'], $data['sort_order']
    );
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("office");
        $cache->deleteByPrefix("admin_offices_");
        $cache->deleteByPrefix("office_item_");
    }
    return $res;
}

function updateOffice($conn, $id, $data) {
    global $cache;
    $stmt = $conn->prepare("UPDATE offices SET city=?, address=?, phone=?, email=?, work_hours=?, latitude=?, longitude=?, is_main=?, sort_order=? WHERE id=?");
    $stmt->bind_param("sssssddiii", 
        $data['city'], $data['address'], $data['phone'], $data['email'], 
        $data['work_hours'], $data['latitude'], $data['longitude'], 
        $data['is_main'], $data['sort_order'], $id
    );
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("office");
        $cache->deleteByPrefix("admin_offices_");
        $cache->deleteByPrefix("office_item_");
    }
    return $res;
}


function getOfficeById($conn, $id) {
    global $cache;
    $id = intval($id);
    $cacheKey = "office_item_" . $id;

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM offices WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) {
        $cache->set($cacheKey, $data);
    }
    return $data;
}

function deleteOffice($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM offices WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("office");
        $cache->deleteByPrefix("admin_offices_");
        $cache->deleteByPrefix("office_item_");
    }
    return $res;
}




function getStatsList($conn, $perPage, $offset) {
    global $cache;
    $cacheKey = "admin_stats_list_p" . $perPage . "_o" . $offset;

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM statistics ORDER BY sort_order ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

function getStatById($conn, $id) {
    global $cache;
    $cacheKey = "stat_id_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM statistics WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) $cache->set($cacheKey, $data);
    return $data;
}

function addStat($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO statistics (title, value, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", 
        $data['title'], 
        $data['value'], 
        $data['description'], 
        $data['sort_order'], 
        $data['is_active']
    );
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("stat_"); 
        $cache->deleteByPrefix("admin_stats_"); 
        $cache->deleteByPrefix("statistics_active_all");
    }
    return $res;
}

function updateStat($conn, $id, $data) {
    global $cache;
    $stmt = $conn->prepare("UPDATE statistics SET title = ?, value = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("sssiii", 
        $data['title'], 
        $data['value'], 
        $data['description'], 
        $data['sort_order'], 
        $data['is_active'],
        $id
    );
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("stat_"); 
        $cache->deleteByPrefix("admin_stats_");
        $cache->deleteByPrefix("statistics_active_all");
        $cache->delete("stat_id_" . $id);
    }
    return $res;
}

function deleteStat($conn, $id) {
    global $cache;
    $stmt = $conn->prepare("DELETE FROM statistics WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("stat_"); 
        $cache->deleteByPrefix("admin_stats_");
        $cache->deleteByPrefix("statistics_active_all");
        $cache->delete("stat_id_" . $id);
    }
    return $res;
}





function getCardsList($conn, $perPage, $offset) {
    global $cache;
    $cacheKey = "admin_cards_list_p" . $perPage . "_o" . $offset;

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM cards ORDER BY sort_order ASC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $perPage, $offset);
    $stmt->execute();
    
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $cache->set($cacheKey, $data);
    return $data;
}

function getCardById($conn, $id) {
    global $cache;
    $cacheKey = "card_id_" . intval($id);

    $cached = $cache->get($cacheKey);
    if ($cached !== null) return $cached;

    $stmt = $conn->prepare("SELECT * FROM cards WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();

    if ($data) $cache->set($cacheKey, $data);
    return $data;
}

function addCard($conn, $data) {
    global $cache;
    $stmt = $conn->prepare("INSERT INTO cards (title, description, image_path, color, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssii", 
        $data['title'], 
        $data['description'], 
        $data['image_path'], 
        $data['color'],
        $data['sort_order'], 
        $data['is_active']
    );
    $res = $stmt->execute();
    if ($res) {
        $cache->deleteByPrefix("card_"); 
        $cache->deleteByPrefix("admin_cards_"); 
        $cache->deleteByPrefix("cards_active_all"); 
    }
    return $res;
}

function updateCard($conn, $id, $data) {
    global $cache;

    $oldPath = null;
    $sel = $conn->prepare("SELECT image_path FROM cards WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $oldPath = $row['image_path'];
    }
    $sel->close();

    $stmt = $conn->prepare("UPDATE cards SET title = ?, description = ?, image_path = ?, color = ?, sort_order = ?, is_active = ? WHERE id = ?");
    $stmt->bind_param("ssssiii", 
        $data['title'], 
        $data['description'], 
        $data['image_path'], 
        $data['color'],
        $data['sort_order'], 
        $data['is_active'],
        $id
    );
    
    $res = $stmt->execute();
    $stmt->close();

    if ($res) {
        if (!empty($oldPath) && $oldPath !== $data['image_path']) {
            deleteImageFromServer($oldPath);
        }

        $cache->deleteByPrefix("card_"); 
        $cache->deleteByPrefix("cards_active_all"); 
        $cache->deleteByPrefix("admin_cards_");
        $cache->delete("card_id_" . $id);
    }
    
    return $res;
}

function deleteCard($conn, $id) {
    global $cache;

    $imagePath = null;
    $sel = $conn->prepare("SELECT image_path FROM cards WHERE id = ?");
    $sel->bind_param("i", $id);
    $sel->execute();
    $result = $sel->get_result();
    if ($row = $result->fetch_assoc()) {
        $imagePath = $row['image_path'];
    }
    $sel->close();

    $stmt = $conn->prepare("DELETE FROM cards WHERE id = ?");
    $stmt->bind_param("i", $id);
    $res = $stmt->execute();
    $stmt->close();

    if ($res) {
        if (!empty($imagePath)) {
            deleteImageFromServer($imagePath);
        }

        $cache->deleteByPrefix("card_"); 
        $cache->deleteByPrefix("cards_active_all"); 
        $cache->deleteByPrefix("admin_cards_");
        $cache->delete("card_id_" . $id);
    }
    
    return $res;
}
?>