<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - <?php echo getSetting('site_title'); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo getSetting('favicon_path'); ?>" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Стили -->
     <style>
:root {
    --sidebar-width: 250px;
    --header-height: 70px;
    --primary-color: #3498db;
    --secondary-color: #2c3e50;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --info-color: #17a2b8;
    --light-color: #f8f9fa;
    --dark-color: #343a40;
    --border-color: #dee2e6;
    --shadow: 0 5px 15px rgba(0,0,0,0.08);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f7fa;
    color: #333;
    display: flex;
    min-height: 100vh;
}

/* Сайдбар */
.sidebar {
    width: var(--sidebar-width);
    background: var(--secondary-color);
    color: white;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    transition: var(--transition);
    z-index: 1000;
}

.sidebar-header {
    padding: 25px 20px;
    background: rgba(0,0,0,0.2);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-logo {
    display: flex;
    align-items: center;
    gap: 15px;
    text-decoration: none;
    color: white;
}

.sidebar-logo img {
    width: 40px;
    height: 40px;
}

.logo-text h2 {
    font-size: 1.25rem;
    margin-bottom: 5px;
}

.logo-text span {
    font-size: 0.75rem;
    opacity: 0.8;
}

.sidebar-menu {
    padding: 20px 0;
}

.menu-title {
    padding: 10px 20px;
    font-size: 0.75rem;
    text-transform: uppercase;
    color: rgba(255,255,255,0.5);
    letter-spacing: 1px;
    margin-bottom: 10px;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: var(--transition);
    border-left: 3px solid transparent;
}

.menu-item:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    border-left-color: var(--primary-color);
}

.menu-item.active {
    background: rgba(52, 152, 219, 0.2);
    color: white;
    border-left-color: var(--primary-color);
}

.menu-item i {
    width: 20px;
    text-align: center;
}

.menu-item span {
    flex: 1;
}

.menu-badge {
    background: var(--primary-color);
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
}

/* Основной контент */
.main-content {
    flex: 1;
    margin-left: var(--sidebar-width);
    min-height: 100vh;
}

/* Шапка */
.header {
    height: var(--header-height);
    background: white;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.toggle-sidebar {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--secondary-color);
    cursor: pointer;
    display: none;
}

.header-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--secondary-color);
}

.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    cursor: pointer;
    position: relative;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

.user-info h4 {
    font-size: 0.9375rem;
    font-weight: 600;
}

.user-info span {
    font-size: 0.8125rem;
    color: #6c757d;
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    min-width: 200px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-radius: 10px;
    overflow: hidden;
    display: none;
    z-index: 100;
}

.user-menu:hover .user-dropdown {
    display: block;
    animation: fadeIn 0.3s ease;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    color: #333;
    text-decoration: none;
    transition: var(--transition);
}

.dropdown-item:hover {
    background: var(--light-color);
}

.dropdown-item i {
    width: 20px;
    color: #6c757d;
}

/* Контейнер контента */
.content-container {
    padding: 30px;
}

/* Карточки */
.card {
    background: white;
    border-radius: 15px;
    box-shadow: var(--shadow);
    margin-bottom: 30px;
    overflow: hidden;
}

.card-header {
    padding: 25px;
    border-bottom: 1px solid var(--border-color);
    background: white;
}

.card-header h3 {
    margin: 0;
    color: var(--secondary-color);
    font-size: 1.5rem;
}

.card-body {
    padding: 25px;
}

/* Кнопки */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-success {
    background: var(--success-color);
    color: white;
}

.btn-warning {
    background: var(--warning-color);
    color: white;
}

.btn-danger {
    background: var(--danger-color);
    color: white;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 0.875rem;
}

/* Формы */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: var(--secondary-color);
    font-weight: 600;
    font-size: 0.9375rem;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group input[type="number"],
.form-group input[type="date"],
.form-group input[type="datetime-local"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -10px;
}

.form-group.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding: 0 10px;
}

/* Таблицы */
.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8f9fa;
    padding: 15px;
    text-align: left;
    font-weight: 600;
    color: var(--secondary-color);
    border-bottom: 2px solid var(--border-color);
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
    vertical-align: middle;
}

