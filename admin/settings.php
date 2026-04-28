<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';

// подключаемся к базе 
$conn = getDBConnection();

// Проверяем авторизацию
requireAdminAuth($conn);



// Проверяем права доступа
if (!hasPermission($conn, 'admin')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

// Обработка сохранения настроек
// ОБРАБОТКА POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Обработка текстовых настроек
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8);
            updateOrInsertSetting($conn, $settingKey, cleanInput($value));
        }
    }

    // 2. Обработка изображений
    $imageFields = [
        'logo' => 'logo',
        'favicon' => 'favicon',
        'background' => 'main_background'
    ];

    foreach ($imageFields as $field => $imageKey) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $uploadResult = uploadImage($_FILES[$field]);
            if ($uploadResult['success']) {
                $path = $uploadResult['path'];
                $settingKey = ($field === 'background') ? 'background_image' : $field . '_path';
                
                // Используем нашу новую функцию
                updateImageSettings($conn, $imageKey, $settingKey, $path);
            }
        }
    }

    logAdminAction($conn, 'settings_update', 'Обновлены настройки сайта');
    redirectWithNotification('settings.php', 'Настройки успешно сохранены', 'success');
}

// ПОЛУЧЕНИЕ ДАННЫХ (вместо ручного цикла)
$settings = getAllSettings($conn);

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
                                <div class="drop-zone"> <!-- onclick="document.getElementById('logo-input').click()" -->
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
                                <div class="drop-zone" > <!-- onclick="document.getElementById('favicon-input').click()" -->
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
            
            <!-- Кнопки сохранения -->
            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Сохранить все настройки
                </button>
            </div>
        </form>
    </div>
</div>

<script src="assets/js/scripts.js"></script>

<?php
// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>