<?php
require_once __DIR__. '/includes/functions.php';

$conn = getDBConnection();
requireAdminAuth($conn);

if (!hasPermission($conn, 'admin')) {
    redirectWithNotification('index.php', 'Недостаточно прав', 'error');
}

// 1. Обработка очистки (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    $days = intval($_POST['days'] ?? 0);
    if ($days > 0 && clearOldLogs($conn, $days)) {
        logAdminAction($conn, 'logs_clear', "Очищены логи старше $days дней");
        redirectWithNotification('logs.php', 'Логи успешно очищены', 'success');
    }
}

// 2. Сбор фильтров из GET
$filters = [
    'admin_id'  => $_GET['admin_id'] ?? null,
    'action'    => $_GET['action'] ?? null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to'   => $_GET['date_to'] ?? null
];

// 3. Получение данных
$admins = getAdminsList($conn);
$logsResult = getAdminLogs($conn, $filters);

require_once __DIR__. '/includes/header.php';
require_once __DIR__. '/includes/menu.php';
?>

<!-- Основной контент -->
<div class="main-content">
    <!-- Шапка -->
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar" > 
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">Логи действий</h1>
        </div>
        
        <?php 
            // Подключаем правую шапку
            require_once __DIR__. '/includes/header-right.php';
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
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table responsive-table">
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
                            <?php while ($row = $logsResult->fetch_assoc()): ?>
                            <tr>
                                <td data-label="ID"><?php echo $row['id']; ?></td>
                                <td data-label="Время">
                                    <?php echo date('d.m.Y', strtotime($row['created_at'])); ?><br>
                                    <small class="text-muted"><?php echo date('H:i:s', strtotime($row['created_at'])); ?></small>
                                </td>
                                <td data-label="Администратор">
                                    <?php if ($row['username']): ?>
                                        <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">Система</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Действие">
                                    <span class="badge badge-action badge-<?php echo $row['action']; ?>">
                                        <?php echo htmlspecialchars($row['action']); ?>
                                    </span>
                                </td>
                                <td data-label="Описание"><?php echo htmlspecialchars($row['description'] ?? '—'); ?></td>
                                <td data-label="IP-адрес"><code><?php echo htmlspecialchars($row['ip_address']); ?></code></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/miniAdminstration.js"></script>

<?php
// Подключаем подвал
require_once __DIR__. '/includes/footer.php';
?>