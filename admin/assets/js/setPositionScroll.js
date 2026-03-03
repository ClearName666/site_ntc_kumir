
// Сохранение позиции скролла меню при обновлении страницы
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    
    // Восстанавливаем позицию скролла при загрузке
    const savedScrollPosition = sessionStorage.getItem('sidebarScrollPosition');
    if (savedScrollPosition && sidebar) {
        sidebar.scrollTop = parseInt(savedScrollPosition);
    }
    
    // Сохраняем позицию скролла перед обновлением страницы
    window.addEventListener('beforeunload', function() {
        if (sidebar) {
            sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop);
        }
    });
});