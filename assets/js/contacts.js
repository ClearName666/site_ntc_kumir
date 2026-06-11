document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
            
    const form = this;
    const submitBtn = form.querySelector('.submit-btn');
    const originalText = submitBtn.textContent;
            
    submitBtn.textContent = 'Отправка...';
    submitBtn.disabled = true;
            
    const formData = new FormData(form);
            
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
    navigator.clipboard.writeText(text).then(function() {
        showCopyNotification(element, 'Скопировано!');
    }).catch(function(err) {
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
    
    if (getComputedStyle(element).position === 'static') {
        element.style.position = 'relative';
    }
    
    element.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 2000);
}


