<?php
session_start();

// Подключаем общие функции
require_once BASE_PATH . '/config/database.php';

// Проверка авторизации администратора
function requireAdminAuth() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_token'])) {
        header('Location: login.php');
        exit();
    }
    
    // Проверка валидности токена
    $conn = getDBConnection();
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
function getCurrentAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Проверка прав администратора
function hasPermission($requiredRole = 'admin') {
    $admin = getCurrentAdmin();
    
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
function getPagination($table, $perPage = 10, $where = '') {
    $conn = getDBConnection();
    
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
function logAdminAction($action, $description = null, $adminId = null) {
    $conn = getDBConnection();
    
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
function isSlugUnique($table, $slug, $excludeId = null) {
    $conn = getDBConnection();
    
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
    $projectRoot = defined('BASE_PATH') ? BASE_PATH : dirname(dirname(__FILE__));
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
function getAllFeedback() {
    $conn = getDBConnection();
    // Сортируем: сначала новые (непрочитанные), затем по дате
    $result = $conn->query("SELECT * FROM feedback ORDER BY is_read ASC, created_at DESC");
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    return $items;
}

// Пометить как прочитанное
function markFeedbackAsRead($id) {
    $conn = getDBConnection();
    $id = intval($id);
    return $conn->query("UPDATE feedback SET is_read = 1 WHERE id = $id");
}

// Удалить обращение
function deleteFeedback($id) {
    $conn = getDBConnection();
    $id = intval($id);
    return $conn->query("DELETE FROM feedback WHERE id = $id");
}

/**
 * Получает все запросы на КП из базы данных
 */
function getAllProductRequests() {
    $conn = getDBConnection();
    $sql = "SELECT * FROM product_requests ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    $requests = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
    return $requests;
}

/**
 * Удаляет заявку на КП и записывает действие в лог
 */
function deleteProductRequest($id) {
    $conn = getDBConnection();
    
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
        logAdminAction('request_delete', "Удалена заявка на " . $requestData['product_name'] . " от " . $requestData['name']);
        return true;
    }
    
    return false;
}
?>