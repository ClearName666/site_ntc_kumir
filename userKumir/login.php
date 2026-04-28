<?php
require_once 'config.php';

if (isAuthorized()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | NTC Kumir</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="logo-wrapper">
                <img src="assets/logo.svg" alt="Logo" class="logo">
            </div>
            <h1>Вход в систему</h1>
            <form id="loginForm" class="login-form">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" id="login" placeholder="Введите логин" required>
                </div>
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" id="password" placeholder="Введите пароль" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <span class="btn-text">Войти</span>
                    <span class="btn-loader"></span>
                </button>
                <div class="error-message" id="errorMessage"></div>
            </form>
        </div>
    </div>
    <script src="js/app.js"></script>
</body>
</html>