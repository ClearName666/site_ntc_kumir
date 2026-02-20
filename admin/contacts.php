<?php
require_once __DIR__. '/includes/functions.php';

$conn = getDBConnection();
requireAdminAuth($conn);

if (!hasPermission($conn, 'editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// --- ОБРАБОТКА POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'contact_type' => cleanInput($_POST['contact_type'] ?? ''),
        'title'        => cleanInput($_POST['title'] ?? ''),
        'value'        => cleanInput($_POST['value'] ?? ''),
        'icon'         => cleanInput($_POST['icon'] ?? ''),
        'sort_order'   => intval($_POST['sort_order'] ?? 0),
        'is_active'    => isset($_POST['is_active']) ? 1 : 0
    ];
    
    if ($action === 'add') {
        if (addContact($conn, $formData)) {
            logAdminAction($conn, 'contact_add', "Добавлен контакт: " . $formData['title']);
            redirectWithNotification('contacts.php', 'Контакт добавлен', 'success');
        }
    } elseif ($action === 'edit' && $id) {
        if (updateContact($conn, $id, $formData)) {
            logAdminAction($conn, 'contact_edit', "Изменен контакт: " . $formData['title']);
            redirectWithNotification('contacts.php', 'Контакт обновлен', 'success');
        }
    }
}

// --- УДАЛЕНИЕ ---
if ($action === 'delete' && $id) {
    $contact = getContactById($conn, $id);
    if (deleteContact($conn, $id)) {
        logAdminAction($conn, 'contact_delete', "Удален: " . ($contact['title'] ?? $id));
        redirectWithNotification('contacts.php', 'Контакт удален', 'success');
    }
}

// Подключаем шапку и меню...
require_once __DIR__. '/includes/header.php';
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
            <h1 class="header-title">
                <?php echo $action === 'add' ? 'Добавить контакт' : ($action === 'edit' ? 'Редактировать контакт' : 'Контакты'); ?>
            </h1>
        </div>
        
        <?php 
            // Подключаем правую шапку
            require_once __DIR__. '/includes/header-right.php';
        ?>
    </header>
    
    <!-- Контент -->
    <div class="content-container">
        <?php if ($action === 'list'): ?>
        <!-- Список контактов -->
        <div class="page-header">
            <div class="header-actions">
                <a href="contacts.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить контакт
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Список контактов</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Тип</th>
                                <th>Название</th>
                                <th>Значение</th>
                                <th>Иконка</th>
                                <th>Сортировка</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pagination = getPagination($conn, 'contacts', 10);
                            $contacts = getContactsList($conn, $pagination['perPage'], $pagination['offset']);
                            
                            foreach ($contacts as $row):
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $row['contact_type']; ?>">
                                        <?php 
                                        $typeNames = [
                                            'address' => 'Адрес',
                                            'email' => 'Email',
                                            'phone' => 'Телефон',
                                            'legal' => 'Реквизиты',
                                            'mobile' => 'Мобильный',
                                            'phone-free' => 'Бесплатный'
                                        ];
                                        echo $typeNames[$row['contact_type']] ?? $row['contact_type'];
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['title']); ?></td>
                                <td><?php echo htmlspecialchars($row['value']); ?></td>
                                <td>
                                    <?php if ($row['icon']): ?>
                                    <i class="fas fa-<?php echo $row['icon']; ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['sort_order']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $row['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="contacts.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="contacts.php?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('Удалить этот контакт?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php echo generatePaginationLinks($pagination); ?>
            </div>
        </div>
        
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Форма добавления/редактирования -->
        <?php 
        if ($action === 'edit' && $id) {
            $contact = getContactById($conn, $id);
            if (!$contact) redirectWithNotification('contacts.php', 'Не найден', 'error');
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Добавить новый контакт' : 'Редактировать контакт'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="contact_type">Тип контакта *</label>
                        <select id="contact_type" name="contact_type" required>
                            <option value="address" <?php echo ($contact['contact_type'] ?? '') === 'address' ? 'selected' : ''; ?>>Адрес</option>
                            <option value="email" <?php echo ($contact['contact_type'] ?? '') === 'email' ? 'selected' : ''; ?>>Email</option>
                            <option value="phone" <?php echo ($contact['contact_type'] ?? '') === 'phone' ? 'selected' : ''; ?>>Телефон</option>
                            <option value="legal" <?php echo ($contact['contact_type'] ?? '') === 'legal' ? 'selected' : ''; ?>>Реквизиты</option>
                            <option value="mobile" <?php echo ($contact['contact_type'] ?? '') === 'mobile' ? 'selected' : ''; ?>>Мобильный телефон</option>
                            <option value="phone-free" <?php echo ($contact['contact_type'] ?? '') === 'phone-free' ? 'selected' : ''; ?>>Бесплатный телефон</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Название *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($contact['title'] ?? ''); ?>" required>
                        <small>Например: "Офис", "Техническая поддержка" и т.д.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="value">Значение *</label>
                        <input type="text" id="value" name="value" value="<?php echo htmlspecialchars($contact['value'] ?? ''); ?>" required>
                        <small>Например: "office@ntckumir.ru", "+7 (3952) 50-48-59"</small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="icon">Иконка Font Awesome</label>
                            <div class="input-with-icon">
                                <i class="fas fa-icons"></i>
                                <input type="text" id="icon" name="icon" placeholder="location, phone, envelope" value="<?php echo htmlspecialchars($contact['icon'] ?? ''); ?>">
                            </div>
                            <small>Название иконки из Font Awesome (без префикса fa-)</small>
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label for="sort_order">Порядок сортировки</label>
                            <input type="number" id="sort_order" name="sort_order" value="<?php echo $contact['sort_order'] ?? 0; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($contact['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <span>Активный контакт</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="contacts.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Подключаем скрипты
require_once __DIR__. '/includes/scripts.php';

// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>