<?php
// admin/colors.php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../Cache.php';

$conn = getDBConnection();
requireAdminAuth($conn);

// ==================== НАСТРАИВАЕМЫЙ МАССИВ ФАЙЛОВ ====================
$cssFiles = [
    'Основной стиль'     => __DIR__ . '/../assets/css/style.css',
    'Шапка'              => __DIR__ . '/../assets/css/header.css',
    'Адаптивность'       => __DIR__ . '/../assets/css/responsive.css',
    'Новости'            => __DIR__ . '/../assets/css/news.css',
    'Статьи'             => __DIR__ . '/../assets/css/articles.css',
    'Контакты'           => __DIR__ . '/../assets/css/contacts.css',
    'FAQ'                => __DIR__ . '/../assets/css/faq.css',
    'Товары'             => __DIR__ . '/../assets/css/products.css',
    'Логин админа'       => __DIR__ . '/../assets/css/loginAdmin.css',
];
// ====================================================================

// Директории
$defaultsDir = __DIR__ . '/../css_defaults/';
$backupsDir  = __DIR__ . '/../backups/';
if (!is_dir($defaultsDir)) mkdir($defaultsDir, 0755, true);
if (!is_dir($backupsDir))  mkdir($backupsDir,  0755, true);

/**
 * Парсит :root переменные из CSS-файла
 */
function parseRootVariables($filePath) {
    if (!file_exists($filePath)) return [];
    $content = file_get_contents($filePath);
    if (preg_match('/:root\s*\{([^}]+)\}/s', $content, $matches)) {
        $block = $matches[1];
        $lines = explode("\n", $block);
        $vars = [];
        foreach ($lines as $line) {
            if (preg_match('/--([^:]+):\s*([^;]+);/', $line, $match)) {
                $varName = trim($match[1]);
                $varValue = trim($match[2]);
                $vars[$varName] = $varValue;
            }
        }
        return $vars;
    }
    return [];
}

/**
 * Обновляет :root блок в CSS-файле
 */
function updateRootVariables($filePath, $newVars) {
    if (!file_exists($filePath)) return false;
    $content = file_get_contents($filePath);
    $newBlock = ":root {\n";
    foreach ($newVars as $name => $value) {
        $newBlock .= "    --{$name}: {$value};\n";
    }
    $newBlock .= "}";
    $pattern = '/:root\s*\{[^}]+\}/s';
    $newContent = preg_replace($pattern, $newBlock, $content);
    if ($newContent === null) return false;
    return file_put_contents($filePath, $newContent) !== false;
}

/**
 * Загружает дефолтные переменные для конкретного файла
 */
function getDefaultVars($fileKey, $defaultsDir, $currentVars) {
    $defaultFile = $defaultsDir . md5($fileKey) . '.json';
    if (file_exists($defaultFile)) {
        $default = json_decode(file_get_contents($defaultFile), true);
        if (is_array($default)) return $default;
    }
    file_put_contents($defaultFile, json_encode($currentVars, JSON_PRETTY_PRINT));
    return $currentVars;
}

/**
 * Создаёт резервную копию всех CSS-файлов (ZIP)
 */
function createBackup($cssFiles, $backupsDir) {
    $date = date('Y-m-d_H-i-s');
    $zipName = "backup_{$date}.zip";
    $zipPath = $backupsDir . $zipName;
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        return false;
    }
    foreach ($cssFiles as $name => $path) {
        if (file_exists($path)) {
            $zip->addFile($path, basename($path));
        }
    }
    $zip->close();
    return $zipName;
}

/**
 * Получает список доступных бэкапов
 */
function getBackupsList($backupsDir) {
    $files = glob($backupsDir . 'backup_*.zip');
    rsort($files); // новее сверху
    return $files;
}

/**
 * Восстанавливает из выбранного ZIP-архива
 */
function restoreBackup($backupFile, $cssFiles) {
    $zip = new ZipArchive();
    if ($zip->open($backupFile) !== true) return false;
    $restored = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        foreach ($cssFiles as $destFile) {
            if (basename($destFile) === $filename) {
                copy("zip://{$backupFile}#{$filename}", $destFile);
                $restored++;
                break;
            }
        }
    }
    $zip->close();
    return $restored > 0;
}

// Загружаем текущие переменные для всех файлов
$allVars = [];
$allDefaults = [];
foreach ($cssFiles as $key => $file) {
    $curr = parseRootVariables($file);
    $allVars[$key] = $curr;
    $allDefaults[$key] = getDefaultVars($key, $defaultsDir, $curr);
}

