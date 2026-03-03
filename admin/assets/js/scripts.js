/**
 * Prime Group Admin Panel JavaScript
 * Оптимизированная и структурированная версия
 */

(function() {
    'use strict';

    // ==================== КОНФИГУРАЦИЯ ====================
    const CONFIG = {
        animationDelay: 0.1,
        maxFileSize: 1 * 1024 * 1024, // 1MB
        allowedImageTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        notificationDuration: 3000
    };

    // ==================== УТИЛИТЫ ====================
    const Utils = {
        /**
         * Безопасное выполнение callback с обработкой ошибок
         */
        safeExecute(callback, errorMessage = 'Произошла ошибка') {
            try {
                return callback();
            } catch (error) {
                console.error(errorMessage, error);
                Notification.show(errorMessage, 'error');
                return null;
            }
        },

        /**
         * Дебаунс для оптимизации производительности
         */
        debounce(func, wait = 300) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Валидация файла изображения
         */
        validateImageFile(file) {
            const errors = [];
            
            if (!file) {
                errors.push('Файл не выбран');
                return errors;
            }

            // Проверка размера
            if (file.size > CONFIG.maxFileSize) {
                const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
                errors.push(`Файл слишком большой (${sizeMB}MB). Максимальный размер: 1MB`);
            }

            // Проверка типа
            if (!CONFIG.allowedImageTypes.includes(file.type)) {
                errors.push('Неподдерживаемый тип файла. Разрешены: JPG, PNG, GIF, WEBP, SVG');
            }

            return errors;
        }
    };

    // ==================== СИСТЕМА УВЕДОМЛЕНИЙ ====================
    const Notification = {
        container: null,

        init() {
            // Создаем контейнер для уведомлений, если его нет
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'notifications-container';
                this.container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                `;
                document.body.appendChild(this.container);
            }
        },

        show(message, type = 'info') {
            this.init();

            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            
            // Иконки для разных типов
            const icons = {
                success: 'check-circle',
                error: 'exclamation-circle',
                warning: 'exclamation-triangle',
                info: 'info-circle'
            };

            notification.innerHTML = `
                <i class="fas fa-${icons[type] || icons.info}"></i>
                <span>${this.escapeHtml(message)}</span>
                <button class="notification-close">&times;</button>
            `;

            // Стили для уведомления
            notification.style.cssText = `
                background: ${this.getBackgroundColor(type)};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                margin-bottom: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideInRight 0.3s ease;
                position: relative;
                min-width: 300px;
            `;

            // Кнопка закрытия
            const closeBtn = notification.querySelector('.notification-close');
            closeBtn.style.cssText = `
                background: none;
                border: none;
                color: white;
                font-size: 20px;
                cursor: pointer;
                padding: 0 5px;
                margin-left: auto;
                opacity: 0.8;
            `;
            closeBtn.addEventListener('click', () => this.close(notification));

            this.container.appendChild(notification);

            // Автоматическое закрытие
            setTimeout(() => this.close(notification), CONFIG.notificationDuration);
        },

        getBackgroundColor(type) {
            const colors = {
                success: '#28a745',
                error: '#dc3545',
                warning: '#ffc107',
                info: '#17a2b8'
            };
            return colors[type] || colors.info;
        },

        close(notification) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    // ==================== УПРАВЛЕНИЕ САЙДБАРОМ ====================
    const SidebarManager = {
        init() {
            const toggleBtn = document.getElementById('toggleSidebar');
            const sidebar = document.getElementById('sidebar');

            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                    // Сохраняем состояние в localStorage
                    localStorage.setItem('sidebarActive', sidebar.classList.contains('active'));
                });

                // Восстанавливаем состояние
                const wasActive = localStorage.getItem('sidebarActive') === 'true';
                if (wasActive) {
                    sidebar.classList.add('active');
                }
            }
        }
    };

    // ==================== АНИМАЦИИ ====================
    const AnimationManager = {
        init() {
            this.animateStatCards();
            this.animateActionButtons();
        },

        animateStatCards() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * CONFIG.animationDelay}s`;
            });
        },

        animateActionButtons() {
            const actionBtns = document.querySelectorAll('.action-btn');
            actionBtns.forEach((btn, index) => {
                btn.style.animationDelay = `${0.5 + index * CONFIG.animationDelay}s`;
            });
        }
    };


    // ==================== ЗАГРУЗКА ФАЙЛОВ ====================
    const FileUploadManager = {
        init() {
            this.initDropZones();
            this.initFileInputs();
        },

        initDropZones() {
            document.querySelectorAll('.drop-zone').forEach(zone => {
                const input = zone.querySelector('.drop-zone__input');
                if (!input) return;

                // Скрываем input через JS (на всякий случай)
                input.style.display = 'none';
                
                // Получаем превью (изображение или текст)
                const thumb = zone.querySelector('.drop-zone__thumb');
                const prompt = zone.querySelector('.drop-zone__prompt');

                // Обработчик клика по всей зоне
                zone.addEventListener('click', function(e) {
                    // Проверяем, не кликнули ли по самому input (хотя он скрыт)
                    if (e.target !== input) {
                        e.preventDefault();
                        e.stopPropagation();
                        input.click();
                    }
                });

                // Если есть изображение, добавляем ему отдельный обработчик
                if (thumb) {
                    thumb.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        input.click();
                    });
                    
                    // Добавляем стиль указателя, чтобы было понятно, что изображение кликабельно
                    thumb.style.cursor = 'pointer';
                }

                // Если есть текст-подсказка
                if (prompt) {
                    prompt.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        input.click();
                    });
                }

                // Drag & Drop события
                zone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    zone.classList.add('drop-zone--over');
                });

                zone.addEventListener('dragleave', () => {
                    zone.classList.remove('drop-zone--over');
                });

                zone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    zone.classList.remove('drop-zone--over');
                    
                    if (e.dataTransfer.files.length) {
                        const file = e.dataTransfer.files[0];
                        this.handleFileSelect(input, file, zone);
                    }
                });

                // Изменение файла
                input.addEventListener('change', () => {
                    if (input.files.length) {
                        this.handleFileSelect(input, input.files[0], zone);
                    }
                });
            });
        },

        initFileInputs() {
            document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
                input.addEventListener('change', () => {
                    const previewId = input.dataset.preview;
                    const preview = document.getElementById(previewId);
                    
                    if (preview && input.files && input.files[0]) {
                        this.updateImagePreview(input.files[0], preview);
                    }
                });
            });
        },

        handleFileSelect(input, file, zone) {
            // Валидация
            const errors = Utils.validateImageFile(file);
            
            if (errors.length > 0) {
                errors.forEach(error => Notification.show(error, 'error'));
                input.value = ''; // Сбрасываем input
                return;
            }

            // Обновляем input.files
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            input.files = dataTransfer.files;

            // Обновляем превью
            this.updateDropZonePreview(zone, file);
            
            // Триггерим событие change
            input.dispatchEvent(new Event('change', { bubbles: true }));
        },

        updateDropZonePreview(zone, file) {
            // Удаляем текстовую подсказку, если она есть
            const prompt = zone.querySelector('.drop-zone__prompt');
            if (prompt) {
                prompt.remove();
            }

            // Ищем существующее изображение
            let thumbnail = zone.querySelector('.drop-zone__thumb');
            
            // Если изображения нет, создаем новое
            if (!thumbnail) {
                thumbnail = document.createElement('img');
                thumbnail.className = 'drop-zone__thumb';
                
                // Добавляем обработчик клика на новое изображение
                thumbnail.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const input = zone.querySelector('.drop-zone__input');
                    if (input) input.click();
                });
                
                // Вставляем изображение перед input
                const input = zone.querySelector('.drop-zone__input');
                zone.insertBefore(thumbnail, input);
            }

            // Добавляем стили
            thumbnail.style.cursor = 'pointer';
            thumbnail.style.maxWidth = '100%';
            thumbnail.style.maxHeight = '150px';
            thumbnail.style.objectFit = 'contain';

            // Загружаем превью
            this.updateImagePreview(file, thumbnail);
        },

        updateImagePreview(file, imgElement) {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                imgElement.src = e.target.result;
                imgElement.style.display = 'block';
            };
            
            reader.onerror = () => {
                Notification.show('Ошибка при чтении файла', 'error');
            };
            
            reader.readAsDataURL(file);
        }
    };

    // ==================== УПРАВЛЕНИЕ ФОРМАМИ ====================
    const FormManager = {
        init() {
            this.initFormValidation();
            this.initDynamicFields();
        },

        initFormValidation() {
            document.querySelectorAll('form[data-validate]').forEach(form => {
                form.addEventListener('submit', (e) => {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;

                    requiredFields.forEach(field => {
                        field.style.borderColor = '';
                        
                        if (!field.value.trim()) {
                            isValid = false;
                            field.style.borderColor = '#dc3545';
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        Notification.show('Пожалуйста, заполните все обязательные поля', 'error');
                    }
                });
            });
        },

        initDynamicFields() {
            // Добавление полей
            document.querySelectorAll('[data-add-field]').forEach(button => {
                button.addEventListener('click', () => {
                    const targetId = button.dataset.addField;
                    const container = document.getElementById(targetId);
                    
                    if (container) {
                        const template = container.querySelector('.field-template');
                        if (template) {
                            const clone = template.cloneNode(true);
                            clone.classList.remove('field-template');
                            clone.style.display = 'block';
                            
                            // Очищаем значения в клоне
                            clone.querySelectorAll('input, select, textarea').forEach(field => {
                                field.value = '';
                            });
                            
                            container.appendChild(clone);
                            
                            // Добавляем обработчик удаления
                            const removeBtn = clone.querySelector('.remove-field');
                            if (removeBtn) {
                                removeBtn.addEventListener('click', () => clone.remove());
                            }
                        }
                    }
                });
            });

            // Удаление полей
            document.querySelectorAll('.remove-field').forEach(button => {
                button.addEventListener('click', function() {
                    const row = this.closest('.field-row, [class*="field"]');
                    if (row) {
                        if (confirm('Удалить это поле?')) {
                            row.remove();
                        }
                    }
                });
            });
        }
    };

    // ==================== ТАБЛИЦЫ ====================
    const TableManager = {
        init() {
            this.initSortableHeaders();
            this.initTableSearch();
        },

        initSortableHeaders() {
            const headers = document.querySelectorAll('th[data-sortable]');
            
            headers.forEach(header => {
                header.style.cursor = 'pointer';
                header.addEventListener('click', () => this.sortTable(header));
            });
        },

        sortTable(header) {
            const table = header.closest('table');
            const column = header.dataset.sortable;
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Определяем порядок сортировки
            const currentOrder = header.dataset.order || 'asc';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            // Сбрасываем сортировку у других заголовков
            table.querySelectorAll('th[data-sortable]').forEach(h => {
                delete h.dataset.order;
                h.classList.remove('sorted-asc', 'sorted-desc');
                const icon = h.querySelector('.sort-icon');
                if (icon) icon.remove();
            });
            
            // Устанавливаем новую сортировку
            header.dataset.order = newOrder;
            header.classList.add(`sorted-${newOrder}`);
            
            // Добавляем иконку
            let icon = header.querySelector('.sort-icon');
            if (!icon) {
                icon = document.createElement('i');
                icon.className = 'fas sort-icon ml-2';
                header.appendChild(icon);
            }
            icon.className = `fas fa-sort-${newOrder === 'asc' ? 'up' : 'down'} sort-icon ml-2`;
            
            // Сортируем строки (простая текстовая сортировка)
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            
            rows.sort((a, b) => {
                const aText = a.children[columnIndex]?.textContent.trim() || '';
                const bText = b.children[columnIndex]?.textContent.trim() || '';
                
                return newOrder === 'asc' 
                    ? aText.localeCompare(bText, 'ru')
                    : bText.localeCompare(aText, 'ru');
            });
            
            // Обновляем DOM
            rows.forEach(row => tbody.appendChild(row));
        },

        initTableSearch() {
            document.querySelectorAll('[data-search]').forEach(input => {
                const handler = Utils.debounce(() => {
                    const searchTerm = input.value.toLowerCase();
                    const tableId = input.dataset.search;
                    const table = document.getElementById(tableId);
                    
                    if (table) {
                        const rows = table.querySelectorAll('tbody tr');
                        rows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(searchTerm) ? '' : 'none';
                        });
                    }
                }, 300);

                input.addEventListener('input', handler);
            });
        }
    };

    // ==================== УПРАВЛЕНИЕ ССЫЛКАМИ ====================
    const LinkManager = {
        init() {
            this.initLogoutLinks();
            this.initDeleteButtons();
        },

        initLogoutLinks() {
            document.querySelectorAll('a[href*="logout"]').forEach(link => {
                link.addEventListener('click', (e) => {
                    if (!confirm('Вы уверены, что хотите выйти?')) {
                        e.preventDefault();
                    }
                });
            });
        },

        initDeleteButtons() {
            document.querySelectorAll('.btn-delete, [data-delete]').forEach(button => {
                button.addEventListener('click', (e) => {
                    if (!confirm('Вы уверены, что хотите удалить этот элемент? Это действие нельзя отменить.')) {
                        e.preventDefault();
                    }
                });
            });
        }
    };

    // ==================== ПАРСИНГ URL ====================
    const UrlParser = {
        init() {
            const urlParams = new URLSearchParams(window.location.search);
            
            if (urlParams.has('message')) {
                const message = decodeURIComponent(urlParams.get('message'));
                const type = urlParams.get('type') || 'info';
                
                // Очищаем URL от параметров
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
                
                // Показываем уведомление
                setTimeout(() => Notification.show(message, type), 100);
            }
        }
    };

    // ==================== ИНИЦИАЛИЗАЦИЯ ====================
    function init() {
        Utils.safeExecute(() => {
            // Добавляем CSS анимации
            addAnimations();
            
            // Инициализация всех модулей
            SidebarManager.init();
            AnimationManager.init();
            FileUploadManager.init();
            FormManager.init();
            TableManager.init();
            LinkManager.init();
            UrlParser.init();
            
            console.log('Admin panel initialized successfully');
        }, 'Ошибка при инициализации админ-панели');
    }

    /**
     * Добавление CSS анимаций
     */
    function addAnimations() {
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            .stat-card, .action-btn {
                opacity: 0;
                animation: fadeInUp 0.5s ease forwards;
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .drop-zone {
                transition: all 0.3s ease;
            }
            
            .drop-zone--over {
                border-color: #007bff;
                background-color: rgba(0, 123, 255, 0.1);
                transform: scale(1.02);
            }
            
            .drop-zone__thumb {
                max-width: 100%;
                max-height: 150px;
                object-fit: contain;
                border-radius: 4px;
            }
            
            .notification {
                position: relative;
                overflow: hidden;
            }
            
            .notification::after {
                content: '';
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
                height: 3px;
                background: rgba(255, 255, 255, 0.5);
                animation: progressBar ${CONFIG.notificationDuration / 1000}s linear forwards;
            }
            
            @keyframes progressBar {
                from { width: 100%; }
                to { width: 0%; }
            }
            
            .sorted-asc::after,
            .sorted-desc::after {
                content: '';
                display: inline-block;
                margin-left: 5px;
            }
        `;
        document.head.appendChild(style);
    }

    // Запускаем после полной загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Экспортируем глобально для отладки (опционально)
    window.AdminPanel = {
        Notification,
        Utils,
        showNotification: (msg, type) => Notification.show(msg, type)
    };

})();

