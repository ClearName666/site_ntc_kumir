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

// Вспомогательная функция для безопасного удаления старого файла
function deleteOldSectionImage($conn, $imagePathSettingKey) {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    if ($stmt) {
        $stmt->bind_param("s", $imagePathSettingKey);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $oldPath = $row['setting_value'];
            // Проверяем, существует ли файл, и не пустая ли строка
            if (!empty($oldPath) && file_exists(__DIR__ . '/../' . $oldPath)) {
                @unlink(__DIR__ . '/../' . $oldPath);
            }
        }
        $stmt->close();
    }
}

// ОБРАБОТКА POST НАСТРОЕК
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // СНАЧАЛА 1. Обработка всех текстовых полей из $_POST
    $_POST['setting_form_view'] = isset($_POST['setting_form_view']) && $_POST['setting_form_view'] == 1 ? 1 : 0;
    $_POST['setting_price_view'] = isset($_POST['setting_price_view']) && $_POST['setting_price_view'] == 1 ? 1 : 0;
    $_POST['setting_site_new_view'] = isset($_POST['setting_site_new_view']) && $_POST['setting_site_new_view'] == 1 ? 1 : 0; // <- ДОБАВЛЕНО
    $_POST['setting_for_whom_view'] = isset($_POST['setting_for_whom_view']) && $_POST['setting_for_whom_view'] == 1 ? 1 : 0;
    $_POST['setting_our_products_view'] = isset($_POST['setting_our_products_view']) && $_POST['setting_our_products_view'] == 1 ? 1 : 0;
    $_POST['setting_advantages_of_our_system_view'] = isset($_POST['setting_advantages_of_our_system_view']) && $_POST['setting_advantages_of_our_system_view'] == 1 ? 1 : 0;
    $_POST['setting_about_the_company_view'] = isset($_POST['setting_about_the_company_view']) && $_POST['setting_about_the_company_view'] == 1 ? 1 : 0;
    $_POST['setting_geography_of_application_view'] = isset($_POST['setting_geography_of_application_view']) && $_POST['setting_geography_of_application_view'] == 1 ? 1 : 0;
    
    // Перебираем POST и сохраняем обычные настройки
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8);
            updateOrInsertSetting($conn, $settingKey, cleanInput($value));
        }
    }

    // Если тип фона сменился на solid или gradient, удаляем старый файл, так как он больше не нужен
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0 && strpos($key, '_bg_type') !== false) {
            if ($value !== 'image') {
                $prefix = str_replace(['setting_', '_bg_type'], '', $key);
                $imagePathKey = $prefix . '_bg_image_path';
                
                // Удаляем физический файл с диска
                deleteOldSectionImage($conn, $imagePathKey);
                
                // Сбрасываем путь к картинке в базе
                updateOrInsertSetting($conn, $imagePathKey, '');
            }
        }
    }

    // ЗАТЕМ 2. Обработка стандартных изображений (Лого, Фавикон) с очисткой старых
    $imageFields = [
        'logo' => 'logo_path',
        'favicon' => 'favicon_path',
        'background' => 'background_image'
    ];

    foreach ($imageFields as $field => $settingKey) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            
            // Удаляем старый файл логотипа/фавикона перед записью нового
            deleteOldSectionImage($conn, $settingKey);

            $uploadResult = uploadImage($_FILES[$field]);
            if ($uploadResult['success']) {
                $path = $uploadResult['path'];
                $imageKey = ($field === 'background') ? 'main_background' : $field;
                updateImageSettings($conn, $imageKey, $settingKey, $path);
            }
        }
    }

    // И В САМЫЙ КОНЕЦ 3. Обработка фонового изображения из УНИВЕРСАЛЬНОГО конструктора
    if (isset($_FILES['universal_bg_file']) && $_FILES['universal_bg_file']['error'] === UPLOAD_ERR_OK) {
        $activeSection = '';
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0 && strpos($key, '_bg_type') !== false) {
                $activeSection = str_replace(['setting_', '_bg_type'], '', $key) . '_background';
                break;
            }
        }

        if (!empty($activeSection)) {
            $prefix = str_replace('_background', '', $activeSection);
            $imagePathKey = $prefix . '_bg_image_path';

            // Удаляем старую картинку секции перед загрузкой новой
            deleteOldSectionImage($conn, $imagePathKey);

            $uploadResult = uploadImage($_FILES['universal_bg_file']);
            if ($uploadResult['success']) {
                $path = $uploadResult['path'];
                
                // Сохраняем чистый путь к картинке
                updateOrInsertSetting($conn, $imagePathKey, $path);
                
                // Генерируем финальный CSS и перезаписываем то, что пришло из POST
                $cssString = "background: url('../" . $path . "') center/cover no-repeat;";
                updateOrInsertSetting($conn, $activeSection, $cssString);
            }
        }
    }

    logAdminAction($conn, 'settings_update', 'Обновлены настройки сайта');
    redirectWithNotification('settings.php', 'Настройки успешно сохранены', 'success');
}

