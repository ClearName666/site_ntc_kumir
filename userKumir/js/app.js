(function() {
    'use strict';

    const API_URL = 'api.php';

    const DOM = {
        loginForm: document.getElementById('loginForm'),
        errorMessage: document.getElementById('errorMessage'),
        searchInput: document.getElementById('searchInput'),
        searchBtn: document.getElementById('searchBtn'),
        searchResults: document.getElementById('searchResults'),
        emptyState: document.getElementById('emptyState'),
        loader: document.getElementById('loader'),
        logoutBtn: document.getElementById('logoutBtn'),
        nodeModal: document.getElementById('nodeModal'),
        modalBody: document.getElementById('modalBody'),
        modalClose: document.querySelector('.modal-close'),
        modalOverlay: document.querySelector('.modal-overlay'),
        menuToggle: document.getElementById('menuToggle'),
        sidebar: document.getElementById('sidebar'),
        sidebarOverlay: document.getElementById('sidebarOverlay'),
        mainContent: document.getElementById('mainContent')
    };

    let isLoading = false;

    // API запрос
    async function apiRequest(action, data = {}) {
        const url = `${API_URL}?action=${action}`;
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (response.status === 401) {
                window.location.href = 'login.php';
                return { success: false, error: 'Сессия истекла' };
            }
            
            const text = await response.text();
            console.log('API Response:', text.substring(0, 200));
            
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON Parse Error:', e);
                console.error('Raw response:', text);
                return { success: false, error: 'Ошибка парсинга ответа', nodes: [] };
            }
        } catch (error) {
            console.error('API Error:', error);
            return { success: false, error: 'Ошибка соединения', nodes: [] };
        }
    }

    function setLoading(loading) {
        isLoading = loading;
        if (DOM.loader) DOM.loader.style.display = loading ? 'flex' : 'none';
        if (DOM.searchBtn) DOM.searchBtn.disabled = loading;
    }

    // function getResourceIcon(resourceId) {
    //     const icons = {
    //         1: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="2" y="2" width="20" height="20" rx="2"/><path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5"/></svg>',
    //         2: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
    //         3: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
    //         4: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 12H4M12 4v16"/></svg>'
    //     };
    //     return icons[resourceId] || icons[1];
    // }

    function getResourceIcon(resourceId) {
        const icons = {
            1: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <path d="M8 3v18M16 3v18M3 12h18M3 8h5M3 16h5M16 16h5M16 8h5"/>
                </svg>`,
            2: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>`,
            3: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 6v6l4 2"/>
                </svg>`,
            4: `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 12H4M12 4v16"/>
                </svg>`
        };
        return icons[resourceId] || icons[1];
    }

    async function doLogin(login, password) {
        const btn = DOM.loginForm?.querySelector('button');
        if (btn) {
            btn.classList.add('loading');
            btn.disabled = true;
        }
        
        const result = await apiRequest('login', { login, password });
        
        if (btn) {
            btn.classList.remove('loading');
            btn.disabled = false;
        }
        
        if (result.success) {
            window.location.href = 'index.php';
        } else {
            if (DOM.errorMessage) DOM.errorMessage.textContent = result.error || 'Ошибка входа';
        }
    }

    async function doSearch(query) {
        if (isLoading) return;
        
        setLoading(true);
        if (DOM.emptyState) DOM.emptyState.style.display = 'none';
        
        const result = await apiRequest('search', { query: query || '' });
        
        if (result.success && result.nodes) {
            renderResults(result.nodes);
        } else {
            renderResults([]);
            console.error('Search error:', result.error);
        }
        
        setLoading(false);
    }

    // function renderResults(nodes) {
    //     if (!DOM.searchResults) return;
        
    //     if (nodes.length === 0) {
    //         DOM.searchResults.innerHTML = '';
    //         if (DOM.emptyState) {
    //             DOM.emptyState.style.display = 'flex';
    //             const p = DOM.emptyState.querySelector('p');
    //             if (p) p.textContent = 'Узлы не найдены';
    //         }
    //         return;
    //     }
        
    //     if (DOM.emptyState) DOM.emptyState.style.display = 'none';
        
    //     DOM.searchResults.innerHTML = nodes.map(node => `
    //         <div class="node-card" data-id="${node.id}">
    //             <div class="node-header">
    //                 <div class="node-icon resource-${node.resource_id || 1}">
    //                     ${getResourceIcon(node.resource_id || 1)}
    //                 </div>
    //                 <div class="node-info">
    //                     <h3>${escapeHtml(node.data || 'Без названия')}</h3>
    //                     <span class="node-code">${escapeHtml(node.code || '—')}</span>
    //                 </div>
    //             </div>
    //             <div class="node-detail">
    //                 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
    //                     <rect x="2" y="2" width="20" height="20" rx="2"/>
    //                     <path d="M8 2v20M16 2v20M2 8h20M2 16h20"/>
    //                 </svg>
    //                 <span>ID: ${node.id}</span>
    //             </div>
    //             <div class="node-detail">
    //                 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
    //                     <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
    //                     <circle cx="12" cy="7" r="4"/>
    //                 </svg>
    //                 <span>${escapeHtml(node.mdmid || '—')}</span>
    //             </div>
    //             <div class="node-driver">
    //                 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
    //                     <rect x="2" y="4" width="20" height="16" rx="2"/>
    //                     <path d="M8 10h8M8 14h6"/>
    //                 </svg>
    //                 <span>${escapeHtml(node.driver || 'Не указан')}</span>
    //             </div>
    //             <div style="margin-top: 8px;">
    //                 <span class="status-badge ${node.enabled == 1 ? '' : 'disabled'}">
    //                     ${node.enabled == 1 ? 'Активен' : 'Отключен'}
    //                 </span>
    //             </div>
    //         </div>
    //     `).join('');
        
    //     DOM.searchResults.querySelectorAll('.node-card').forEach((card, i) => {
    //         card.addEventListener('click', () => openModal(nodes[i]));
    //     });
    // }

 function renderResults(nodes) {
    if (!DOM.searchResults) return;
    
    if (nodes.length === 0) {
        DOM.searchResults.innerHTML = '';
        if (DOM.emptyState) {
            DOM.emptyState.style.display = 'flex';
            const p = DOM.emptyState.querySelector('p');
            if (p) p.textContent = 'Узлы не найдены';
        }
        return;
    }
    
    if (DOM.emptyState) DOM.emptyState.style.display = 'none';
    
    DOM.searchResults.innerHTML = nodes.map(node => `
        <div class="node-card" data-id="${node.id}">
            <div class="node-card-header">
                <div class="node-icon resource-${node.resource_id || 1}">
                    ${getResourceIcon(node.resource_id || 1)}
                </div>
                <div class="node-header-info">
                    <div class="node-code">${escapeHtml(node.code || 'Без кода')}</div>
                    <span class="status-badge ${node.enabled == 1 ? '' : 'disabled'}">
                        ${node.enabled == 1 ? '● Активен' : '○ Отключен'}
                    </span>
                </div>
            </div>
            
            <div class="node-card-body">
                <div class="node-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                        <circle cx="12" cy="9" r="2.5"/>
                    </svg>
                    <span class="node-row-text">${escapeHtml(node.data || 'Адрес не указан')}</span>
                </div>
                
                <div class="node-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <path d="M8 10h8M8 14h6"/>
                    </svg>
                    <span class="node-row-text">${escapeHtml(node.driver || 'Драйвер не указан')}</span>
                </div>
                
                <div class="node-row">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <rect x="2" y="2" width="20" height="20" rx="2"/>
                        <path d="M7 2v20M17 2v20M2 12h20"/>
                    </svg>
                    <span class="node-row-text">
                        <span class="node-badge">MDM</span> ${escapeHtml(node.mdmid || '—')} 
                        <span class="node-badge">Оборуд.</span> ${escapeHtml(node.equipid || '—')}
                    </span>
                </div>
            </div>
        </div>
    `).join('');
    
    DOM.searchResults.querySelectorAll('.node-card').forEach((card, i) => {
        card.addEventListener('click', () => openModal(nodes[i]));
    });
}
    

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

function openModal(node) {
    if (!DOM.nodeModal || !DOM.modalBody) return;
    
    DOM.modalBody.innerHTML = `
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border);">
            <div class="node-icon resource-${node.resource_id || 1}" style="width: 56px; height: 56px;">
                ${getResourceIcon(node.resource_id || 1)}
            </div>
            <div>
                <h2 style="margin-bottom: 4px; font-size: 18px;">${escapeHtml(node.code || 'Узел')}</h2>
                <span class="status-badge ${node.enabled == 1 ? '' : 'disabled'}">
                    ${node.enabled == 1 ? '● Активен' : '○ Отключен'}
                </span>
            </div>
        </div>
        
        <div class="modal-detail-grid">
            <div class="modal-detail-row">
                <svg class="modal-detail-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v8M8 12h8"/>
                </svg>
                <div class="modal-detail-content">
                    <span class="modal-detail-label">ID</span>
                    <span class="modal-detail-value">${node.id}</span>
                </div>
            </div>
            
            <div class="modal-detail-row">
                <svg class="modal-detail-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    <circle cx="12" cy="9" r="2.5"/>
                </svg>
                <div class="modal-detail-content">
                    <span class="modal-detail-label">Адрес</span>
                    <span class="modal-detail-value">${escapeHtml(node.data || 'Не указан')}</span>
                </div>
            </div>
            
            <div class="modal-detail-row">
                <svg class="modal-detail-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="M8 10h8M8 14h6"/>
                </svg>
                <div class="modal-detail-content">
                    <span class="modal-detail-label">Драйвер</span>
                    <span class="modal-detail-value">${escapeHtml(node.driver || 'Не указан')}</span>
                </div>
            </div>
            
            <div class="modal-detail-row">
                <svg class="modal-detail-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="2" y="2" width="20" height="20" rx="2"/>
                    <path d="M8 2v20M16 2v20M2 8h20M2 16h20"/>
                </svg>
                <div class="modal-detail-content">
                    <span class="modal-detail-label">Оборудование</span>
                    <span class="modal-detail-value">
                        MDM: ${escapeHtml(node.mdmid || '—')}<br>
                        ID: ${escapeHtml(node.equipid || '—')}
                    </span>
                </div>
            </div>
            
            <div class="modal-detail-row">
                <svg class="modal-detail-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M4 4h16v16H4z"/>
                    <circle cx="9" cy="9" r="1"/>
                    <circle cx="15" cy="15" r="1"/>
                </svg>
                <div class="modal-detail-content">
                    <span class="modal-detail-label">Дополнительно</span>
                    <span class="modal-detail-value">
                        Тип ресурса: ${node.resource_id || 1}<br>
                        Счётчиков: ${node.full_count || 0}<br>
                        ID драйвера: ${node.driver_id || '—'}
                    </span>
                </div>
            </div>
        </div>
    `;
    
    DOM.nodeModal.classList.add('active');
    document.body.style.overflow = 'hidden';
}
    function closeModal() {
        if (!DOM.nodeModal) return;
        DOM.nodeModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    async function doLogout() {
        await apiRequest('logout');
        window.location.href = 'login.php';
    }

    function toggleSidebar() {
        if (DOM.sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    function openSidebar() {
        DOM.sidebar.classList.add('open');
        DOM.sidebarOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        DOM.sidebar.classList.remove('open');
        DOM.sidebarOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    function bindEvents() {

        // Мобильное меню
        if (DOM.menuToggle) {
            DOM.menuToggle.addEventListener('click', toggleSidebar);
        }

        if (DOM.sidebarOverlay) {
            DOM.sidebarOverlay.addEventListener('click', closeSidebar);
        }

        // Закрытие меню при клике на пункт меню
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    closeSidebar();
                }
            });
        });

        // Закрытие меню при ресайзе
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                openSidebar();
            } else {
                closeSidebar();
            }
        });

        if (DOM.loginForm) {
            DOM.loginForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const login = document.getElementById('login')?.value.trim();
                const password = document.getElementById('password')?.value.trim();
                
                if (login && password) {
                    doLogin(login, password);
                } else {
                    if (DOM.errorMessage) DOM.errorMessage.textContent = 'Введите логин и пароль';
                }
            });
        }

        if (DOM.searchBtn) {
            DOM.searchBtn.addEventListener('click', function() {
                doSearch(DOM.searchInput?.value.trim() || '');
            });
        }

        if (DOM.searchInput) {
            DOM.searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    doSearch(e.target.value.trim() || '');
                }
            });
        }

        if (DOM.logoutBtn) {
            DOM.logoutBtn.addEventListener('click', doLogout);
        }

        if (DOM.modalClose) DOM.modalClose.addEventListener('click', closeModal);
        if (DOM.modalOverlay) DOM.modalOverlay.addEventListener('click', closeModal);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && DOM.nodeModal?.classList.contains('active')) {
                closeModal();
            }
        });
    }

    function init() {
        bindEvents();
        
        if (DOM.searchResults) {
            setTimeout(() => doSearch(''), 100);
        }

        // Intersection Observer для анимации при скролле
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'scale(1) translateY(0)';
                }
            });
        }, { threshold: 0.1 });

        // При рендеринге карточек
        document.querySelectorAll('.node-card').forEach(card => {
            observer.observe(card);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();