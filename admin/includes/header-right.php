<?php 

    // подключаемся к базе 
    // $conn = getDBConnection();

    // Получаем информацию о текущем администраторе
    $admin = getCurrentAdmin($conn);
?>


<div class="header-right">
    <div class="user-menu">
        <div class="user-avatar">
            <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
        </div>
        <div class="user-info">
            <h4><?php echo htmlspecialchars($admin['full_name'] ?? $admin['username']); ?></h4>
            <span><?php echo $admin['role'] === 'superadmin' ? 'Супер-администратор' : 'Администратор'; ?></span>
        </div>
        <div class="user-dropdown">
            <a href="profile.php" class="dropdown-item">
                <i class="fas fa-user"></i>
                <span>Профиль</span>
            </a>
            <a href="settings.php" class="dropdown-item">
                <i class="fas fa-cog"></i>
                <span>Настройки</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="dropdown-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Выйти</span>
            </a>
        </div>
    </div>
</div>