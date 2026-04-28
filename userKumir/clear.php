<?php
session_start();
session_destroy();
echo "Сессия очищена. <a href='login.php'>Войти</a>";
?>