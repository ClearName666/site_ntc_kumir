<?php
require_once __DIR__ . '/includes/functions.php';

$conn = getDBConnection();

requireAdminAuth($conn);

if (!hasPermission($conn, 'admin')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

function deleteOldSectionImage($conn, $imagePathSettingKey) {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    if ($stmt) {
        $stmt->bind_param("s", $imagePathSettingKey);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $oldPath = $row['setting_value'];
            if (!empty($oldPath) && file_exists(__DIR__ . '/../' . $oldPath)) {
                @unlink(__DIR__ . '/../' . $oldPath);
            }
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Сброс всех стилей (опционально)
    if (isset($_POST['reset_all_styles'])) {
        $styleKeys = [
            'hero_background', 'hero_bg_type', 'hero_bg_color1', 'hero_bg_color2', 'hero_bg_image_path', 'hero_text_color',
            'for_whom_background', 'for_whom_bg_type', 'for_whom_bg_color1', 'for_whom_bg_color2', 'for_whom_bg_image_path', 'for_whom_text_color',
            'our_products_background', 'our_products_bg_type', 'our_products_bg_color1', 'our_products_bg_color2', 'our_products_bg_image_path', 'our_products_text_color',
            'advantages_of_our_system_background', 'advantages_of_our_system_bg_type', 'advantages_of_our_system_bg_color1', 'advantages_of_our_system_bg_color2', 'advantages_of_our_system_bg_image_path', 'advantages_of_our_system_text_color',
            'about_the_company_background', 'about_the_company_bg_type', 'about_the_company_bg_color1', 'about_the_company_bg_color2', 'about_the_company_bg_image_path', 'about_the_company_text_color',
            'geography_of_application_background', 'geography_of_application_bg_type', 'geography_of_application_bg_color1', 'geography_of_application_bg_color2', 'geography_of_application_bg_image_path', 'geography_of_application_text_color',
            'news_artcles_background', 'news_artcles_bg_type', 'news_artcles_bg_color1', 'news_artcles_bg_color2', 'news_artcles_bg_image_path', 'news_artcles_text_color'
        ];
        foreach ($styleKeys as $key) {
            $stmt = $conn->prepare("DELETE FROM settings WHERE setting_key = ?");
            $stmt->bind_param("s", $key);
            $stmt->execute();
            $stmt->close();
        }
        logAdminAction($conn, 'settings_reset', 'Сброшены все стили секций');
        redirectWithNotification('settings.php', 'Все стили сброшены до стандартных', 'success');
    }

    // --- Чекбоксы отображения секций ---
    $checkboxes = [
        'setting_form_view', 'setting_price_view', 'setting_site_new_view',
        'setting_for_whom_view', 'setting_our_products_view', 'setting_advantages_of_our_system_view',
        'setting_about_the_company_view', 'setting_geography_of_application_view',
        'setting_news_artcles_view', 'setting_office_view'
    ];
    foreach ($checkboxes as $cb) {
        $_POST[$cb] = isset($_POST[$cb]) && $_POST[$cb] == 1 ? 1 : 0;
        $key = substr($cb, 8); // убираем 'setting_'
        updateOrInsertSetting($conn, $key, $_POST[$cb]);
    }

    // --- Обычные текстовые поля (site_title, phone, email и т.д.) ---
    $textSettings = [
        'site_title', 'company_name', 'phone', 'company_email', 'company_address',
        'video_id', 'copyright_text', 'developer_text'
    ];
    foreach ($textSettings as $tKey) {
        if (isset($_POST['setting_' . $tKey])) {
            updateOrInsertSetting($conn, $tKey, cleanInput($_POST['setting_' . $tKey]));
        }
    }

    // --- ЦВЕТ ТЕКСТА для секций (приходит из нового блока) ---
    // $textColorFields = [
    //     'hero_text_color',
    //     'for_whom_text_color',
    //     'our_products_text_color',
    //     'advantages_of_our_system_text_color',
    //     'about_the_company_text_color',
    //     'geography_of_application_text_color',
    //     'news_artcles_text_color'
    // ];
    // foreach ($textColorFields as $field) {
    //     if (isset($_POST[$field])) {
    //         updateOrInsertSetting($conn, $field, cleanInput($_POST[$field]));
    //     }
    // }

    // --- НАСТРОЙКИ ФОНА из универсального конструктора ---
    if (isset($_POST['active_section']) && !empty($_POST['active_section'])) {
        $section = $_POST['active_section'];               // например 'hero_background'
        $prefix = str_replace('_background', '', $section); // 'hero'
        
        $bgType = $_POST['universal_bg_type'] ?? 'solid';
        updateOrInsertSetting($conn, $prefix . '_bg_type', $bgType);
        
        if ($bgType === 'solid') {
            $color1 = $_POST['uni_color_1'] ?? '#ffffff';
            updateOrInsertSetting($conn, $prefix . '_bg_color1', $color1);
            // Удаляем градиентный цвет2, если был
            updateOrInsertSetting($conn, $prefix . '_bg_color2', '');
            $css = "background: $color1;";
            updateOrInsertSetting($conn, $section, $css);
        } elseif ($bgType === 'gradient') {
            $color1 = $_POST['uni_grad_1'] ?? '#ffffff';
            $color2 = $_POST['uni_grad_2'] ?? '#ffffff';
            updateOrInsertSetting($conn, $prefix . '_bg_color1', $color1);
            updateOrInsertSetting($conn, $prefix . '_bg_color2', $color2);
            $css = "background: linear-gradient(to bottom, $color1, $color2);";
            updateOrInsertSetting($conn, $section, $css);
        } elseif ($bgType === 'image') {
            // Для image тип сохраняется, но CSS будет сформирован после загрузки файла
            updateOrInsertSetting($conn, $prefix . '_bg_type', 'image');
            // Если нет загруженного файла, оставляем старую картинку (ничего не делаем)
        }
        
        // // Сохраняем цвет текста из конструктора (если передан)
        // if (isset($_POST['uni_text_color'])) {
        //     updateOrInsertSetting($conn, $prefix . '_text_color', cleanInput($_POST['uni_text_color']));
        // }
    }

    // --- Загрузка логотипа, фавиконки, фонового изображения (общие) ---
    $imageFields = [
        'logo' => 'logo_path',
        'favicon' => 'favicon_path',
        'background' => 'background_image'
    ];
    foreach ($imageFields as $field => $settingKey) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            deleteOldSectionImage($conn, $settingKey);
            $uploadResult = uploadImage($_FILES[$field]);
            if ($uploadResult['success']) {
                $path = $uploadResult['path'];
                $imageKey = ($field === 'background') ? 'main_background' : $field;
                updateImageSettings($conn, $imageKey, $settingKey, $path);
            }
        }
    }

    // --- Загрузка изображения для фона секции (universal_bg_file) ---
    if (isset($_FILES['universal_bg_file']) && $_FILES['universal_bg_file']['error'] === UPLOAD_ERR_OK) {
        $activeSection = $_POST['active_section'] ?? '';
        if (!empty($activeSection)) {
            $prefix = str_replace('_background', '', $activeSection);
            $imagePathKey = $prefix . '_bg_image_path';
            deleteOldSectionImage($conn, $imagePathKey);
            $uploadResult = uploadImage($_FILES['universal_bg_file']);
            if ($uploadResult['success']) {
                $path = $uploadResult['path'];
                updateOrInsertSetting($conn, $imagePathKey, $path);
                $cssString = "background: url('../" . $path . "') center/cover no-repeat;";
                updateOrInsertSetting($conn, $activeSection, $cssString);
                updateOrInsertSetting($conn, $prefix . '_bg_type', 'image');
            }
        }
    }

    logAdminAction($conn, 'settings_update', 'Обновлены настройки сайта');
    redirectWithNotification('settings.php', 'Настройки успешно сохранены', 'success');
}

