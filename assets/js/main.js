document.addEventListener('DOMContentLoaded', function() {
    // Мобильное меню
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileMenu.classList.toggle('active');
            this.textContent = mobileMenu.classList.contains('active') ? '✕' : '☰';
        });
    }
    
    // Закрытие меню при клике вне его
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.mobile-menu') && 
            !event.target.closest('.mobile-menu-btn')) {
            if (mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                mobileMenuBtn.textContent = '☰';
            }
        }
    });
    
    // Закрытие меню при клике на ссылку внутри него
    document.querySelectorAll('.mobile-menu a').forEach(link => {
        link.addEventListener('click', function() {
            mobileMenu.classList.remove('active');
            mobileMenuBtn.textContent = '☰';
        });
    });
    
    // Плавная прокрутка для якорных ссылок
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            if (this.getAttribute('href') !== '#') {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
    // Анимация элементов при скролле
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    // Наблюдаем за элементами
    document.querySelectorAll('.card, .advantage-item, .stat-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.5s, transform 0.5s';
        observer.observe(el);
    });
    
    // Для десктопного хедера делаем его фиксированным при скролле
    let lastScrollTop = 0;
    const header = document.querySelector('.main-header');
    
    window.addEventListener('scroll', function() {
        let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > 100) {
            header.style.position = 'fixed';
            header.style.top = '0';
            header.style.boxShadow = '0 5px 20px rgba(0,0,0,0.3)';
        } else {
            header.style.position = 'relative';
            header.style.boxShadow = 'none';
        }
        
        lastScrollTop = scrollTop;
    });
});