<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';

// Проверяем авторизацию
requireAdminAuth();

// Проверяем права доступа
if (!hasPermission('admin')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

$conn = getDBConnection();

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8); // Убираем префикс "setting_"
            $settingValue = cleanInput($value);
            
            // Проверяем, существует ли настройка
            $stmt = $conn->prepare("SELECT id FROM settings WHERE setting_key = ?");
            $stmt->bind_param("s", $settingKey);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Обновляем существующую настройку
                $updateStmt = $conn->prepare("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
                $updateStmt->bind_param("ss", $settingValue, $settingKey);
                $updateStmt->execute();
            } else {
                // Добавляем новую настройку
                $insertStmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $insertStmt->bind_param("ss", $settingKey, $settingValue);
                $insertStmt->execute();
            }
        }
    }
    

    // Обработка загрузки изображений
    $imageFields = [
        'logo' => 'logo',
        'favicon' => 'favicon',
        'background' => 'main_background' // ключ в таблице images
    ];

    foreach ($imageFields as $field => $imageKey) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES[$field]);

            if ($uploadResult['success']) {
                $path = $uploadResult['path'];

                // ---------- SETTINGS ----------
                $settingKey = $field === 'background' ? 'background_image' : $field . '_path';

                $updateStmt = $conn->prepare("
                    UPDATE settings 
                    SET setting_value = ?, updated_at = NOW() 
                    WHERE setting_key = ?
                ");
                $updateStmt->bind_param("ss", $path, $settingKey);
                $updateStmt->execute();

                // ---------- IMAGES ----------
                // Проверяем есть ли запись
                $checkStmt = $conn->prepare("SELECT id FROM images WHERE image_key = ?");
                $checkStmt->bind_param("s", $imageKey);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();

                if ($checkResult->num_rows > 0) {
                    // Обновляем существующую
                    $imgStmt = $conn->prepare("
                        UPDATE images 
                        SET image_path = ?, created_at = NOW() 
                        WHERE image_key = ?
                    ");
                    $imgStmt->bind_param("ss", $path, $imageKey);
                    $imgStmt->execute();
                } else {
                    // Вставляем новую
                    $altText = ucfirst($imageKey);

                    $insertStmt = $conn->prepare("
                        INSERT INTO images (image_key, image_path, alt_text, category, sort_order, created_at)
                        VALUES (?, ?, ?, 'content', 0, NOW())
                    ");
                    $insertStmt->bind_param("sss", $imageKey, $path, $altText);
                    $insertStmt->execute();
                }
            }
        }
    }
    
    logAdminAction('settings_update', 'Обновлены настройки сайта');
    redirectWithNotification('settings.php', 'Настройки успешно сохранены', 'success');
}

