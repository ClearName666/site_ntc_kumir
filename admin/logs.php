<?php
// Определяем базовый путь
define('BASE_PATH', dirname(__DIR__));

// Подключаем функции
require_once BASE_PATH . '/admin/includes/functions.php';

// Проверяем авторизацию
requireAdminAuth();

// Проверяем права доступа
if (!hasPermission('admin')) {
    redirectWithNotification('index.php', 'Недостаточно прав для доступа к этой странице', 'error');
}

$conn = getDBConnection();

// Обработка очистки логов
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $days = intval($_POST['days'] ?? 0);
    
    if ($days > 0) {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $stmt = $conn->prepare("DELETE FROM admin_logs WHERE created_at < ?");
        $stmt->bind_param("s", $date);
        
        if ($stmt->execute()) {
            logAdminAction('logs_clear', "Очищены логи старше $days дней");
            redirectWithNotification('logs.php', 'Логи успешно очищены', 'success');
        } else {
            redirectWithNotification('logs.php', 'Ошибка при очистке логов', 'error');
        }
    }
}

// Фильтрация логов
$filter = [];
$where = "1=1";
$params = [];
$types = "";

if (!empty($_GET['admin_id'])) {
    $filter['admin_id'] = intval($_GET['admin_id']);
    $where .= " AND al.admin_id = ?";
    $params[] = $filter['admin_id'];
    $types .= "i";
}

if (!empty($_GET['action'])) {
    $filter['action'] = cleanInput($_GET['action']);
    $where .= " AND al.action LIKE ?";
    $params[] = "%" . $filter['action'] . "%";
    $types .= "s";
}

if (!empty($_GET['date_from'])) {
    $filter['date_from'] = $_GET['date_from'];
    $where .= " AND DATE(al.created_at) >= ?";
    $params[] = $filter['date_from'];
    $types .= "s";
}

if (!empty($_GET['date_to'])) {
    $filter['date_to'] = $_GET['date_to'];
    $where .= " AND DATE(al.created_at) <= ?";
    $params[] = $filter['date_to'];
    $types .= "s";
}

// Получаем список администраторов для фильтра
$admins = $conn->query("SELECT id, username FROM admins ORDER BY username")->fetch_all(MYSQLI_ASSOC);

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
            <h1 class="header-title">Логи действий</h1>
        </div>
        
        <?php 
            // Подключаем правую шапку
            require_once BASE_PATH . '/admin/includes/header-right.php';
        ?>
    </header>
    
    <!-- Контент -->
    <div class="content-container">
        <!-- Фильтры -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-filter"></i> Фильтры</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="" class="row">
                    <div class="form-group col-md-3">
                        <label for="admin_id">Администратор</label>
                        <select id="admin_id" name="admin_id" class="form-control">
                            <option value="">Все администраторы</option>
                            <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>" <?php echo ($filter['admin_id'] ?? '') == $admin['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($admin['username']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-3">
                        <label for="action">Действие</label>
                        <input type="text" id="action" name="action" class="form-control" 
                               value="<?php echo htmlspecialchars($filter['action'] ?? ''); ?>" placeholder="Например: login">
                    </div>
                    
                    <div class="form-group col-md-2">
                        <label for="date_from">Дата с</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               value="<?php echo htmlspecialchars($filter['date_from'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group col-md-2">
                        <label for="date_to">Дата по</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               value="<?php echo htmlspecialchars($filter['date_to'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Применить
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Логи -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3>Журнал действий</h3>
                    <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#clearLogsModal">
                        <i class="fas fa-broom"></i> Очистить логи
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Время</th>
                                <th>Администратор</th>
                                <th>Действие</th>
                                <th>Описание</th>
                                <th>IP-адрес</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Подготовка запроса с фильтрами
                            $query = "SELECT al.*, a.username 
                                     FROM admin_logs al 
                                     LEFT JOIN admins a ON al.admin_id = a.id 
                                     WHERE $where 
                                     ORDER BY al.created_at DESC 
                                     LIMIT 100";
                            
                            if (!empty($params)) {
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param($types, ...$params);
                                $stmt->execute();
                                $result = $stmt->get_result();
                            } else {
                                $result = $conn->query($query);
                            }
                            
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <?php echo date('d.m.Y', strtotime($row['created_at'])); ?><br>
                                    <small class="text-muted"><?php echo date('H:i:s', strtotime($row['created_at'])); ?></small>
                                </td>
                                <td>
                                    <?php if ($row['username']): ?>
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    <?php else: ?>
                                    <span class="text-muted">Система</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-action badge-<?php echo $row['action']; ?>">
                                        <?php echo htmlspecialchars($row['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['description'] ?? '—'); ?></td>
                                <td>
                                    <code><?php echo htmlspecialchars($row['ip_address']); ?></code>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно очистки логов -->
<div class="modal fade" id="clearLogsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Очистка логов</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="days">Удалить логи старше (дней)</label>
                        <select id="days" name="days" class="form-control" required>
                            <option value="7">7 дней</option>
                            <option value="30">30 дней</option>
                            <option value="90">90 дней</option>
                            <option value="180">180 дней</option>
                            <option value="365">365 дней</option>
                        </select>
                        <small class="text-muted">Все логи старше указанного периода будут удалены безвозвратно.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" name="clear_logs" class="btn btn-danger">Очистить логи</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Подключаем скрипты
require_once BASE_PATH . '/admin/includes/scripts.php';
?>

<script>
// Инициализация модального окна
if (window.location.hash === '#clear-logs') {
    $('#clearLogsModal').modal('show');
}

// Подтверждение очистки
$('form').on('submit', function(e) {
    if ($(this).find('[name="clear_logs"]').length) {
        if (!confirm('Вы уверены, что хотите очистить логи? Это действие нельзя отменить.')) {
            e.preventDefault();
        }
    }
});
</script>

<?php
// Подключаем подвал
require_once BASE_PATH . '/admin/includes/footer.php';
?>