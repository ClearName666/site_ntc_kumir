/**
 * Мобильное меню - адаптивная навигация
 * Обеспечивает работу выезжающего меню на смартфонах
 */

document.addEventListener('DOMContentLoaded', function() {
    // Создаем элементы если они еще не созданы
    initMobileMenu();
});

function initMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    
    // Проверяем, не на мобильном ли мы устройстве
    const isMobile = window.innerWidth <= 768;
    
    // Проверяем, есть ли уже оверлей
    let overlay = document.querySelector('.menu-overlay');
    let closeBtn = document.querySelector('.menu-close-btn');
    let toggleBtn = document.querySelector('.mobile-menu-toggle');
    
    // Создаем оверлей если его нет
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'menu-overlay';
        document.body.appendChild(overlay);
    }
    
    // Создаем кнопку закрытия если её нет
    if (!closeBtn && sidebar) {
        closeBtn = document.createElement('button');
        closeBtn.className = 'menu-close-btn';
        closeBtn.innerHTML = '<i class="fas fa-times"></i>';
        closeBtn.setAttribute('aria-label', 'Закрыть меню');
        sidebar.insertBefore(closeBtn, sidebar.firstChild);
    }
    
    // Создаем кнопку-гамбургер если её нет
    if (!toggleBtn) {
        toggleBtn = document.createElement('button');
        toggleBtn.className = 'mobile-menu-toggle toggle-sidebar';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        toggleBtn.setAttribute('aria-label', 'Открыть меню');
        
        // Добавляем кнопку в левую часть шапки
        const headerLeft = document.querySelector('.header-left');
        if (headerLeft) {
            const existingToggle = headerLeft.querySelector('.toggle-sidebar');
            if (existingToggle) {
                // Вставляем после существующей кнопки
                existingToggle.insertAdjacentElement('afterend', toggleBtn);
            } else {
                headerLeft.insertBefore(toggleBtn, headerLeft.firstChild);
            }
        }
    }
    
    // Функция открытия меню
    function openMenu() {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden'; // Блокируем прокрутку фона
    }
    
    // Функция закрытия меню
    function closeMenu() {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = ''; // Восстанавливаем прокрутку
    }
    
    // Обработчики событий
    if (toggleBtn && isMobile) {
        toggleBtn.addEventListener('click', openMenu);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeMenu);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeMenu);
    }
    
    // Закрываем меню при нажатии на Esc
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeMenu();
        }
    });
    
    // Обновляем состояние при ресайзе
    window.addEventListener('resize', function() {
        const mobile = window.innerWidth <= 768;
        
        if (!mobile) {
            // На десктопе закрываем меню и убираем блокировку
            closeMenu();
        }
    });
}