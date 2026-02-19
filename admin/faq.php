<?php

// Подключаем функции
require_once __DIR__. '/includes/functions.php';

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
    $question = cleanInput($_POST['question'] ?? '');
    $answer = $_POST['answer'] ?? '';
    $category = cleanInput($_POST['category'] ?? 'Общие вопросы');
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if ($action === 'add' || ($action === 'edit' && $id)) {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO faq (question, answer, category, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $question, $answer, $category, $sortOrder, $isActive);
            
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                logAdminAction('faq_add', "Добавлен FAQ: " . safeSubstr($question, 0, 50));
                redirectWithNotification('faq.php', 'Вопрос успешно добавлен', 'success');
            } else {
                redirectWithNotification('faq.php?action=add', 'Ошибка при добавлении вопроса', 'error');
            }
        } else {
            $stmt = $conn->prepare("UPDATE faq SET question = ?, answer = ?, category = ?, sort_order = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("sssiii", $question, $answer, $category, $sortOrder, $isActive, $id);
            
            if ($stmt->execute()) {
                logAdminAction('faq_edit', "Отредактирован FAQ: " . safeSubstr($question, 0, 50));
                redirectWithNotification('faq.php', 'Вопрос успешно обновлен', 'success');
            } else {
                redirectWithNotification('faq.php?action=edit&id=' . $id, 'Ошибка при обновлении вопроса', 'error');
            }
        }
    }
}

// Обработка удаления
if ($action === 'delete' && $id) {
    // Получаем информацию о вопросе для лога
    $stmt = $conn->prepare("SELECT question FROM faq WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $faq = $result->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM faq WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        logAdminAction('faq_delete', "Удален FAQ: " . safeSubstr($faq['question'] ?? '', 0, 50));
        redirectWithNotification('faq.php', 'Вопрос успешно удален', 'success');
    } else {
        redirectWithNotification('faq.php', 'Ошибка при удалении вопроса', 'error');
    }
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
            <h1 class="header-title">
                <?php echo $action === 'add' ? 'Добавить вопрос' : ($action === 'edit' ? 'Редактировать вопрос' : 'Вопросы и ответы'); ?>
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
        <!-- Список вопросов -->
        <div class="page-header">
            <div class="header-actions">
                <a href="faq.php?action=add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Добавить вопрос
                </a>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Список вопросов</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Вопрос</th>
                                <th>Категория</th>
                                <th>Ответ</th>
                                <th>Сортировка</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $pagination = getPagination('faq', 10);
                            $stmt = $conn->prepare("SELECT * FROM faq ORDER BY category, sort_order, id DESC LIMIT ? OFFSET ?");
                            $stmt->bind_param("ii", $pagination['perPage'], $pagination['offset']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['question']); ?></td>
                                <td>
                                    <span class="category-badge"><?php echo htmlspecialchars($row['category']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars(safeSubstr($row['answer'], 0, 50)) . '...'; ?></td>
                                <td><?php echo $row['sort_order']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $row['is_active'] ? 'Активен' : 'Неактивен'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="faq.php?action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="faq.php?action=delete&id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('Удалить этот вопрос?')">
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
        $faq = [];
        if ($action === 'edit' && $id) {
            $stmt = $conn->prepare("SELECT * FROM faq WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $faq = $result->fetch_assoc();
            
            if (!$faq) {
                redirectWithNotification('faq.php', 'Вопрос не найден', 'error');
            }
        }
        ?>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo $action === 'add' ? 'Добавить новый вопрос' : 'Редактировать вопрос'; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="category">Категория</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="Общие вопросы" <?php echo ($faq['category'] ?? '') === 'Общие вопросы' ? 'selected' : ''; ?>>Общие вопросы</option>
                            <option value="Технические вопросы" <?php echo ($faq['category'] ?? '') === 'Технические вопросы' ? 'selected' : ''; ?>>Технические вопросы</option>
                            <option value="Финансовые вопросы" <?php echo ($faq['category'] ?? '') === 'Финансовые вопросы' ? 'selected' : ''; ?>>Финансовые вопросы</option>
                            <option value="Монтаж и настройка" <?php echo ($faq['category'] ?? '') === 'Монтаж и настройка' ? 'selected' : ''; ?>>Монтаж и настройка</option>
                            <option value="Техническое обслуживание" <?php echo ($faq['category'] ?? '') === 'Техническое обслуживание' ? 'selected' : ''; ?>>Техническое обслуживание</option>
                            <option value="Обучение и поддержка" <?php echo ($faq['category'] ?? '') === 'Обучение и поддержка' ? 'selected' : ''; ?>>Обучение и поддержка</option>
                            <option value="Программное обеспечение" <?php echo ($faq['category'] ?? '') === 'Программное обеспечение' ? 'selected' : ''; ?>>Программное обеспечение</option>
                            <option value="Оборудование" <?php echo ($faq['category'] ?? '') === 'Оборудование' ? 'selected' : ''; ?>>Оборудование</option>
                            <option value="Поддержка" <?php echo ($faq['category'] ?? '') === 'Поддержка' ? 'selected' : ''; ?>>Поддержка</option>
                            <option value="Документация" <?php echo ($faq['category'] ?? '') === 'Документация' ? 'selected' : ''; ?>>Документация</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="question">Вопрос *</label>
                        <textarea id="question" name="question" rows="2" class="form-control" required><?php echo htmlspecialchars($faq['question'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="answer">Ответ *</label>
                        <textarea id="answer" name="answer" rows="6" class="form-control" required><?php echo htmlspecialchars($faq['answer'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="sort_order">Порядок сортировки</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?php echo $faq['sort_order'] ?? 0; ?>">
                        </div>
                        
                        <div class="form-group col-md-6">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="1" <?php echo ($faq['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                <span>Активный вопрос</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Сохранить
                        </button>
                        <a href="faq.php" class="btn btn-secondary">Отмена</a>
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