// Обработка POST
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Ошибка безопасности (CSRF).';
    } elseif (isset($_POST['create_backup'])) {
        $backupName = createBackup($cssFiles, $backupsDir);
        if ($backupName) {
            $message = "Резервная копия создана: {$backupName}";
        } else {
            $error = 'Не удалось создать резервную копию. Проверьте права на запись в папку backups/.';
        }
    } elseif (isset($_POST['restore_backup']) && isset($_POST['backup_file'])) {
        $backupFile = $_POST['backup_file'];
        if (file_exists($backupFile) && restoreBackup($backupFile, $cssFiles)) {
            $message = "Восстановление из бэкапа выполнено успешно.";
            // Перезагружаем переменные
            foreach ($cssFiles as $key => $file) {
                $allVars[$key] = parseRootVariables($file);
            }
        } else {
            $error = 'Не удалось восстановить из выбранной копии.';
        }
    } elseif (isset($_POST['reset_all_defaults'])) {
        // Сброс всех файлов к их дефолтам
        $allOk = true;
        foreach ($cssFiles as $key => $file) {
            $def = $allDefaults[$key];
            if (!updateRootVariables($file, $def)) $allOk = false;
        }
        if ($allOk) {
            $message = "Все файлы сброшены к заводским настройкам.";
            foreach ($cssFiles as $key => $file) {
                $allVars[$key] = parseRootVariables($file);
            }
        } else {
            $error = "Ошибка при сбросе некоторых файлов.";
        }
    } elseif (isset($_POST['save_all'])) {
        // Сохраняем изменения для всех файлов
        $allOk = true;
        foreach ($cssFiles as $key => $file) {
            $newVars = [];
            $oldVars = $allVars[$key];
            foreach ($oldVars as $varName => $oldValue) {
                $postKey = "var_{$key}_{$varName}";
                $newVars[$varName] = trim($_POST[$postKey] ?? $oldValue);
            }
            if (!updateRootVariables($file, $newVars)) {
                $allOk = false;
            } else {
                $allVars[$key] = $newVars;
            }
        }
        if ($allOk) {
            $message = "Все изменения сохранены.";
        } else {
            $error = "Не удалось сохранить некоторые файлы. Проверьте права на запись.";
        }
    }
}

// CSRF токен
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Подключение шапки и меню
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/menu.php';
?>
<link rel="stylesheet" href="assets/css/color.css">
<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar"><i class="fas fa-bars"></i></button>
            <h1 class="header-title">Настройка цветов и CSS-переменных</h1>
        </div>
        <?php require_once __DIR__ . '/includes/header-right.php'; ?>
    </header>

    <div class="content-container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Блок с кнопками управления (без табов) -->
        <div class="action-bar">
            <form method="POST" style="display: inline-block;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" name="create_backup" class="btn btn-secondary">
                    <i class="fas fa-database"></i> Создать резервную копию
                </button>
            </form>
            <form method="POST" style="display: inline-block;" id="restoreForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <select name="backup_file" class="backup-select">
                    <option value="">-- Выберите бэкап --</option>
                    <?php foreach (getBackupsList($backupsDir) as $backup): ?>
                        <option value="<?php echo htmlspecialchars($backup); ?>">
                            <?php echo htmlspecialchars(basename($backup)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="restore_backup" class="btn btn-warning">
                    <i class="fas fa-undo-alt"></i> Восстановить из копии
                </button>
            </form>
            <form method="POST" style="display: inline-block;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" name="reset_all_defaults" class="btn btn-danger" 
                        onclick="return confirm('Сбросить ВСЕ файлы к заводским настройкам? Это необратимо.')">
                    <i class="fas fa-globe"></i> Сбросить всё к заводским
                </button>
            </form>
        </div>

        <!-- Основная форма редактирования всех файлов -->
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="save_all" value="1">

            <?php foreach ($cssFiles as $key => $file): ?>
                <div class="file-block">
                    <div class="file-block-header">
                        <h3><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($key); ?></h3>
                        <span class="file-path"><?php echo htmlspecialchars($file); ?></span>
                    </div>
                    <div class="file-block-body">
                        <?php if (empty($allVars[$key])): ?>
                            <div class="alert alert-warning">В этом файле нет переменных :root</div>
                        <?php else: ?>
                            <div class="vars-grid">
                                <?php foreach ($allVars[$key] as $varName => $value): ?>
                                    <div class="var-card">
                                        <div class="var-name">
                                            <code>--<?php echo htmlspecialchars($varName); ?></code>
                                        </div>
                                        <div class="var-control">
                                            <?php
                                            $isColor = preg_match('/^(#|rgb|rgba|hsl|hsla)/i', $value);
                                            if ($isColor):
                                            ?>
                                                <input type="color" 
                                                       name="var_<?php echo $key; ?>_<?php echo $varName; ?>" 
                                                       value="<?php echo htmlspecialchars($value); ?>"
                                                       class="color-picker">
                                                <span class="color-preview" style="background: <?php echo htmlspecialchars($value); ?>"></span>
                                            <?php else: ?>
                                                <input type="text" 
                                                       name="var_<?php echo $key; ?>_<?php echo $varName; ?>" 
                                                       value="<?php echo htmlspecialchars($value); ?>"
                                                       class="text-input">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-save-all">
                    <i class="fas fa-save"></i> Сохранить все изменения
                </button>
            </div>
        </form>

        <div class="info-card">
            <h4><i class="fas fa-info-circle"></i> Информация</h4>
            <ul>
                <li>Дефолтные настройки хранятся в <code>css_defaults/</code> – удалите файл, чтобы пересоздать из текущих значений.</li>
                <li>Резервные копии сохраняются в <code>backups/</code> в формате ZIP.</li>
                <li>При восстановлении будут заменены только те файлы, которые есть в архиве.</li>
            </ul>
        </div>
    </div>
</div>


<script>
// Небольшой скрипт, чтобы при выборе цвета обновлялся превью (если есть)
document.querySelectorAll('.color-picker').forEach(picker => {
    picker.addEventListener('input', function() {
        const preview = this.nextElementSibling;
        if (preview && preview.classList.contains('color-preview')) {
            preview.style.backgroundColor = this.value;
        }
    });
});
// Подтверждение восстановления
document.querySelector('#restoreForm')?.addEventListener('submit', function(e) {
    const select = this.querySelector('.backup-select');
    if (!select.value) {
        e.preventDefault();
        alert('Пожалуйста, выберите резервную копию.');
    } else {
        return confirm('Восстановить из выбранной копии? Текущие файлы будут перезаписаны.');
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>