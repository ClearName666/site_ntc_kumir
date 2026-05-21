document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.querySelector('.for-whom-wrapper');
    const items = document.querySelectorAll('.target-item');
    
    // 1. Анимация появления при прокрутке экрана до секции (IntersectionObserver)
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Запускаем появление элементов по очереди (эффект волны)
                items.forEach((item, index) => {
                    setTimeout(() => {
                        item.classList.add('animated');
                        // Возвращаем дефолтный scale из CSS после вылета
                        item.style.transform = 'scale(1)';
                    }, index * 100); // Задержка между блоками 100мс
                });
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    if(wrapper) observer.observe(wrapper);

    // 2. Интерактивный фокус (приглушение остальных блоков при ховере)
    items.forEach(item => {
        item.addEventListener('mouseenter', () => {
            wrapper.classList.add('has-hovered');
        });
        item.addEventListener('mouseleave', () => {
            wrapper.classList.remove('has-hovered');
        });
    });
});