<?php
require_once 'config.php';

if (isset($_SESSION['sessid'])) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_BASE_URL . '/logout.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, 'SESSID=' . $_SESSION['sessid']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

session_destroy();
header('Location: login.php');
exit;
?>