// --- Загрузка текущих настроек из БД ---
$settingsResult = $conn->query("SELECT * FROM settings");
$settings = [];
while ($row = $settingsResult->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/menu.php';
?>

<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar" style="display: none;">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">Настройки сайта</h1>
        </div>
        <?php require_once __DIR__ . '/includes/header-right.php'; ?>
    </header>
    
    <div class="content-container">
        <form method="POST" action="" enctype="multipart/form-data" id="settingsForm">
            <!-- ========== ОСНОВНЫЕ НАСТРОЙКИ ========== -->
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
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="setting_video_id">ID Rutub видео о компании</label>
                            <input type="text" id="setting_video_id" name="setting_video_id" 
                                   value="<?php echo htmlspecialchars($settings['video_id'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Чекбоксы отображения -->
                    <div class="form-group">
                        <label><input type="checkbox" name="setting_form_view" value="1" <?php echo (($settings['form_view'] ?? 0) == 1) ? 'checked' : ''; ?>> Форма отображения</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="setting_price_view" value="1" <?php echo (($settings['price_view'] ?? 0) == 1) ? 'checked' : ''; ?>> Цены товаров отображение</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="setting_site_new_view" value="1" <?php echo (($settings['site_new_view'] ?? 0) == 1) ? 'checked' : ''; ?>> Новый вариант сайта</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="setting_for_whom_view" value="1" <?php echo (($settings['for_whom_view'] ?? 0) == 1) ? 'checked' : ''; ?>> Секция "Для кого" отображение</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="setting_our_products_view" value="1" <?php echo (($settings['our_products_view'] ?? 0) == 1) ? 'checked' : ''; ?>> Секция "Наша продукция" отображение</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="setting_advantages_of_our_system_view" value="1" <?php echo (($settings['advantages_of_our_system_view'] ?? 0) == 1) ? 'checked' : ''; ?>> Секция "Преимущества нашей системы" отображение</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="setting_about_the_company_view" value="1" <?php echo (($settings['about_the_company_view'] ?? 0) == 1) ? 'checked' : ''; ?>> Секция "О компании" отображение</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="setting_geography_of_application_view" value="1" <?php echo (($settings['geography_of_application_view'] ?? 0) == 1) ? 'checked' : ''; ?>> Секция "География применения" отображение</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="setting_news_artcles_view" value="1" <?php echo (($settings['news_artcles_view'] ?? 0) == 1) ? 'checked' : ''; ?>> Секция "Статьи и новости"</label>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="setting_office_view" value="1" <?php echo (($settings['office_view'] ?? 0) == 1) ? 'checked' : ''; ?>> Секция "Наши офисы"</label>
                    </div>
                </div>
            </div>
            
            <!-- ========== КОНСТРУКТОР ФОНОВ И ТЕКСТА ========== -->
            <div class="card mt-4">
                <div class="card-body">
                    <!-- Скрытое поле для активной секции -->
                    <input type="hidden" name="active_section" id="active_section" value="hero_background">
                    
                    <div class="form-group">
                        <label for="section_selector" style="font-weight: bold; color: #0055ff;">1. Выберите секцию:</label>
                        <select id="section_selector" class="form-control" style="border: 2px solid #0055ff;">
                            <option value="hero_background">Секция "Hero" (Главный экран)</option>
                            <option value="for_whom_background">Секция "Для кого"</option>
                            <option value="our_products_background">Секция "Наша продукция"</option>
                            <option value="advantages_of_our_system_background">Секция "Преимущества системы"</option>
                            <option value="about_the_company_background">Секция "О компании"</option>
                            <option value="geography_of_application_background">Секция "География применения"</option>
                            <option value="news_artcles_background">Секция "Статьи и новости"</option>
                        </select>
                    </div>
                    
                    <hr>
                    
                    <div class="form-group">
                        <label for="universal_bg_type">2. Тип заднего фона</label>
                        <select name="universal_bg_type" id="universal_bg_type" class="form-control">
                            <option value="solid">Сплошной цвет</option>
                            <option value="gradient">Вертикальный градиент</option>
                            <option value="image">Фоновое изображение</option>
                        </select>
                    </div>
                    
                    <div class="bg-options-container" style="background: rgba(0,0,0,0.03); padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                        <div class="bg-option-block" id="uni_block_solid">
                            <div class="form-group mb-0">
                                <label>Цвет заливки:</label>
                                <input type="color" name="uni_color_1" id="uni_color_1" class="form-control" style="width: 80px; height: 40px; padding: 2px;">
                            </div>
                        </div>
                        
                        <div class="bg-option-block" id="uni_block_gradient" style="display:none;">
                            <div class="form-row mb-0">
                                <div class="form-group col-md-3">
                                    <label>Цвет СВЕРХУ:</label>
                                    <input type="color" name="uni_grad_1" id="uni_grad_1" class="form-control" style="height: 40px;">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Цвет СНИЗУ:</label>
                                    <input type="color" name="uni_grad_2" id="uni_grad_2" class="form-control" style="height: 40px;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-option-block" id="uni_block_image" style="display:none;">
                            <div class="form-group mb-0">
                                <label>Загрузить изображение фона:</label>
                                <div class="image-upload-container">
                                    <div class="drop-zone" style="height: 160px; overflow: hidden; position: relative; border: 2px dashed #0055ff; border-radius: 6px; background: #fff;">
                                        <img src="" id="uni_image_thumb" class="drop-zone__thumb" style="display: none; width: 100%; height: 100%; object-fit: cover; position: absolute; top:0; left:0; z-index: 1;">
                                        <span class="drop-zone__prompt" id="uni_image_prompt" style="position: relative; z-index: 2; text-shadow: 0 1px 4px rgba(255,255,255,0.8); font-weight: bold; color: #333;">
                                            <i class="fas fa-cloud-upload-alt"></i> Нажмите для загрузки нового фона
                                        </span>
                                        <input type="file" name="universal_bg_file" id="uni_file_input" class="drop-zone__input" accept="image/*" style="position: absolute; top:0; left:0; width:100%; height:100%; opacity:0; z-index:3; cursor:pointer;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Блок выбора цвета текста -->
                    <!-- <div class="form-group mt-3">
                        <label for="uni_text_color">3. Цвет текста для этой секции</label>
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <input type="color" name="uni_text_color" id="uni_text_color" class="form-control" style="width: 60px; height: 60px; padding: 3px;">
                            <div>
                                <div style="font-weight: bold; margin-bottom: 5px;">Выбранный цвет: <code id="text_color_hex">#333333</code></div>
                                <small class="text-muted">Этот цвет будет применён ко всем текстовым элементам секции</small>
                            </div>
                            <button type="button" id="auto_text_color_btn" class="btn btn-outline-secondary" style="margin-left: auto;">
                                <i class="fas fa-magic"></i> Авто
                            </button>
                        </div>
                        <div id="text_preview_box" style="margin-top: 15px; padding: 15px; border: 1px solid #dee2e6; border-radius: 6px; background: #f9f9f9;">
                            <div id="text_preview_heading" style="font-size: 1.2rem; font-weight: bold;">Заголовок секции</div>
                            <div id="text_preview_text" style="margin-top: 5px;">Обычный текст параграфа для предпросмотра</div>
                            <a href="#" id="text_preview_link" style="display: block; margin-top: 5px;">Ссылка в тексте</a>
                        </div>
                    </div> -->
                    
                    <div class="form-group mb-0 mt-3">
                        <label>Предпросмотр фона:</label>
                        <div id="uni_preview_box" style="width: 100%; height: 120px; border: 2px dashed #ccc; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #000;">
                            Предпросмотр секции
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ========== ИЗОБРАЖЕНИЯ (логотип, фавикон) ========== -->
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-images"></i> Изображения</h3>
                </div>
                <div class="card-body">
                    <div class="form-row images-container"> 
                        <div class="form-group image-field-col"> 
                            <label>Логотип</label>
                            <div class="image-upload-container">
                                <div class="drop-zone">
                                    <?php if (!empty($settings['logo_path'])): ?>
                                        <img src="../<?php echo $settings['logo_path']; ?>" class="drop-zone__thumb" alt="Лого">
                                    <?php else: ?>
                                        <span class="drop-zone__prompt"><i class="fas fa-cloud-upload-alt"></i>Перетащите лого</span>
                                    <?php endif; ?>
                                    <input type="file" name="logo" id="logo-input" class="drop-zone__input" accept="image/*">
                                </div>
                            </div>
                        </div>
                        <div class="form-group image-field-col">
                            <label>Favicon</label>
                            <div class="image-upload-container">
                                <div class="drop-zone">
                                    <?php if (!empty($settings['favicon_path'])): ?>
                                        <img src="../<?php echo $settings['favicon_path']; ?>" class="drop-zone__thumb" style="width: 48px; height: 48px;">
                                    <?php else: ?>
                                        <span class="drop-zone__prompt"><i class="fas fa-upload"></i>Favicon</span>
                                    <?php endif; ?>
                                    <input type="file" name="favicon" id="favicon-input" class="drop-zone__input" accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ========== ТЕКСТЫ (копирайт и т.д.) ========== -->
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
            
            <div class="form-actions mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save"></i> Сохранить все настройки
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script src="assets/js/scripts.js"></script>

<script>
    // Текущие настройки секций из БД (передаются в JS)
    window.backendBackgroundSettings = {
        hero_background: {
            type: `<?= $settings['hero_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['hero_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['hero_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['hero_bg_image_path'] ?? '' ?>`,
            textColor: `<?= $settings['hero_text_color'] ?? '#333333' ?>`
        },
        for_whom_background: {
            type: `<?= $settings['for_whom_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['for_whom_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['for_whom_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['for_whom_bg_image_path'] ?? '' ?>`,
            textColor: `<?= $settings['for_whom_text_color'] ?? '#333333' ?>`
        },
        our_products_background: {
            type: `<?= $settings['our_products_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['our_products_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['our_products_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['our_products_bg_image_path'] ?? '' ?>`,
            textColor: `<?= $settings['our_products_text_color'] ?? '#333333' ?>`
        },
        advantages_of_our_system_background: {
            type: `<?= $settings['advantages_of_our_system_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['advantages_of_our_system_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['advantages_of_our_system_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['advantages_of_our_system_bg_image_path'] ?? '' ?>`,
            textColor: `<?= $settings['advantages_of_our_system_text_color'] ?? '#333333' ?>`
        },
        about_the_company_background: {
            type: `<?= $settings['about_the_company_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['about_the_company_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['about_the_company_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['about_the_company_bg_image_path'] ?? '' ?>`,
            textColor: `<?= $settings['about_the_company_text_color'] ?? '#333333' ?>`
        },
        geography_of_application_background: {
            type: `<?= $settings['geography_of_application_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['geography_of_application_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['geography_of_application_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['geography_of_application_bg_image_path'] ?? '' ?>`,
            textColor: `<?= $settings['geography_of_application_text_color'] ?? '#333333' ?>`
        },
        news_artcles_background: {
            type: `<?= $settings['news_artcles_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['news_artcles_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['news_artcles_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['news_artcles_bg_image_path'] ?? '' ?>`,
            textColor: `<?= $settings['news_artcles_text_color'] ?? '#333333' ?>`
        }
    };
    
    // --- Управление формой (конструктор) ---
    document.addEventListener('DOMContentLoaded', function() {
        const sectionSelect = document.getElementById('section_selector');
        const activeSectionInput = document.getElementById('active_section');
        const bgTypeSelect = document.getElementById('universal_bg_type');
        const solidBlock = document.getElementById('uni_block_solid');
        const gradientBlock = document.getElementById('uni_block_gradient');
        const imageBlock = document.getElementById('uni_block_image');
        const colorSolid = document.getElementById('uni_color_1');
        const grad1 = document.getElementById('uni_grad_1');
        const grad2 = document.getElementById('uni_grad_2');
        const textColorInput = document.getElementById('uni_text_color');
        const textColorHex = document.getElementById('text_color_hex');
        const previewBox = document.getElementById('uni_preview_box');
        
        // Функция обновления видимости блоков в зависимости от типа фона
        function updateBgBlocksVisibility() {
            const type = bgTypeSelect.value;
            solidBlock.style.display = (type === 'solid') ? 'block' : 'none';
            gradientBlock.style.display = (type === 'gradient') ? 'flex' : 'none';
            imageBlock.style.display = (type === 'image') ? 'block' : 'none';
        }
        
        // Функция обновления предпросмотра фона и цвета текста
        function updatePreview() {
            const type = bgTypeSelect.value;
            let bgStyle = '';
            if (type === 'solid') {
                bgStyle = `background: ${colorSolid.value};`;
            } else if (type === 'gradient') {
                bgStyle = `background: linear-gradient(to bottom, ${grad1.value}, ${grad2.value});`;
            } else if (type === 'image') {
                const thumb = document.getElementById('uni_image_thumb');
                if (thumb.src && thumb.style.display !== 'none') {
                    bgStyle = `background: url('${thumb.src}') center/cover no-repeat;`;
                } else {
                    // если картинка не загружена, показываем сообщение
                    bgStyle = `background: #cccccc; display: flex; align-items: center; justify-content: center;`;
                    previewBox.innerHTML = 'Предпросмотр секции<br><small>(изображение не выбрано)</small>';
                    previewBox.style.cssText = bgStyle + 'height:120px; border-radius:6px; color:#000;';
                    return;
                }
            }
            previewBox.style.cssText = bgStyle + 'height:120px; border-radius:6px; display: flex; align-items: center; justify-content: center; font-weight: bold; color: ' + textColorInput.value + ';';
            previewBox.innerHTML = 'Предпросмотр секции';
        }
        
        // Обновление цвета текста в предпросмотре
        function updateTextColorPreview() {
            const color = textColorInput.value;
            textColorHex.innerText = color;
            const previewHeading = document.getElementById('text_preview_heading');
            const previewText = document.getElementById('text_preview_text');
            const previewLink = document.getElementById('text_preview_link');
            if (previewHeading) previewHeading.style.color = color;
            if (previewText) previewText.style.color = color;
            if (previewLink) previewLink.style.color = color;
            // также обновляем цвет текста в previewBox
            // if (previewBox) previewBox.style.color = color;
        }
        
        // Загрузка настроек выбранной секции
        function loadSectionSettings() {
            const sectionKey = sectionSelect.value;
            activeSectionInput.value = sectionKey;
            const data = window.backendBackgroundSettings[sectionKey];
            if (!data) return;
            
            bgTypeSelect.value = data.type;
            updateBgBlocksVisibility();
            
            if (data.type === 'solid') {
                colorSolid.value = data.c1;
            } else if (data.type === 'gradient') {
                grad1.value = data.c1;
                grad2.value = data.c2;
            } else if (data.type === 'image') {
                if (data.img) {
                    const imgPath = '../' + data.img;
                    const thumb = document.getElementById('uni_image_thumb');
                    thumb.src = imgPath;
                    thumb.style.display = 'block';
                    document.getElementById('uni_image_prompt').style.display = 'none';
                } else {
                    document.getElementById('uni_image_thumb').style.display = 'none';
                    document.getElementById('uni_image_prompt').style.display = 'block';
                }
            }
            
            // загружаем цвет текста
            // if (data.textColor) {
            //     textColorInput.value = data.textColor;
            //     updateTextColorPreview();
            // }
            updatePreview();
        }
        
        // События
        sectionSelect.addEventListener('change', loadSectionSettings);
        bgTypeSelect.addEventListener('change', function() {
            updateBgBlocksVisibility();
            updatePreview();
        });
        colorSolid.addEventListener('input', updatePreview);
        grad1.addEventListener('input', updatePreview);
        grad2.addEventListener('input', updatePreview);
        textColorInput.addEventListener('input', function() {
            updateTextColorPreview();
            updatePreview();
        });
        
        // Обработка загрузки файла изображения
        const fileInput = document.getElementById('uni_file_input');
        const thumb = document.getElementById('uni_image_thumb');
        const prompt = document.getElementById('uni_image_prompt');
        fileInput.addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    thumb.src = ev.target.result;
                    thumb.style.display = 'block';
                    prompt.style.display = 'none';
                    updatePreview();
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Авто-подбор цвета текста (простой пример: чёрный или белый в зависимости от яркости фона)
        document.getElementById('auto_text_color_btn').addEventListener('click', function() {
            // упрощённо: инвертируем цвет основного фона (если solid)
            let bgColor = '#ffffff';
            if (bgTypeSelect.value === 'solid') {
                bgColor = colorSolid.value;
            } else if (bgTypeSelect.value === 'gradient') {
                bgColor = grad1.value; // берём верхний цвет градиента
            } else {
                // для изображения оставляем белый
                bgColor = '#ffffff';
            }
            // преобразуем hex в RGB
            let r, g, b;
            if (bgColor.startsWith('#')) {
                r = parseInt(bgColor.slice(1,3), 16);
                g = parseInt(bgColor.slice(3,5), 16);
                b = parseInt(bgColor.slice(5,7), 16);
                const brightness = (r*0.299 + g*0.587 + b*0.114);
                const textColor = (brightness > 128) ? '#000000' : '#ffffff';
                textColorInput.value = textColor;
                updateTextColorPreview();
                updatePreview();
            }
        });
        
        // Инициализация
        loadSectionSettings();
        updateBgBlocksVisibility();
        updatePreview();
        updateTextColorPreview();
    });
</script>