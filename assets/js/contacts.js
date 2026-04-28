document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
            
    const form = this;
    const submitBtn = form.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
            
    submitBtn.textContent = 'Отправка...';
    submitBtn.disabled = true;
            
    const formData = new FormData(form);
            
    // Отправляем запрос на ЭТОТ ЖЕ файл (contacts.php)
    fetch(window.location.href, {
        method: 'POST',
        body: formData
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
        alert('Произошла ошибка при отправке.');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
});



function copyToClipboard(text, element) {
    // Используем современный API буфера обмена
    navigator.clipboard.writeText(text).then(function() {
        // Показываем уведомление об успешном копировании
        showCopyNotification(element, 'Скопировано!');
    }).catch(function(err) {
        // Запасной вариант для старых браузеров
        fallbackCopyText(text, element);
    });
}

function fallbackCopyText(text, element) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        showCopyNotification(element, 'Скопировано!');
    } catch (err) {
        showCopyNotification(element, 'Ошибка копирования', true);
    }
    
    document.body.removeChild(textarea);
}

function showCopyNotification(element, message, isError = false) {
    // Создаем элемент уведомления
    const notification = document.createElement('div');
    notification.textContent = message;
    notification.style.cssText = `
        position: absolute;
        background: ${isError ? '#f44336' : '#4CAF50'};
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        top: -30px;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        z-index: 1000;
    `;
    
    // Добавляем относительное позиционирование для элемента
    if (getComputedStyle(element).position === 'static') {
        element.style.position = 'relative';
    }
    
    element.appendChild(notification);
    
    // Удаляем уведомление через 2 секунды
    setTimeout(() => {
        notification.remove();
    }, 2000);
}


