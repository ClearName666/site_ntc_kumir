<!-- Основной контент -->
<div class="main-content">
    <!-- Шапка -->
    <header class="header">
        <div class="header-left">
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="header-title">Дашборд</h1>
        </div>
        
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
    </header>
    
    <!-- Контент -->
    <div class="content-container">
        <!-- Приветствие -->
        <section class="welcome-section">
            <div class="welcome-header">
                <div class="welcome-text">
                    <h1>Добро пожаловать, <?php echo htmlspecialchars($admin['username']); ?>!</h1>
                    <p>Управляйте контентом, товарами и настройками вашего сайта</p>
                </div>
                <div class="welcome-icon">
                    <i class="fas fa-crown"></i>
                </div>
            </div>
        </section>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-title">Статьи</div>
                </div>
                <div class="stat-value"><?php echo $stats['articles']; ?></div>
                <div class="stat-change">+2 за неделю</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="stat-title">Новости</div>
                </div>
                <div class="stat-value"><?php echo $stats['news']; ?></div>
                <div class="stat-change">+5 за месяц</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-title">Товары</div>
                </div>
                <div class="stat-value"><?php echo $stats['products']; ?></div>
                <div class="stat-change">+3 новых</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <div class="stat-title">Вопросы</div>
                </div>
                <div class="stat-value"><?php echo $stats['faq']; ?></div>
                <div class="stat-change">Все активно</div>
            </div>
        </div>
        
        <!-- Быстрые действия -->
        <section class="quick-actions">
            <h2 class="section-title">
                <i class="fas fa-bolt"></i>
                Быстрые действия
            </h2>
            
            <div class="actions-grid">
                <a href="articles.php?action=add" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="action-text">
                        <h4>Добавить статью</h4>
                        <p>Создать новую статью</p>
                    </div>
                </a>
                
                <a href="news.php?action=add" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="action-text">
                        <h4>Добавить новость</h4>
                        <p>Опубликовать новость</p>
                    </div>
                </a>
                
                <a href="products.php?action=add" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="action-text">
                        <h4>Добавить товар</h4>
                        <p>Добавить новый товар</p>
                    </div>
                </a>
                
                <a href="settings.php" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="action-text">
                        <h4>Настройки сайта</h4>
                        <p>Изменить настройки</p>
                    </div>
                </a>
            </div>
        </section>
        
        <!-- Последние действия -->
        <section class="recent-activity">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Последние действия
            </h2>
            
            <div class="activity-list">
                <?php if (!empty($recentLogs)): ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <?php echo htmlspecialchars($log['username'] ?? 'Система'); ?>
                                </div>
                                <div class="activity-desc">
                                    <?php echo htmlspecialchars($log['description'] ?? $log['action']); ?>
                                </div>
                            </div>
                            <div class="activity-time">
                                <?php echo date('H:i', strtotime($log['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="activity-item">
                        <div class="activity-content">
                            <div class="activity-desc">Нет последних действий</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>