.data-table tr:hover {
    background: #f8f9fa;
}

/* Бейджи статусов */
.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.published,
.status-badge.active,
.status-badge.available {
    background: rgba(39, 174, 96, 0.1);
    color: var(--success-color);
}

.status-badge.draft,
.status-badge.inactive,
.status-badge.not-available {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

/* Пагинация */
.pagination {
    display: flex;
    gap: 5px;
    margin-top: 30px;
    justify-content: center;
}

.page-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: white;
    border: 1px solid var(--border-color);
    color: var(--secondary-color);
    text-decoration: none;
    transition: var(--transition);
}

.page-link:hover {
    background: var(--light-color);
    border-color: var(--primary-color);
}

.page-link.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Уведомления */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 25px;
    border-radius: 10px;
    color: white;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    z-index: 10000;
    animation: slideInRight 0.5s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.notification.success {
    background: var(--success-color);
}

.notification.error {
    background: var(--danger-color);
}

.notification.warning {
    background: var(--warning-color);
}

.notification.info {
    background: var(--primary-color);
}

/* Сетка контент-блоков */
.content-blocks-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.content-block-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 20px;
    transition: var(--transition);
}

.content-block-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow);
}

.block-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.block-header h4 {
    margin: 0;
    color: var(--secondary-color);
}

.block-category {
    font-size: 0.75rem;
    background: var(--light-color);
    color: #6c757d;
    padding: 3px 8px;
    border-radius: 4px;
}

.block-content {
    margin-bottom: 15px;
    color: #666;
}

/* Анимации */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Адаптивность */
@media (max-width: 992px) {
    :root {
        --sidebar-width: 70px;
    }
    
    .logo-text,
    .menu-item span,
    .menu-badge,
    .menu-title {
        display: none;
    }
    
    .sidebar-header {
        padding: 20px 15px;
        justify-content: center;
    }
    
    .sidebar-logo img {
        width: 35px;
        height: 35px;
    }
    
    .menu-item {
        justify-content: center;
        padding: 15px;
    }
    
    .main-content {
        margin-left: 70px;
    }
    
    .header-title {
        font-size: 1.25rem;
    }
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .main-content {
        margin-left: 0;
    }
    
    .toggle-sidebar {
        display: block;
    }
    
    .content-container {
        padding: 20px;
    }
    
    .form-group.col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}

@media (max-width: 480px) {
    .header {
        padding: 0 15px;
    }
    
    .content-blocks-grid {
        grid-template-columns: 1fr;
    }
    
    .pagination {
        flex-wrap: wrap;
    }
}

/* Дополнительные стили для форм */
.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    margin-bottom: 10px;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 10px;
    width: 18px;
    height: 18px;
}

.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--border-color);
}

/* Бейджи для разных типов */
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-primary {
    background: rgba(52, 152, 219, 0.1);
    color: var(--primary-color);
}

.badge-secondary {
    background: rgba(108, 117, 125, 0.1);
    color: #6c757d;
}

.badge-success {
    background: rgba(39, 174, 96, 0.1);
    color: var(--success-color);
}

.badge-warning {
    background: rgba(243, 156, 18, 0.1);
    color: var(--warning-color);
}

.badge-danger {
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger-color);
}

/* Иконки действий */
.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-edit {
    background: rgba(52, 152, 219, 0.1);
    color: var(--primary-color);
    border: none;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
}

.btn-edit:hover {
    background: var(--primary-color);
    color: white;
}

.btn-delete {
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger-color);
    border: none;
    padding: 8px;
    border-radius: 6px;
    cursor: pointer;
}

.btn-delete:hover {
    background: var(--danger-color);
    color: white;
}

/* Заголовки страниц */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h2 {
    margin: 0;
    color: var(--secondary-color);
}