// Получаем все настройки из базы в единый массив
$settingsResult = $conn->query("SELECT * FROM settings");
$settings = [];
while ($row = $settingsResult->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Подключаем шапку
require_once __DIR__. '/includes/header.php';

// Подключаем меню
require_once __DIR__. '/includes/menu.php';
?>

<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar" style="display: none;">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">Настройки сайта</h1>
        </div>
        <?php require_once __DIR__. '/includes/header-right.php'; ?>
    </header>
    
    <div class="content-container">
        <form method="POST" action="" enctype="multipart/form-data">
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
                    
                    <div class="form-group">
                        <label for="setting_form_view">
                            <input type="checkbox" id="setting_form_view" name="setting_form_view" value="1" 
                                <?php echo (($settings['form_view'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            Форма отображения
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="setting_price_view">
                            <input type="checkbox" id="setting_price_view" name="setting_price_view" value="1" 
                                <?php echo (($settings['price_view'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            Цены товаров отображение
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="setting_site_new_view">
                            <input type="checkbox" id="setting_site_new_view" name="setting_site_new_view" value="1" 
                                <?php echo (($settings['site_new_view'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            Новый вариант сайта
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="setting_for_whom_view">
                            <input type="checkbox" id="setting_for_whom_view" name="setting_for_whom_view" value="1" 
                                <?php echo (($settings['for_whom_view'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            Секция "Для кого" отображение
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="setting_our_products_view">
                            <input type="checkbox" id="setting_our_products_view" name="setting_our_products_view" value="1" 
                                <?php echo (($settings['our_products_view'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            Секция "Наша продукция" отображение
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="setting_advantages_of_our_system_view">
                            <input type="checkbox" id="setting_advantages_of_our_system_view" name="setting_advantages_of_our_system_view" value="1" 
                                <?php echo (($settings['advantages_of_our_system_view'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            Секция "Преимущества нашей системы" отображение
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="setting_about_the_company_view">
                            <input type="checkbox" id="setting_about_the_company_view" name="setting_about_the_company_view" value="1" 
                                <?php echo (($settings['about_the_company_view'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            Секция "О компании" отображение
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="setting_geography_of_application_view">
                            <input type="checkbox" id="setting_geography_of_application_view" name="setting_geography_of_application_view" value="1" 
                                <?php echo (($settings['geography_of_application_view'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            Секция "География применения" отображение
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3><i class="fas fa-paint-brush"></i> Конструктор фонов для секций сайта</h3>
                </div>
                <div class="card-body">
                    <input type="hidden" id="final_css_output" name="">
                    <input type="hidden" id="final_bg_type" name="">
                    <input type="hidden" id="final_color1" name="">
                    <input type="hidden" id="final_color2" name="">

                    <div class="form-group">
                        <label for="section_selector" style="font-weight: bold; color: #0055ff;">1. Выберите секцию для редактирования:</label>
                        <select id="section_selector" class="form-control" style="border: 2px solid #0055ff;">
                            <option value="hero_background">Секция "Hero" (Главный экран)</option>
                            <option value="for_whom_background">Секция "Для кого"</option>
                            <option value="our_products_background">Секция "Наша продукция"</option>
                            <option value="advantages_of_our_system_background">Секция "Преимущества системы"</option>
                            <option value="about_the_company_background">Секция "О компании"</option>
                            <option value="geography_of_application_background">Секция "География применения"</option>
                        </select>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label for="universal_bg_type">2. Тип заднего фона для выбранной секции</label>
                        <select id="universal_bg_type" class="form-control">
                            <option value="solid">Сплошной цвет</option>
                            <option value="gradient">Вертикальный градиент</option>
                            <option value="image">Фоновое изображение</option>
                        </select>
                    </div>

                    <div class="bg-options-container" style="background: rgba(0,0,0,0.03); padding: 15px; border-radius: 6px; margin-bottom: 15px;">
                        <div class="bg-option-block" id="uni_block_solid">
                            <div class="form-group mb-0">
                                <label>Выберите цвет заливки:</label>
                                <input type="color" id="uni_color_1" class="form-control" style="width: 80px; height: 40px; padding: 2px;">
                            </div>
                        </div>

                        <div class="bg-option-block" id="uni_block_gradient" style="display:none;">
                            <div class="form-row mb-0">
                                <div class="form-group col-md-3">
                                    <label>Цвет СВЕРХУ:</label>
                                    <input type="color" id="uni_grad_1" class="form-control" style="height: 40px;">
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Цвет СНИЗУ:</label>
                                    <input type="color" id="uni_grad_2" class="form-control" style="height: 40px;">
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
                                    <small class="text-muted d-block mt-1">Файл применится именно к выбранной в данный момент секции после сохранения.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-0" id="uni_preview_wrapper">
                        <label>Живой предпросмотр фона секции:</label>
                        <div id="uni_preview_box" style="width: 100%; height: 120px; border: 2px dashed #ccc; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: bold; text-shadow: 0 1px 3px rgba(255,255,255,0.8); transition: all 0.3s ease; color: #000;">
                            Предпросмотр секции
                        </div>
                    </div>
                </div>
            </div>

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
                                <small class="text-muted">200×60px, SVG/PNG</small>
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
                                <small class="text-muted">ICO, PNG</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
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

<?php require_once __DIR__. '/includes/footer.php'; ?>

<script src="assets/js/scripts.js"></script>

<script>
    window.backendBackgroundSettings = {
        hero_background: {
            css: `<?= $settings['hero_background'] ?? '' ?>`,
            type: `<?= $settings['hero_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['hero_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['hero_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['hero_bg_image_path'] ?? '' ?>`
        },
        for_whom_background: {
            css: `<?= $settings['for_whom_background'] ?? '' ?>`,
            type: `<?= $settings['for_whom_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['for_whom_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['for_whom_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['for_whom_bg_image_path'] ?? '' ?>`
        },
        our_products_background: {
            css: `<?= $settings['our_products_background'] ?? '' ?>`,
            type: `<?= $settings['our_products_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['our_products_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['our_products_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['our_products_bg_image_path'] ?? '' ?>`
        },
        advantages_of_our_system_background: {
            css: `<?= $settings['advantages_of_our_system_background'] ?? '' ?>`,
            type: `<?= $settings['advantages_of_our_system_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['advantages_of_our_system_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['advantages_of_our_system_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['advantages_of_our_system_bg_image_path'] ?? '' ?>`
        },
        about_the_company_background: {
            css: `<?= $settings['about_the_company_background'] ?? '' ?>`,
            type: `<?= $settings['about_the_company_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['about_the_company_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['about_the_company_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['about_the_company_bg_image_path'] ?? '' ?>`
        },
        geography_of_application_background: {
            css: `<?= $settings['geography_of_application_background'] ?? '' ?>`,
            type: `<?= $settings['geography_of_application_bg_type'] ?? 'solid' ?>`,
            c1: `<?= $settings['geography_of_application_bg_color1'] ?? '#ffffff' ?>`,
            c2: `<?= $settings['geography_of_application_bg_color2'] ?? '#ffffff' ?>`,
            img: `<?= $settings['geography_of_application_bg_image_path'] ?? '' ?>`
        }
    };
</script>

<script src="assets/js/castomBackrgound.js"></script>