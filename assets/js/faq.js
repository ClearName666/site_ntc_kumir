// Обработка формы вопроса
// document.getElementById('questionForm').addEventListener('submit', function(e) {
//     e.preventDefault();
    
//     const formData = new FormData(this);
//     const submitBtn = this.querySelector('.submit-btn');
//     const originalText = submitBtn.textContent;
    
//     submitBtn.textContent = 'Отправка...';
//     submitBtn.disabled = true;
    
//     // Здесь должна быть AJAX отправка формы
//     // Для примера просто покажем сообщение
//     setTimeout(() => {
//         alert('Ваш вопрос отправлен! Мы ответим вам в ближайшее время.');
//         this.reset();
//         submitBtn.textContent = originalText;
//         submitBtn.disabled = false;
//     }, 1000);
// });

// Обработка формы вопроса
document.getElementById('questionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
    
    submitBtn.textContent = 'Отправка...';
    submitBtn.disabled = true;
    
    // Отправляем запрос на этот же файл (faq.php)
    fetch('faq.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // Пометка для PHP, что это AJAX
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            form.reset();
        } else {
            alert('Ошибка: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Произошла ошибка при связи с сервером.');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});

// Анимация FAQ элементов
document.addEventListener('DOMContentLoaded', function() {
    const faqItems = document.querySelectorAll('.faq-item');
    
    // Автоматическое открытие первого элемента
    if (faqItems.length > 0) {
        setTimeout(() => {
            const firstToggle = faqItems[0].querySelector('.faq-toggle');
            if (firstToggle) {
                firstToggle.checked = true;
            }
        }, 500);
    }
    
    // Плавное появление при скролле
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    faqItems.forEach(item => {
        item.style.animationPlayState = 'paused';
        observer.observe(item);
    });
    
    // Аккордеон - закрытие других при открытии одного
    const faqToggles = document.querySelectorAll('.faq-toggle');
    faqToggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            if (this.checked) {
                faqToggles.forEach(otherToggle => {
                    if (otherToggle !== this) {
                        otherToggle.checked = false;
                    }
                });
            }
        });
    });
    
    // Поиск по FAQ (можно добавить позже)
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Поиск по вопросам...';
    searchInput.className = 'faq-search';
    searchInput.style.cssText = `
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        margin-bottom: 2rem;
        display: none;
    `;
    
    // Можно добавить перед списком FAQ
    // document.querySelector('.faq-section').prepend(searchInput);
});