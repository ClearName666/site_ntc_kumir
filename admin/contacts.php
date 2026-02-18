<?php
// Определяем базовый путь
define('BASE_PATH', dirname(__DIR__));

// Подключаем функции
require_once BASE_PATH . '/admin/includes/functions.php';

// Проверяем авторизацию
requireAdminAuth();

// Проверяем права доступа
if (!hasPermission('editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

$conn = getDBConnection();
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

// Обработка добавления/редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contactType = cleanInput($_POST['contact_type'] ?? '');
    $title = cleanInput($_POST['title'] ?? '');
    $value = cleanInput($_POST['value'] ?? '');
    $icon = cleanInput($_POST['icon'] ?? '');
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if ($action === 'add' || ($action === 'edit' && $id)) {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO contacts (contact_type, title, value, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $contactType, $title, $value, $icon, $sortOrder, $isActive);
            
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                logAdminAction('contact_add', "Добавлен контакт: $title");
                redirectWithNotification('contacts.php', 'Контакт успешно добавлен', 'success');
            } else {
                redirectWithNotification('contacts.php?action=add', 'Ошибка при добавлении контакта', 'error');
            }
        } else {
            $stmt = $conn->prepare("UPDATE contacts SET contact_type = ?, title = ?, value = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssssiii", $contactType, $title, $value, $icon, $sortOrder, $isActive, $id);
            
            if ($stmt->execute()) {
                logAdminAction('contact_edit', "Отредактирован контакт: $title");
                redirectWithNotification('contacts.php', 'Контакт успешно обновлен', 'success');
            } else {
                redirectWithNotification('contacts.php?action=edit&id=' . $id, 'Ошибка при обновлении контакта', 'error');
            }
        }
    }
}

// Обработка удаления
if ($action === 'delete' && $id) {
    // Получаем информацию о контакте для лога
    $stmt = $conn->prepare("SELECT title FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $contact = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAdminAction('contact_delete', "Удален контакт: " . ($contact['title'] ?? 'ID ' . $id));
        redirectWithNotification('contacts.php', 'Контакт успешно удален', 'success');
    } else {
        redirectWithNotification('contacts.php', 'Ошибка при удалении контакта', 'error');
    }
}

// Подключаем шапку
require_once BASE_PATH . '/admin/includes/header.php';

// Подключаем меню
require_once BASE_PATH . '/admin/includes/menu.php';
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
        
        <div class="header-right">
            <div class="user-menu">
                <div class="user-avatar">
                    <?php $admin = getCurrentAdmin(); echo strtoupper(substr($admin['username'], 0, 1)); ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></h4>
                    <span>Администратор</span>
                </div>
            </div>
        </div>
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
                            $pagination = getPagination('contacts', 10);
                            $stmt = $conn->prepare("SELECT * FROM contacts ORDER BY contact_type, sort_order, title LIMIT ? OFFSET ?");
                            $stmt->bind_param("ii", $pagination['perPage'], $pagination['offset']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($row = $result->fetch_assoc()):
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
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php echo generatePaginationLinks($pagination); ?>
            </div>
        </div>
        
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <!-- Форма добавления/редактирования -->
        <?php
        $contact = [];
        if ($action === 'edit' && $id) {
            $stmt = $conn->prepare("SELECT * FROM contacts WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $contact = $result->fetch_assoc();
            
            if (!$contact) {
                redirectWithNotification('contacts.php', 'Контакт не найден', 'error');
            }
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
require_once BASE_PATH . '/admin/includes/scripts.php';

// Подключаем подвал
require_once BASE_PATH . '/admin/includes/footer.php';
?>