// Получаем все настройки
$settingsResult = $conn->query("SELECT * FROM settings ORDER BY setting_key");
$settings = [];
while ($row = $settingsResult->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Подключаем шапку
require_once __DIR__. '/includes/header.php';

// Подключаем меню
require_once __DIR__. '/includes/menu.php';
?>

<!-- Основной контент -->
<div class="main-content">
    <!-- Шапка -->
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">Настройки сайта</h1>
        </div>
        
        <?php 
            // Подключаем правую шапку
            require_once __DIR__. '/includes/header-right.php';
        ?>
    </header>
    
    <!-- Контент -->
    <div class="content-container">
        <form method="POST" action="" enctype="multipart/form-data">
            <!-- Основные настройки -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Основные настройки</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="setting_site_title">Название сайта</label>
                        <input type="text" id="setting_site_title" name="setting_site_title" 
                               value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="setting_company_name">Название компании</label>
                            <input type="text" id="setting_company_name" name="setting_company_name" 
                                   value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="setting_phone">Телефон</label>
                            <input type="text" id="setting_phone" name="setting_phone" 
                                   value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="setting_company_email">Email компании</label>
                            <input type="email" id="setting_company_email" name="setting_company_email" 
                                   value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="setting_company_address">Адрес компании</label>
                            <input type="text" id="setting_company_address" name="setting_company_address" 
                                   value="<?php echo htmlspecialchars($settings['company_address'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Изображения -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-images"></i> Изображения</h3>
                </div>
                <div class="card-body">
                    <div class="form-row images-container"> <div class="form-group image-field-col"> <label>Логотип</label>
                            <div class="image-upload-container">
                                <div class="drop-zone" onclick="document.getElementById('logo-input').click()">
                                    <?php if (!empty($settings['logo_path'])): ?>
                                        <img src="../<?php echo $settings['logo_path']; ?>" class="drop-zone__thumb" alt="Лого">
                                    <?php else: ?>
                                        <span class="drop-zone__prompt"><i class="fas fa-cloud-upload-alt"></i>Перетащите лого</span>
                                    <?php endif; ?>
                                    <input type="file" name="logo" id="logo-input" class="drop-zone__input" accept="image/*">
                                </div>
                                <small class="text-muted">200×60px, SVG/PNG</small>
                            </div>
                        </div>

                        <div class="form-group image-field-col">
                            <label>Favicon</label>
                            <div class="image-upload-container">
                                <div class="drop-zone" onclick="document.getElementById('favicon-input').click()">
                                    <?php if (!empty($settings['favicon_path'])): ?>
                                        <img src="../<?php echo $settings['favicon_path']; ?>" class="drop-zone__thumb" style="width: 48px; height: 48px;">
                                    <?php else: ?>
                                        <span class="drop-zone__prompt"><i class="fas fa-upload"></i>Favicon</span>
                                    <?php endif; ?>
                                    <input type="file" name="favicon" id="favicon-input" class="drop-zone__input" accept="image/*">
                                </div>
                                <small class="text-muted">ICO, PNG</small>
                            </div>
                        </div>

                        <div class="form-group image-field-col">
                            <label>Фоновое изображение</label>
                            <div class="image-upload-container">
                                <div class="drop-zone" onclick="document.getElementById('bg-input').click()">
                                    <?php if (!empty($settings['background_image'])): ?>
                                        <img src="../<?php echo $settings['background_image']; ?>" class="drop-zone__thumb" alt="Фон">
                                    <?php else: ?>
                                        <span class="drop-zone__prompt"><i class="fas fa-image"></i>Фон HD</span>
                                    <?php endif; ?>
                                    <input type="file" name="background" id="bg-input" class="drop-zone__input" accept="image/*">
                                </div>
                                <small class="text-muted">Рекомендуемый размер: HD</small>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            
            <!-- Тексты -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-file-alt"></i> Тексты</h3>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="setting_copyright_text">Текст копирайта</label>
                            <input type="text" id="setting_copyright_text" name="setting_copyright_text" 
                                   value="<?php echo htmlspecialchars($settings['copyright_text'] ?? '© 2026 Все права защищены'); ?>">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="setting_developer_text">Текст разработчика</label>
                            <input type="text" id="setting_developer_text" name="setting_developer_text" 
                                   value="<?php echo htmlspecialchars($settings['developer_text'] ?? 'Разработано в Prime Group'); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Дополнительные настройки -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-sliders-h"></i> Дополнительные настройки</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="setting_default_email">Email по умолчанию для уведомлений</label>
                        <input type="email" id="setting_default_email" name="setting_default_email" 
                               value="<?php echo htmlspecialchars($settings['default_email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="setting_items_per_page">Элементов на странице (админка)</label>
                            <input type="number" id="setting_items_per_page" name="setting_items_per_page" 
                                   value="<?php echo htmlspecialchars($settings['items_per_page'] ?? '10'); ?>" min="5" max="100">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="setting_cache_timeout">Таймаут кэша (секунды)</label>
                            <input type="number" id="setting_cache_timeout" name="setting_cache_timeout" 
                                   value="<?php echo htmlspecialchars($settings['cache_timeout'] ?? '3600'); ?>" min="0">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Кнопки сохранения -->
            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Сохранить все настройки
                </button>
                <button type="reset" class="btn btn-secondary">Сбросить</button>
            </div>
        </form>
    </div>
</div>

<?php
// Подключаем скрипты
require_once __DIR__. '/includes/scripts.php';

// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>