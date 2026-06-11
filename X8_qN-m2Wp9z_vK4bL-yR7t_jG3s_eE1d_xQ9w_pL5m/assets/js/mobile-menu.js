/**
 * Мобильное меню - адаптивная навигация
 * Обеспечивает работу выезжающего меню на смартфонах
 */

document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
});

function initMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    
    const isMobile = window.innerWidth <= 768;
    
    let overlay = document.querySelector('.menu-overlay');
    let closeBtn = document.querySelector('.menu-close-btn');
    let toggleBtn = document.querySelector('.mobile-menu-toggle');
    
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'menu-overlay';
        document.body.appendChild(overlay);
    }
    
    if (!closeBtn && sidebar) {
        closeBtn = document.createElement('button');
        closeBtn.className = 'menu-close-btn';
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.setAttribute('aria-label', 'Закрыть меню');
        sidebar.insertBefore(closeBtn, sidebar.firstChild);
    }
    
    if (!toggleBtn) {
        toggleBtn = document.createElement('button');
        toggleBtn.className = 'mobile-menu-toggle toggle-sidebar';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.setAttribute('aria-label', 'Открыть меню');
        
        const headerLeft = document.querySelector('.header-left');
        if (headerLeft) {
            const existingToggle = headerLeft.querySelector('.toggle-sidebar');
            if (existingToggle) {
                existingToggle.insertAdjacentElement('afterend', toggleBtn);
            } else {
                headerLeft.insertBefore(toggleBtn, headerLeft.firstChild);
            }
        }
    }
    
    function openMenu() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMenu() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    if (toggleBtn && isMobile) {
        toggleBtn.addEventListener('click', openMenu);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeMenu);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeMenu);
    }
    
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeMenu();
        }
    });
    
    window.addEventListener('resize', function() {
        const mobile = window.innerWidth <= 768;
        
        if (!mobile) {
            closeMenu();
        }
    });
}