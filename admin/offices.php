<?php
require_once __DIR__. '/includes/functions.php';

$conn = getDBConnection();
requireAdminAuth($conn);

if (!hasPermission($conn, 'editor')) {
    redirectWithNotification('index.php', 'Недостаточно прав', 'error');
}

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);

// --- ОБРАБОТКА POST (Добавление и Редактирование) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'city'       => cleanInput($_POST['city'] ?? ''),
        'address'    => cleanInput($_POST['address'] ?? ''),
        'phone'      => cleanInput($_POST['phone'] ?? ''),
        'email'      => cleanInput($_POST['email'] ?? ''),
        'work_hours' => cleanInput($_POST['work_hours'] ?? ''),
        'latitude'   => cleanInput($_POST['latitude'] ?? ''),
        'longitude'  => cleanInput($_POST['longitude'] ?? ''),
        'sort_order' => intval($_POST['sort_order'] ?? 0),
        'is_main'    => isset($_POST['is_main']) ? 1 : 0
    ];
    
    if ($action === 'add') {
        if (addOffice($conn, $formData)) {
            logAdminAction($conn, 'office_add', "Добавлен офис: " . $formData['city']);
            redirectWithNotification('offices.php', 'Офис успешно добавлен', 'success');
        }
    } elseif ($action === 'edit' && $id) {
        if (updateOffice($conn, $id, $formData)) {
            logAdminAction($conn, 'office_edit', "Изменен офис: " . $formData['city']);
            redirectWithNotification('offices.php', 'Данные офиса обновлены', 'success');
        }
    }
}

// --- УДАЛЕНИЕ ---
if ($action === 'delete' && $id) {
    $office = getOfficeById($conn, $id);
    if (deleteOffice($conn, $id)) {
        logAdminAction($conn, 'office_delete', "Удален офис: " . ($office['city'] ?? $id));
        redirectWithNotification('offices.php', 'Офис удален', 'success');
    }
}

// Подключаем шапку и меню
require_once __DIR__. '/includes/header.php';
require_once __DIR__. '/includes/menu.php';
?>

<div class="main-content">
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">
                <?php echo $action === 'add' ? 'Добавить офис' : ($action === 'edit' ? 'Редактировать офис' : 'Офисы компании'); ?>
            </h1>
        </div>
        
        <?php require_once __DIR__. '/includes/header-right.php'; ?>
    </header>
    
    <div class="content-container">
        <?php if ($action === 'list'): ?>
        <div class="page-header">
            <div class="header-actions">
                <a href="offices.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить офис
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Список подразделений</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Город</th>
                                <th>Адрес</th>
                                <th>Телефон</th>
                                <th>Статус</th>
                                <th>Сорт.</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pagination = getPagination($conn, 'offices', 10);
                            $offices = getAdminOfficesList($conn, $pagination['perPage'], $pagination['offset']);
                            
                            foreach ($offices as $row):
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['city']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['address']); ?></td>
                                <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                <td>
                                    <?php if ($row['is_main']): ?>
                                        <span class="badge badge-primary">Главный</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Филиал</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['sort_order']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="offices.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="offices.php?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-delete">
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
        <?php 
        if ($action === 'edit' && $id) {
            $office = getOfficeById($conn, $id);
            if (!$office) redirectWithNotification('offices.php', 'Офис не найден', 'error');
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Новое подразделение' : 'Правка данных офиса'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="city">Город *</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($office['city'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="phone">Телефон</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($office['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Точный адрес *</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($office['address'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="email">Email офиса</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($office['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="work_hours">Режим работы</label>
                            <input type="text" id="work_hours" name="work_hours" placeholder="Пн-Пт: 09:00 - 18:00" value="<?php echo htmlspecialchars($office['work_hours'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="latitude">Широта (Yandex/Google)</label>
                            <input type="text" id="latitude" name="latitude" value="<?php echo htmlspecialchars($office['latitude'] ?? ''); ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="longitude">Долгота</label>
                            <input type="text" id="longitude" name="longitude" value="<?php echo htmlspecialchars($office['longitude'] ?? ''); ?>">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="sort_order">Порядок</label>
                            <input type="number" id="sort_order" name="sort_order" value="<?php echo $office['sort_order'] ?? 0; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_main" value="1" <?php echo ($office['is_main'] ?? 0) ? 'checked' : ''; ?>>
                            <span>Это главный офис</span>
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="offices.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="assets/js/miniAdminstration.js"></script>

<?php
require_once __DIR__. '/includes/footer.php';
?>