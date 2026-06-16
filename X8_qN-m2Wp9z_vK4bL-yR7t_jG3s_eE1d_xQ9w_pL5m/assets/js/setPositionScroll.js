
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    
    const savedScrollPosition = sessionStorage.getItem('sidebarScrollPosition');
    if (savedScrollPosition && sidebar) {
        sidebar.scrollTop = parseInt(savedScrollPosition);
    }
    
    window.addEventListener('beforeunload', function() {
        if (sidebar) {
            sessionStorage.setItem('sidebarScrollPosition', sidebar.scrollTop);
        }
    });
});