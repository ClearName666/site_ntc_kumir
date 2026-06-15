<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = isset($_SESSION['admin_id']);

if ($isAdmin) {
	header('Location: /X8_qN-m2Wp9z_vK4bL-yR7t_jG3s_eE1d_xQ9w_pL5m/index.php');
}