/* Модальные окна */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 15px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h5 {
    margin: 0;
    color: var(--secondary-color);
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Прогресс-бар */
.progress {
    height: 10px;
    background: var(--light-color);
    border-radius: 5px;
    overflow: hidden;
    margin: 10px 0;
}

.progress-bar {
    height: 100%;
    background: var(--primary-color);
    border-radius: 5px;
    transition: width 0.3s ease;
}

/* Карточки статистики на главной */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: var(--shadow);
    transition: var(--transition);
    border-left: 4px solid var(--primary-color);
    animation: fadeIn 0.6s ease;
}

.stat-card:nth-child(2) { border-left-color: var(--success-color); }
.stat-card:nth-child(3) { border-left-color: var(--warning-color); }
.stat-card:nth-child(4) { border-left-color: var(--danger-color); }

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.15);
}

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: rgba(52, 152, 219, 0.1);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
    font-size: 1.5rem;
}

.stat-title {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 5px;
}

.stat-change {
    font-size: 0.875rem;
    color: #27ae60;
}

/* Быстрые действия */
.quick-actions {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: var(--shadow);
    margin-bottom: 30px;
}

.section-title {
    font-size: 1.5rem;
    color: var(--secondary-color);
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: var(--primary-color);
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: var(--light-color);
    border: 2px solid transparent;
    border-radius: 10px;
    text-decoration: none;
    color: var(--secondary-color);
    transition: var(--transition);
}

.action-btn:hover {
    background: white;
    border-color: var(--primary-color);
    transform: translateY(-3px);
}

.action-icon {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
}

.action-text h4 {
    font-size: 1rem;
    margin-bottom: 5px;
}

.action-text p {
    font-size: 0.8125rem;
    color: #6c757d;
}

/* Последние действия */
.recent-activity {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: var(--shadow);
}

.activity-list {
    max-height: 300px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    border-bottom: 1px solid var(--border-color);
    transition: var(--transition);
}

.activity-item:hover {
    background: var(--light-color);
}

.activity-icon {
    width: 40px;
    height: 40px;
    background: rgba(52, 152, 219, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary-color);
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.activity-desc {
    font-size: 0.875rem;
    color: #6c757d;
}

.activity-time {
    font-size: 0.75rem;
    color: #95a5a6;
}

/* Приветствие */
.welcome-section {
    background: linear-gradient(135deg, var(--primary-color), #2c3e50);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    animation: slideUp 0.6s ease;
}

.welcome-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.welcome-text h1 {
    font-size: 2rem;
    margin-bottom: 10px;
}

.welcome-text p {
    opacity: 0.9;
    font-size: 1rem;
}

.welcome-icon {
    font-size: 4rem;
    opacity: 0.2;
}

/* Роли администраторов */
.role-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.role-superadmin {
    background: rgba(231, 76, 60, 0.1);
    color: var(--danger-color);
}

.role-admin {
    background: rgba(52, 152, 219, 0.1);
    color: var(--primary-color);
}

.role-editor {
    background: rgba(39, 174, 96, 0.1);
    color: var(--success-color);
}

/* Бейджи действий в логах */
.badge-action {
    background: rgba(52, 152, 219, 0.1);
    color: var(--primary-color);
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
}


/* Область для перетаскивания */
.image-upload-container {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

/* Контейнер для всех трех полей */
.form-row.images-container {
    display: flex;
    flex-wrap: wrap;
    gap: 25px; /* Вот этот отступ между карточками */
    margin-top: 15px;
}

/* Стили для каждой колонки, чтобы они не слипались */
.image-field-col {
    flex: 1; /* Распределяем поровну */
    min-width: 250px; /* Чтобы на мобилках они прыгали друг под друга */
}

.drop-zone {
    width: 100%;
    height: 150px;
    padding: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    border: 2px dashed #ccc;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f9f9f9;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.drop-zone:hover, .drop-zone--over {
    border-color: #007bff;
    background: #f0f7ff;
}

.drop-zone__input {
    display: none;
}

.drop-zone__thumb {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.drop-zone__prompt {
    font-size: 14px;
    color: #777;
}

.drop-zone__prompt i {
    display: block;
    font-size: 30px;
    margin-bottom: 10px;
}

.drop-zone:hover {
    border-color: #4a90e2;
    transform: translateY(-2px);
    transition: all 0.2s ease;
}
     </style>
</head>
<body>