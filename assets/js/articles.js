document.addEventListener('DOMContentLoaded', function() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.article-card-stack').forEach(card => {
        observer.observe(card);
    });
    
    if (document.querySelector('.article-body')) {
        const paragraphs = document.querySelectorAll('.article-body p');
        paragraphs.forEach((p, index) => {
            p.style.animationDelay = (index * 0.1) + 's';
        });
    }
});


