// Анимация появления карточек новостей
document.addEventListener('DOMContentLoaded', function() {
    // Intersection Observer для плавного появления
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    // Наблюдаем за карточками новостей
    document.querySelectorAll('.news-card-stack').forEach(card => {
        observer.observe(card);
    });
    
    // Сортировка по дате (если нужно будет добавить фильтры)
    const sortNewsByDate = (order = 'desc') => {
        const grid = document.getElementById('newsGrid');
        if (!grid) return;
        
        const cards = Array.from(grid.querySelectorAll('.news-card-stack'));
        
        cards.sort((a, b) => {
            const dateA = new Date(a.dataset.date || 0);
            const dateB = new Date(b.dataset.date || 0);
            return order === 'desc' ? dateB - dateA : dateA - dateB;
        });
        
        // Переставляем карточки в новом порядке
        cards.forEach(card => grid.appendChild(card));
    };
    
    // Инициализация (опционально - можно добавить кнопки сортировки)
    // sortNewsByDate('desc');
    
    // Ленивая загрузка изображений
    if ('loading' in HTMLImageElement.prototype) {
        // Браузер поддерживает native lazy loading
        const images = document.querySelectorAll('img[loading="lazy"]');
        images.forEach(img => {
            if (img.complete) {
                img.classList.add('loaded');
            } else {
                img.addEventListener('load', function() {
                    this.classList.add('loaded');
                });
            }
        });
    }
    
    // Плавный скролл к началу при пагинации
    document.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if (!this.classList.contains('active')) {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }
        });
    });
});

