document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.querySelector('.for-whom-wrapper');
    const items = document.querySelectorAll('.target-item');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                items.forEach((item, index) => {
                    setTimeout(() => {
                        item.classList.add('animated');
                        item.style.transform = 'scale(1)';
                    }, index * 100);
                });
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    if(wrapper) observer.observe(wrapper);

    items.forEach(item => {
        item.addEventListener('mouseenter', () => {
            wrapper.classList.add('has-hovered');
        });
        item.addEventListener('mouseleave', () => {
            wrapper.classList.remove('has-hovered');
        });
    });
});