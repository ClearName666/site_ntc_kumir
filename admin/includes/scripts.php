<?php
// Этот файл содержит только JavaScript код
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Переключение сайдбара
    const toggleSidebar = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    
    if (toggleSidebar && sidebar) {
        toggleSidebar.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Анимация статистики
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.1) + 's';
    });
    
    // Анимация быстрых действий
    const actionBtns = document.querySelectorAll('.action-btn');
    actionBtns.forEach((btn, index) => {
        btn.style.animationDelay = (0.5 + index * 0.1) + 's';
    });
    
    // Показ уведомлений
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideInRight 0.5s ease reverse';
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }
    
    // Проверка URL параметров для уведомлений
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('message')) {
        showNotification(decodeURIComponent(urlParams.get('message')), urlParams.get('type') || 'info');
    }
    
    // Подтверждение выхода
    const logoutLinks = document.querySelectorAll('a[href*="logout"]');
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите выйти?')) {
                e.preventDefault();
            }
        });
    });
    
    // Подтверждение удаления
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите удалить этот элемент? Это действие нельзя отменить.')) {
                e.preventDefault();
            }
        });
    });
    
    // Динамическое добавление полей
    const addButtons = document.querySelectorAll('[data-add-field]');
    addButtons.forEach(button => {
        button.addEventListener('click', function() {
            const target = this.getAttribute('data-add-field');
            const container = document.getElementById(target);
            if (container) {
                const template = container.querySelector('.field-template');
                if (template) {
                    const clone = template.cloneNode(true);
                    clone.classList.remove('field-template');
                    clone.style.display = 'block';
                    container.appendChild(clone);
                    
                    // Добавляем обработчик удаления для нового поля
                    const removeBtn = clone.querySelector('.remove-field');
                    if (removeBtn) {
                        removeBtn.addEventListener('click', function() {
                            clone.remove();
                        });
                    }
                }
            }
        });
    });
    
    // Удаление полей
    const removeButtons = document.querySelectorAll('.remove-field');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.field-row').remove();
        });
    });
    
    // Валидация форм
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--danger-color)';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Пожалуйста, заполните все обязательные поля', 'error');
            }
        });
    });
    
    // Загрузка файлов с предпросмотром
    const fileInputs = document.querySelectorAll('input[type="file"][data-preview]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const previewId = this.getAttribute('data-preview');
            const preview = document.getElementById(previewId);
            
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    
    // Сортировка таблиц
    const sortableHeaders = document.querySelectorAll('th[data-sortable]');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const column = this.getAttribute('data-sortable');
            const currentOrder = this.getAttribute('data-order') || 'asc';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            // Сбрасываем сортировку у других заголовков
            sortableHeaders.forEach(h => {
                if (h !== this) {
                    h.removeAttribute('data-order');
                    h.classList.remove('sorted-asc', 'sorted-desc');
                }
            });
            
            // Устанавливаем новую сортировку
            this.setAttribute('data-order', newOrder);
            this.classList.remove('sorted-asc', 'sorted-desc');
            this.classList.add('sorted-' + newOrder);
            
            // Добавляем иконку сортировки
            const icon = this.querySelector('.sort-icon') || document.createElement('i');
            icon.className = 'fas sort-icon ml-2';
            icon.classList.add(newOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down');
            
            if (!this.querySelector('.sort-icon')) {
                this.appendChild(icon);
            }
            
            // Здесь должна быть логика сортировки таблицы
            // (требуется реализация на сервере или JavaScript)
        });
    });
    
    // Поиск в таблицах
    const searchInputs = document.querySelectorAll('[data-search]');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const tableId = this.getAttribute('data-search');
            const table = document.getElementById(tableId);
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    });
});

// Он отвечает за визуальное отображение файла после того, как ты его перетащил
document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
    const dropZoneElement = inputElement.closest(".drop-zone");

    // Клик по зоне открывает выбор файла
    dropZoneElement.addEventListener("click", (e) => {
        inputElement.click();
    });

    inputElement.addEventListener("change", (e) => {
        if (inputElement.files.length) {
            updateThumbnail(dropZoneElement, inputElement.files[0]);
        }
    });

    dropZoneElement.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropZoneElement.classList.add("drop-zone--over");
    });

    ["dragleave", "dragend"].forEach((type) => {
        dropZoneElement.addEventListener(type, (e) => {
            dropZoneElement.classList.remove("drop-zone--over");
        });
    });

    dropZoneElement.addEventListener("drop", (e) => {
        e.preventDefault();

        if (e.dataTransfer.files.length) {
            inputElement.files = e.dataTransfer.files;
            updateThumbnail(dropZoneElement, e.dataTransfer.files[0]);
        }

        dropZoneElement.classList.remove("drop-zone--over");
    });
});

/**
 * Обновляет превью изображения в зоне
 */
function updateThumbnail(dropZoneElement, file) {
    let thumbnailElement = dropZoneElement.querySelector(".drop-zone__thumb");

    // Удаляем текст-подсказку, если она есть
    if (dropZoneElement.querySelector(".drop-zone__prompt")) {
        dropZoneElement.querySelector(".drop-zone__prompt").remove();
    }

    // Если превью еще нет (первая загрузка), создаем его
    if (!thumbnailElement) {
        thumbnailElement = document.createElement("img");
        thumbnailElement.classList.add("drop-zone__thumb");
        dropZoneElement.appendChild(thumbnailElement);
    }

    // Читаем файл и вставляем в src
    if (file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => {
            thumbnailElement.src = reader.result;
        };
    }
}

</script>