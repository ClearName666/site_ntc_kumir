<?php
// Запуск сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Настройки API
define('API_BASE_URL', 'https://v4.ntckumir.ru');

// Проверка авторизации
function isAuthorized() {
    return isset($_SESSION['sessid']) && !empty($_SESSION['sessid']);
}

// Получение SESSID
function getSessId() {
    return $_SESSION['sessid'] ?? null;
}
?>