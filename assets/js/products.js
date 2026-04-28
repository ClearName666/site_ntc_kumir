// Анимация появления карточек товаров
document.addEventListener('DOMContentLoaded', function() {
    // Intersection Observer для товаров
    const productObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                productObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    // Наблюдаем за карточками товаров
    document.querySelectorAll('.product-card').forEach(card => {
        productObserver.observe(card);
    });
    
    // Параллакс эффект для категорий
    const categoryCards = document.querySelectorAll('.category-card');
    categoryCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateY = (x - centerX) / 25;
            const rotateX = (centerY - y) / 25;
            
            card.style.transform = `
                perspective(1000px) 
                rotateX(${rotateX}deg) 
                rotateY(${rotateY}deg) 
                translateY(-10px)
            `;
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) translateY(0)';
        });
    });
    
    // Анимация счетчика цены (если нужно)
    const priceElements = document.querySelectorAll('.product-price-value');
    priceElements.forEach(priceElement => {
        const priceText = priceElement.textContent;
        const priceNumber = parseFloat(priceText.replace(/[^\d]/g, ''));
        
        // Можно добавить анимацию счетчика при появлении
        if (priceNumber > 0) {
            priceElement.style.opacity = '0';
            priceElement.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                priceElement.style.transition = 'opacity 0.5s, transform 0.5s';
                priceElement.style.opacity = '1';
                priceElement.style.transform = 'translateY(0)';
            }, 300);
        }
    });
    
    // Плавный скролл для якорных ссылок
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Эффект волны при клике на карточку
    document.querySelectorAll('.product-card, .category-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (!this.querySelector('a').contains(e.target)) return;
            
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(39, 174, 96, 0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                width: ${size}px;
                height: ${size}px;
                top: ${y}px;
                left: ${x}px;
                pointer-events: none;
                z-index: 1;
            `;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Добавляем стиль для ripple эффекта
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
});


document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('kpModal');
    const openBtns = document.querySelectorAll('.open-kp-modal');
    const closeBtn = document.querySelector('.close-modal');

    // Открытие окна
    openBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const productName = this.getAttribute('data-product');
            document.getElementById('modalProductName').textContent = 'Товар: ' + productName;
            document.getElementById('kpProductInput').value = productName;
            modal.style.display = 'flex';
        });
    });

    // Закрытие по крестику
    closeBtn.onclick = function() {
        modal.style.display = 'none';
    }

    // Закрытие при клике вне окна
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // 1. ЭЛЕМЕНТЫ
    const modal = document.getElementById('kpModal');
    const openBtns = document.querySelectorAll('.open-kp-modal');
    const closeBtn = document.querySelector('.close-modal');
    const kpForm = document.getElementById('kpForm');

    // 2. ЛОГИКА МОДАЛЬНОГО ОКНА
    if (modal && openBtns.length > 0) {
        openBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault(); // На всякий случай гасим переход
                const productName = this.getAttribute('data-product');
                document.getElementById('modalProductName').textContent = 'Товар: ' + productName;
                document.getElementById('kpProductInput').value = productName;
                modal.style.display = 'flex';
            });
        });

        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    }

    // 3. ОТПРАВКА ФОРМЫ ЧЕРЕЗ AJAX
    if (kpForm) {
        kpForm.addEventListener('submit', function(e) {
            // ОСТАНАВЛИВАЕМ перезагрузку страницы!
            e.preventDefault();
            e.stopPropagation();
            
            const submitBtn = this.querySelector('.submit-kp-btn');
            const originalText = submitBtn.textContent;
            const formData = new FormData(this);
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Отправка...';
            
            fetch('products.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Ошибка сети');
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    kpForm.reset();
                    modal.style.display = 'none';
                } else {
                    alert('Ошибка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Не удалось отправить запрос. Попробуйте позже.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
            
            return false; // Дополнительная страховка от перезагрузки
        });
    }

    // --- Тут можно оставить твой старый код анимаций (Observer и т.д.) ---
    const productObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                productObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.product-card').forEach(card => {
        productObserver.observe(card);
    });
});