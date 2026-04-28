<?php
require_once 'config.php';

if (!isAuthorized()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Главная | NTC Kumir</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="main-page">
    <!-- Бургер-меню -->
    <button class="menu-toggle" id="menuToggle">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <!-- Оверлей для мобильного меню -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="app-wrapper">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="assets/logo.svg" alt="Logo" class="logo">
                <span class="app-name">Kumir</span>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                    <span>Поиск узлов</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="avatar">👤</div>
                    <span class="username">Пользователь</span>
                </div>
                <button id="logoutBtn" class="btn-logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Выход</span>
                </button>
            </div>
        </aside>

        <main class="main-content" id="mainContent">
            <header class="page-header">
                <h1>Поиск узлов</h1>
                <div class="search-wrapper">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input type="text" id="searchInput" class="search-input" placeholder="Поиск по узлам...">
                    <button id="searchBtn" class="btn btn-primary">Найти</button>
                </div>
            </header>

            <div class="content-area">
                <div id="loader" class="loader-overlay" style="display: none;">
                    <div class="loader-spinner"></div>
                </div>
                <div id="searchResults" class="nodes-grid"></div>
                <div id="emptyState" class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="11" cy="11" r="8"/>
                        <path d="M21 21l-4.35-4.35"/>
                    </svg>
                    <p>Загрузка узлов...</p>
                </div>
            </div>
        </main>
    </div>

    <div id="nodeModal" class="modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <script src="js/app.js"></script>
</body>
</html>