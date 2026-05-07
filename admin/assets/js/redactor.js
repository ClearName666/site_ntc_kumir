// ===== РЕДАКТОР СТАТЕЙ =====

let articleBuilder = null;

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

class ArticleBuilder {
    constructor(existingContent = null) {
        this.blocks = [];
        this.dragSourceIndex = null; // для перетаскивания через ручку
        this.load(existingContent);
        this.render();
    }
    
    load(existingContent) {
        if (existingContent && existingContent.trim()) {
            this.parseExistingContent(existingContent);
        } else {
            const saved = localStorage.getItem('articleBlocks');
            if (saved) {
                try {
                    this.blocks = JSON.parse(saved);
                } catch(e) {}
            }
        }
    }
    
    parseExistingContent(html) {
        this.blocks = [];
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        
        // Ищем главную сетку блоков
        let grid = tempDiv.querySelector('.article-builder-grid');
        if (!grid) {
            const text = tempDiv.innerHTML.trim();
            if (text) {
                this.blocks.push({
                    id: Date.now(),
                    type: 'text',
                    content: text,
                    fontSize: 16,
                    glassFrame: false,
                    wide: false
                });
            }
            return;
        }
        
        // Проходим по всем дочерним элементам сетки строго по порядку
        const children = Array.from(grid.children);
        for (const child of children) {
            if (child.classList.contains('article-builder-block')) {
                // Может быть list-block, image-block, или обычный
                if (child.classList.contains('list-block')) {
                    const titleEl = child.querySelector('.list-title');
                    const title = titleEl ? titleEl.innerText : 'Список';
                    const items = Array.from(child.querySelectorAll('.styled-list li')).map(li => li.innerText);
                    this.blocks.push({
                        id: Date.now() + Math.random(),
                        type: 'list',
                        title: title,
                        items: items,
                        glassFrame: child.classList.contains('glass-frame'),
                        wide: child.classList.contains('block-wide')
                    });
                } else if (child.classList.contains('image-block')) {
                    const img = child.querySelector('img');
                    if (img && img.src) {
                        this.blocks.push({
                            id: Date.now() + Math.random(),
                            type: 'image',
                            imageUrl: img.src,
                            glassFrame: false,
                            wide: child.classList.contains('block-wide')
                        });
                    }
                } else {
                    // Обычный текстовый блок
                    let fontSize = 16;
                    const styleAttr = child.getAttribute('style');
                    if (styleAttr) {
                        const match = styleAttr.match(/font-size:\s*(\d+)px/);
                        if (match) fontSize = parseInt(match[1]);
                    }
                    this.blocks.push({
                        id: Date.now() + Math.random(),
                        type: 'text',
                        content: child.innerHTML,
                        imageUrl: null,
                        glassFrame: child.classList.contains('glass-frame'),
                        wide: child.classList.contains('block-wide'),
                        fontSize: fontSize
                    });
                }
            } else if (child.classList.contains('article-slider')) {
                // Слайдер
                const images = [];
                const imgs = child.querySelectorAll('img');
                imgs.forEach(img => {
                    if (img.src) images.push(img.src);
                });
                this.blocks.push({
                    id: Date.now() + Math.random(),
                    type: 'slider',
                    images: images,
                    glassFrame: false,
                    wide: child.classList.contains('block-wide')
                });
            }
        }
        this.save();
    }
    
    save() {
        localStorage.setItem('articleBlocks', JSON.stringify(this.blocks));
    }
    
    addBlock(type) {
        let newBlock;
        switch(type) {
            case 'text':
                newBlock = { id: Date.now(), type: 'text', content: '<p>Введите текст...</p>', imageUrl: null, glassFrame: false, wide: false, fontSize: 16 };
                break;
            case 'image':
                newBlock = { id: Date.now(), type: 'image', content: null, imageUrl: null, glassFrame: false, wide: false };
                break;
            case 'slider':
                newBlock = { id: Date.now(), type: 'slider', images: [], glassFrame: false, wide: false };
                break;
            case 'list':
                newBlock = { id: Date.now(), type: 'list', title: 'Мой список', items: ['Элемент 1', 'Элемент 2'], glassFrame: false, wide: false };
                break;
            default: return;
        }
        this.blocks.push(newBlock);
        this.save();
        this.render();
    }
    
    deleteBlock(id) {
        this.blocks = this.blocks.filter(block => block.id !== id);
        this.save();
        this.render();
    }
    
    updateContent(id, content) {
        const block = this.blocks.find(b => b.id === id);
        if (block) block.content = content;
        this.save();
    }
    
    updateImage(id, imageUrl) {
        const block = this.blocks.find(b => b.id === id);
        if (block) {
            block.imageUrl = imageUrl;
            this.save();
            this.render();
        }
    }
    
    addSliderImage(id, imageUrl) {
        const block = this.blocks.find(b => b.id === id);
        if (block && block.type === 'slider') {
            if (!block.images) block.images = [];
            if (block.images.length < 10) {
                block.images.push(imageUrl);
                this.save();
                this.render();
            } else {
                this.showNotification('Максимум 10 изображений', 'error');
            }
        }
    }
    
    toggleGlassFrame(id) {
        const block = this.blocks.find(b => b.id === id);
        if (block) {
            block.glassFrame = !block.glassFrame;
            this.save();
            this.render();
        }
    }
    
    toggleWide(id) {
        const block = this.blocks.find(b => b.id === id);
        if (block) {
            block.wide = !block.wide;
            this.save();
            this.render();
        }
    }
    
    clearAll() {
        if (confirm('Удалить все блоки?')) {
            this.blocks = [];
            this.save();
            this.render();
        }
    }

    setFontSize(blockId, size) {
        const block = this.blocks.find(b => b.id === blockId);
        if (block) {
            block.fontSize = size;
            this.save();
        }
        const editableDiv = document.querySelector(`[data-block-id="${blockId}"] .builder-editable`);
        if (editableDiv) {
            editableDiv.style.fontSize = size + 'px';
        }
    }
    
    formatText(blockId, command, value = null) {
        const editableDiv = document.querySelector(`[data-block-id="${blockId}"] .builder-editable`);
        if (!editableDiv) return;
        editableDiv.focus();
        if (command === 'removeFormat') {
            document.execCommand('removeFormat', false, null);
        } else if (command === 'formatBlock') {
            document.execCommand(command, false, value);
        } else {
            document.execCommand(command, false, null);
        }
        const block = this.blocks.find(b => b.id === blockId);
        if (block) {
            block.content = editableDiv.innerHTML;
            this.save();
        }
        editableDiv.focus();
    }
    
    compressImage(file) {
        return new Promise((resolve, reject) => {
            if (!file) return reject('Неверный файл');
            if (file.type === 'image/svg+xml') {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.onerror = () => reject('Ошибка чтения SVG');
                reader.readAsDataURL(file);
                return;
            }
            if (!file.type.startsWith('image/')) return reject('Неверный формат');
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let width = img.width, height = img.height;
                    const maxWidth = 800, maxHeight = 600;
                    if (width > maxWidth) { height = (height * maxWidth) / width; width = maxWidth; }
                    if (height > maxHeight) { width = (width * maxHeight) / height; height = maxHeight; }
                    canvas.width = width; canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    if (file.type === 'image/png') ctx.clearRect(0, 0, width, height);
                    ctx.drawImage(img, 0, 0, width, height);
                    const format = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                    const quality = file.type === 'image/png' ? 1 : 0.7;
                    resolve(canvas.toDataURL(format, quality));
                };
                img.onerror = () => reject('Ошибка загрузки');
                img.src = e.target.result;
            };
            reader.onerror = () => reject('Ошибка чтения');
            reader.readAsDataURL(file);
        });
    }
    
    async handleImageUpload(blockId, file, isSlider = false) {
        if (!file) return this.showNotification('Выберите изображение', 'error');
        this.showNotification('Загрузка...', 'info');
        try {
            const base64 = await this.compressImage(file);
            if (isSlider) this.addSliderImage(blockId, base64);
            else this.updateImage(blockId, base64);
            this.showNotification('Успешно!', 'success');
        } catch(e) {
            this.showNotification('Ошибка загрузки', 'error');
        }
    }
    
    showNotification(msg, type) {
        const n = document.createElement('div');
        n.textContent = msg;
        n.style.cssText = `position:fixed; bottom:20px; right:20px; padding:12px 20px; background:${type==='success'?'#10b981':type==='error'?'#ef4444':'#3b82f6'}; color:white; border-radius:8px; z-index:10001; max-width:300px; font-size:14px;`;
        document.body.appendChild(n);
        setTimeout(() => n.remove(), 3000);
    }
    
    handleDragOver(e) { e.preventDefault(); }
    handleDrop(e, blockId, isSlider) {
        e.preventDefault();
        const files = e.dataTransfer.files;
        if (files.length) this.handleImageUpload(blockId, files[0], isSlider);
    }
    
    selectImageFile(blockId, isSlider) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = (e) => {
            if (e.target.files[0]) this.handleImageUpload(blockId, e.target.files[0], isSlider);
        };
        input.click();
    }
    
    // Перетаскивание блоков через ручку
    onDragStartHandle(e, index) {
        this.dragSourceIndex = index;
        e.dataTransfer.setData('text/plain', index);
        e.dataTransfer.effectAllowed = 'move';
        e.target.closest('.builder-block').style.opacity = '0.4';
    }
    
    onDragOverBlock(e, targetIndex) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        const blockDiv = e.currentTarget;
        blockDiv.style.boxShadow = '0 0 0 2px #3b82f6';
    }
    
    onDragLeaveBlock(e) {
        e.currentTarget.style.boxShadow = 'none';
    }
    
    onDropOnBlock(e, targetIndex) {
        e.preventDefault();
        const blockDiv = e.currentTarget;
        blockDiv.style.boxShadow = 'none';
        if (this.dragSourceIndex !== null && this.dragSourceIndex !== targetIndex) {
            const moved = this.blocks[this.dragSourceIndex];
            this.blocks.splice(this.dragSourceIndex, 1);
            this.blocks.splice(targetIndex, 0, moved);
            this.save();
            this.render();
        }
        this.dragSourceIndex = null;
    }
    
    onDragEndHandle(e) {
        const blockDiv = e.target.closest('.builder-block');
        if (blockDiv) blockDiv.style.opacity = '';
        this.dragSourceIndex = null;
        // Сбросить тени у всех блоков
        document.querySelectorAll('.builder-block').forEach(b => b.style.boxShadow = 'none');
    }
    
    render() {
        const grid = document.getElementById('blocksGrid');
        if (!grid) return;
        grid.innerHTML = '';
        if (this.blocks.length === 0) {
            grid.innerHTML = `<div style="grid-column: span 2; text-align: center; padding: 60px; color: #6b7280; background: #f9fafb; border-radius: 12px; border: 2px dashed #e5e7eb;">
                <i class="fas fa-plus-circle" style="font-size: 48px; margin-bottom: 16px; color: #3b82f6;"></i>
                <p>Нет блоков. Добавьте текстовый блок, изображение, слайдер или перечисление</p>
            </div>`;
            return;
        }
        
        this.blocks.forEach((block, index) => {
            const blockDiv = document.createElement('div');
            blockDiv.className = `builder-block ${block.wide ? 'block-wide' : ''}`;
            blockDiv.setAttribute('data-block-id', block.id);
            blockDiv.setAttribute('data-index', index);
            // Блок не draggable, перетаскивание только через ручку
            blockDiv.style.position = 'relative';
            blockDiv.style.borderRadius = '24px';
            blockDiv.style.transition = 'all 0.3s ease';
            if (block.wide) blockDiv.style.gridColumn = 'span 2';
            
            // Стили в зависимости от типа
            if (block.type === 'slider' || block.type === 'image') {
                blockDiv.style.padding = '0';
                blockDiv.style.background = 'transparent';
                blockDiv.style.border = 'none';
                blockDiv.style.minHeight = 'auto';
                blockDiv.style.display = 'block';
            } else {
                blockDiv.style.padding = '24px';
                blockDiv.style.background = 'white';
                blockDiv.style.border = '1px solid #e5e7eb';
                blockDiv.style.minHeight = '150px';
                blockDiv.style.display = 'flex';
                blockDiv.style.flexDirection = 'column';
            }
            
            if (block.type === 'text' && block.glassFrame) {
                blockDiv.style.background = 'rgba(255,255,255,0.2)';
                blockDiv.style.backdropFilter = 'blur(12px)';
                blockDiv.style.border = '1px solid rgba(255,255,255,0.3)';
                blockDiv.style.boxShadow = '0 8px 32px rgba(0,0,0,0.1)';
            }
            
            // Ручка перетаскивания (draggable)
            const dragHandle = document.createElement('div');
            dragHandle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
            dragHandle.draggable = true;
            dragHandle.style.cssText = 'position:absolute; left:-12px; top:50%; transform:translateY(-50%); width:28px; height:56px; background:#e5e7eb; border-radius:12px; display:flex; align-items:center; justify-content:center; cursor:grab; color:#6b7280; z-index:20;';
            dragHandle.addEventListener('dragstart', (e) => this.onDragStartHandle(e, index));
            dragHandle.addEventListener('dragend', (e) => this.onDragEndHandle(e));
            
            // Кнопка удаления
            const deleteBtn = document.createElement('button');
            deleteBtn.innerHTML = '×';
            deleteBtn.style.cssText = 'position:absolute; top:-10px; right:-10px; width:28px; height:28px; background:#ef4444; color:white; border:none; border-radius:50%; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:18px; z-index:20;';
            deleteBtn.onclick = (e) => { e.stopPropagation(); this.deleteBlock(block.id); };
            
            blockDiv.appendChild(dragHandle);
            blockDiv.appendChild(deleteBtn);
            
            // Обработчики drop на самом блоке (не на ручке)
            blockDiv.addEventListener('dragover', (e) => this.onDragOverBlock(e, index));
            blockDiv.addEventListener('dragleave', (e) => this.onDragLeaveBlock(e));
            blockDiv.addEventListener('drop', (e) => this.onDropOnBlock(e, index));
            
            // --- Текстовый блок ---
            if (block.type === 'text') {
                const controls = document.createElement('div');
                controls.style.cssText = 'position:absolute; bottom:10px; left:50%; transform:translateX(-50%); display:flex; gap:8px; z-index:20; background:rgba(0,0,0,0.6); padding:6px 12px; border-radius:30px; backdrop-filter:blur(4px);';
                
                const glassCb = document.createElement('div');
                glassCb.style.cssText = 'background:#f3f4f6; padding:6px 10px; border-radius:20px; display:flex; align-items:center; gap:6px; cursor:pointer;';
                glassCb.innerHTML = `<input type="checkbox" id="glass-${block.id}" ${block.glassFrame ? 'checked' : ''}><label style="font-size:11px; cursor:pointer;">✨ Стекло</label>`;
                glassCb.querySelector('input').onchange = (e) => { e.stopPropagation(); this.toggleGlassFrame(block.id); };
                
                const wideCb = document.createElement('div');
                wideCb.style.cssText = 'background:#f3f4f6; padding:6px 10px; border-radius:20px; display:flex; align-items:center; gap:6px; cursor:pointer;';
                wideCb.innerHTML = `<input type="checkbox" id="wide-${block.id}" ${block.wide ? 'checked' : ''}><label style="font-size:11px; cursor:pointer;">📐 На всю ширину</label>`;
                wideCb.querySelector('input').onchange = (e) => { e.stopPropagation(); this.toggleWide(block.id); };
                
                controls.appendChild(glassCb);
                controls.appendChild(wideCb);
                blockDiv.appendChild(controls);
                
                const toolbar = document.createElement('div');
                toolbar.style.cssText = 'background:rgba(249,250,251,0.95); backdropFilter:blur(8px); border:1px solid #e5e7eb; border-radius:12px; padding:8px; margin-bottom:16px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;';
                toolbar.innerHTML = `
                    <button data-cmd="bold" style="padding:6px 14px; border:1px solid #d1d5db; background:white; border-radius:8px;"><b>B</b></button>
                    <button data-cmd="italic" style="padding:6px 14px; border:1px solid #d1d5db; background:white; border-radius:8px;"><i>I</i></button>
                    <button data-cmd="underline" style="padding:6px 14px; border:1px solid #d1d5db; background:white; border-radius:8px;"><u>U</u></button>
                    <button data-cmd="h1" style="padding:6px 14px; border:1px solid #d1d5db; background:white; border-radius:8px;">H1</button>
                    <button data-cmd="h2" style="padding:6px 14px; border:1px solid #d1d5db; background:white; border-radius:8px;">H2</button>
                    <button data-cmd="h3" style="padding:6px 14px; border:1px solid #d1d5db; background:white; border-radius:8px;">H3</button>
                    <div style="display:flex; align-items:center; gap:6px; margin-left:8px;">
                        <span style="font-size:12px;">🔤</span>
                        <input type="range" min="5" max="40" value="16" step="1" style="width:100px;">
                        <span class="font-size-val" style="font-size:11px; width:30px;">16px</span>
                    </div>
                `;
                const slider = toolbar.querySelector('input[type="range"]');
                const valSpan = toolbar.querySelector('.font-size-val');
                // внутри toolbar, после создания слайдера и valSpan
                slider.value = block.fontSize || 16;
                valSpan.textContent = (block.fontSize || 16) + 'px';
                slider.addEventListener('input', (e) => {
                    const val = e.target.value;
                    valSpan.textContent = val + 'px';
                    this.setFontSize(block.id, val);
                });
                toolbar.querySelectorAll('[data-cmd]').forEach(btn => {
                    btn.onclick = (e) => {
                        e.preventDefault();
                        const cmd = btn.getAttribute('data-cmd');
                        if (cmd === 'h1' || cmd === 'h2' || cmd === 'h3') this.formatText(block.id, 'formatBlock', cmd);
                        else this.formatText(block.id, cmd);
                    };
                });
                
                const editable = document.createElement('div');
                editable.setAttribute('contenteditable', 'true');
                editable.className = 'builder-editable';
                editable.style.cssText = 'outline:none; flex:1; min-height:120px; line-height:1.6; word-wrap:break-word; white-space:pre-wrap;';
                editable.innerHTML = block.content || '<p>Введите текст...</p>';
                // Восстанавливаем размер шрифта
                if (block.fontSize) {
                    editable.style.fontSize = block.fontSize + 'px';
                } else {
                    editable.style.fontSize = '16px';
                    block.fontSize = 16;
                }
                editable.oninput = () => this.updateContent(block.id, editable.innerHTML);
                
                blockDiv.appendChild(toolbar);
                blockDiv.appendChild(editable);
            }
            // --- Блок изображения ---
            else if (block.type === 'image') {
                const controls = document.createElement('div');
                controls.style.cssText = 'position:absolute; bottom:10px; left:50%; transform:translateX(-50%); display:flex; gap:8px; z-index:20; background:rgba(0,0,0,0.6); padding:6px 12px; border-radius:30px;';
                const wideCb = document.createElement('div');
                wideCb.style.cssText = 'background:rgba(0,0,0,0.6); padding:6px 10px; border-radius:20px; display:flex; align-items:center; gap:6px; color:white; cursor:pointer;';
                wideCb.innerHTML = `<input type="checkbox" id="wide-img-${block.id}" ${block.wide ? 'checked' : ''}><label style="font-size:11px; cursor:pointer; color:white;">📐 На всю ширину</label>`;
                wideCb.querySelector('input').onchange = (e) => { e.stopPropagation(); this.toggleWide(block.id); };
                controls.appendChild(wideCb);
                blockDiv.appendChild(controls);
                
                const container = document.createElement('div');
                container.style.cssText = 'text-align:center; min-height:200px; display:flex; align-items:center; justify-content:center; background:transparent; border-radius:20px; flex-direction:column;';
                container.ondragover = (e) => this.handleDragOver(e);
                container.ondrop = (e) => this.handleDrop(e, block.id, false);
                
                if (block.imageUrl && block.imageUrl !== 'null') {
                    const img = document.createElement('img');
                    img.src = block.imageUrl;
                    img.style.cssText = 'width:100%; height:auto; border-radius:20px; display:block;';
                    container.appendChild(img);
                    const btnDiv = document.createElement('div');
                    btnDiv.style.cssText = 'position:absolute; bottom:10px; right:10px; display:flex; gap:8px;';
                    const changeBtn = document.createElement('button');
                    changeBtn.innerHTML = '🖼 Заменить';
                    changeBtn.style.cssText = 'background:rgba(0,0,0,0.7); color:white; border:none; border-radius:8px; padding:6px 12px; cursor:pointer;';
                    changeBtn.onclick = () => this.selectImageFile(block.id, false);
                    const delBtn = document.createElement('button');
                    delBtn.innerHTML = '🗑 Удалить';
                    delBtn.style.cssText = 'background:rgba(0,0,0,0.7); color:white; border:none; border-radius:8px; padding:6px 12px; cursor:pointer;';
                    delBtn.onclick = () => this.updateImage(block.id, null);
                    btnDiv.appendChild(changeBtn);
                    btnDiv.appendChild(delBtn);
                    container.appendChild(btnDiv);
                } else {
                    const uploadBtn = document.createElement('button');
                    uploadBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Выбрать изображение';
                    uploadBtn.style.cssText = 'background:#3b82f6; color:white; border:none; border-radius:12px; padding:12px 24px; cursor:pointer; display:flex; align-items:center; gap:8px;';
                    uploadBtn.onclick = () => this.selectImageFile(block.id, false);
                    const dragText = document.createElement('p');
                    dragText.innerHTML = 'Или перетащите изображение сюда';
                    dragText.style.cssText = 'color:#6b7280; margin-top:10px; font-size:12px;';
                    container.appendChild(uploadBtn);
                    container.appendChild(dragText);
                }
                blockDiv.appendChild(container);
            }
            // --- Слайдер ---
            else if (block.type === 'slider') {
                
                const sliderContainer = document.createElement('div');
                sliderContainer.style.cssText = 'position:relative; border-radius:24px; overflow:hidden; background:#f9fafb;';
                if (!block.images || block.images.length === 0) {
                    const uploadArea = document.createElement('div');
                    uploadArea.style.cssText = 'text-align:center; padding:40px;';
                    uploadArea.innerHTML = `
                        <i class="fas fa-images" style="font-size:48px; color:#3b82f6;"></i>
                        <h3>Создайте слайдер</h3>
                        <p>Загрузите от 2 до 10 изображений</p>
                        <button class="builder-btn builder-btn-primary" style="margin-top:16px;">+ Добавить изображения</button>
                        <p style="font-size:12px;">Или перетащите файлы сюда</p>
                    `;
                    const addBtn = uploadArea.querySelector('button');
                    addBtn.onclick = () => this.selectImageFile(block.id, true);
                    uploadArea.ondragover = (e) => this.handleDragOver(e);
                    uploadArea.ondrop = (e) => this.handleDrop(e, block.id, true);
                    sliderContainer.appendChild(uploadArea);
                } else {
                    let currentIdx = 0;
                    const inner = document.createElement('div');
                    inner.style.cssText = 'position:relative; width:100%; overflow:hidden; border-radius:24px;';
                    const track = document.createElement('div');
                    track.style.cssText = 'display:flex; transition:transform 0.5s cubic-bezier(0.4,0,0.2,1);';
                    block.images.forEach(src => {
                        const slide = document.createElement('div');
                        slide.style.cssText = 'flex-shrink:0; width:100%;';
                        const img = document.createElement('img');
                        img.src = src;
                        img.style.cssText = 'width:100%; display:block;';
                        slide.appendChild(img);
                        track.appendChild(slide);
                    });
                    inner.appendChild(track);
                    
                    const updateTransform = () => { track.style.transform = `translateX(-${currentIdx * 100}%)`; };
                    const prevBtn = document.createElement('button');
                    prevBtn.innerHTML = '‹';
                    prevBtn.style.cssText = 'position:absolute; top:50%; left:16px; transform:translateY(-50%); width:40px; height:40px; background:rgba(255,255,255,0.9); border:none; border-radius:50%; cursor:pointer; font-size:20px; z-index:10;';
                    const nextBtn = document.createElement('button');
                    nextBtn.innerHTML = '›';
                    nextBtn.style.cssText = 'position:absolute; top:50%; right:16px; transform:translateY(-50%); width:40px; height:40px; background:rgba(255,255,255,0.9); border:none; border-radius:50%; cursor:pointer; font-size:20px; z-index:10;';
                    
                    prevBtn.onclick = () => { 
                        currentIdx = (currentIdx - 1 + block.images.length) % block.images.length; 
                        updateTransform(); 
                        updateDots(); 
                        // обновить миниатюры
                        document.querySelectorAll(`[data-thumb-for="${block.id}"]`).forEach((thumb, i) => {
                            thumb.style.borderColor = i === currentIdx ? '#3b82f6' : 'transparent';
                        });
                    };
                    nextBtn.onclick = () => { 
                        currentIdx = (currentIdx + 1) % block.images.length; 
                        updateTransform(); 
                        updateDots(); 
                        // обновить миниатюры
                        document.querySelectorAll(`[data-thumb-for="${block.id}"]`).forEach((thumb, i) => {
                            thumb.style.borderColor = i === currentIdx ? '#3b82f6' : 'transparent';
                        });
                    };
                    
                    inner.appendChild(prevBtn);
                    inner.appendChild(nextBtn);
                    
                    const dotsDiv = document.createElement('div');
                    dotsDiv.style.cssText = 'position:absolute; bottom:16px; left:50%; transform:translateX(-50%); display:flex; gap:8px; z-index:10;';
                    const updateDots = () => {
                        dotsDiv.innerHTML = '';
                        block.images.forEach((_, i) => {
                            const dot = document.createElement('button');
                            dot.style.cssText = `width:8px; height:8px; background:${i === currentIdx ? 'white' : 'rgba(255,255,255,0.5)'}; border:none; border-radius:50%; cursor:pointer;`;
                            dot.onclick = () => { 
                            currentIdx = i; 
                            updateTransform(); 
                            updateDots(); 
                            document.querySelectorAll(`[data-thumb-for="${block.id}"]`).forEach((thumb, idx) => {
                                thumb.style.borderColor = idx === currentIdx ? '#3b82f6' : 'transparent';
                            });
                        };
                            dotsDiv.appendChild(dot);
                        });
                    };
                    updateDots();
                    inner.appendChild(dotsDiv);
                    
                    const addPhotoBtn = document.createElement('button');
                    addPhotoBtn.innerHTML = '+ Добавить фото';
                    addPhotoBtn.style.cssText = 'position:absolute; bottom:16px; left:16px; background:rgba(0,0,0,0.6); color:white; border:none; border-radius:20px; padding:6px 12px; cursor:pointer; font-size:12px; z-index:10;';
                    addPhotoBtn.onclick = () => this.selectImageFile(block.id, true);
                    inner.appendChild(addPhotoBtn);

                    // ======= ВСТАВИТЬ МИНИАТЮРЫ =======
                        const thumbsContainer = document.createElement('div');
                        thumbsContainer.style.cssText = 'position: absolute; bottom: 16px; right: 16px; display: flex; gap: 8px; z-index: 10; background: rgba(0,0,0,0.5); padding: 8px; border-radius: 16px; backdrop-filter: blur(4px); overflow-x: auto; max-width: calc(100% - 100px);';
                        block.images.forEach((imgSrc, idx) => {
                        const thumb = document.createElement('div');
                        thumb.style.cssText = 'width: 50px; height: 50px; border-radius: 8px; overflow: hidden; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; flex-shrink: 0;';
                        if (idx === currentIdx) thumb.style.borderColor = '#3b82f6';
                        const thumbImg = document.createElement('img');
                        thumbImg.src = imgSrc;
                        thumbImg.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                        thumb.appendChild(thumbImg);
                        thumb.onclick = () => {
                            currentIdx = idx;
                            track.style.transform = `translateX(-${currentIdx * 100}%)`;
                            updateDots();
                            // Обновить border активной миниатюры
                            document.querySelectorAll(`[data-thumb-for="${block.id}"]`).forEach(t => t.style.borderColor = 'transparent');
                            thumb.style.borderColor = '#3b82f6';
                        };
                        thumb.setAttribute('data-thumb-for', block.id);
                        thumbsContainer.appendChild(thumb);
                    });
                    inner.appendChild(thumbsContainer);
                    sliderContainer.appendChild(inner);
                }
                blockDiv.appendChild(sliderContainer);
            }
            // --- Блок перечисления (list) ---
            else if (block.type === 'list') {
                // Стеклянный стиль, если включен
                if (block.glassFrame) {
                    blockDiv.style.background = 'rgba(255, 255, 255, 0.2)';
                    blockDiv.style.backdropFilter = 'blur(12px)';
                    blockDiv.style.border = '1px solid rgba(255, 255, 255, 0.3)';
                    blockDiv.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.1)';
                } else {
                    blockDiv.style.background = 'white';
                    blockDiv.style.border = '1px solid #e5e7eb';
                    blockDiv.style.boxShadow = 'none';
                }
                
                const controls = document.createElement('div');
                controls.style.cssText = 'position:absolute; bottom:10px; left:50%; transform:translateX(-50%); display:flex; gap:8px; z-index:20; background:rgba(0,0,0,0.6); padding:6px 12px; border-radius:30px; backdrop-filter:blur(4px);';
                
                const glassCb = document.createElement('div');
                glassCb.style.cssText = 'background:#f3f4f6; padding:6px 10px; border-radius:20px; display:flex; align-items:center; gap:6px; cursor:pointer;';
                glassCb.innerHTML = `<input type="checkbox" id="glass-list-${block.id}" ${block.glassFrame ? 'checked' : ''}><label style="font-size:11px;">✨ Стекло</label>`;
                glassCb.querySelector('input').onchange = (e) => { e.stopPropagation(); this.toggleGlassFrame(block.id); };
                
                const wideCb = document.createElement('div');
                wideCb.style.cssText = 'background:#f3f4f6; padding:6px 10px; border-radius:20px; display:flex; align-items:center; gap:6px; cursor:pointer;';
                wideCb.innerHTML = `<input type="checkbox" id="wide-list-${block.id}" ${block.wide ? 'checked' : ''}><label style="font-size:11px;">📐 На всю ширину</label>`;
                wideCb.querySelector('input').onchange = (e) => { e.stopPropagation(); this.toggleWide(block.id); };
                
                controls.appendChild(glassCb);
                controls.appendChild(wideCb);
                blockDiv.appendChild(controls);
                
                const titleInput = document.createElement('input');
                titleInput.type = 'text';
                titleInput.value = block.title || 'Мой список';
                titleInput.style.cssText = 'width:100%; font-size:20px; font-weight:bold; border:none; border-bottom:2px solid #e5e7eb; margin-bottom:16px; padding:8px 0; outline:none; background:transparent;';
                titleInput.addEventListener('input', () => { block.title = titleInput.value; this.save(); });
                blockDiv.appendChild(titleInput);
                
                const itemsContainer = document.createElement('div');
                itemsContainer.style.cssText = 'display:flex; flex-direction:column; gap:12px;';
                const updateItems = () => {
                    itemsContainer.innerHTML = '';
                    block.items.forEach((item, idx) => {
                        const row = document.createElement('div');
                        row.style.cssText = 'display:flex; align-items:center; gap:8px;';
                        const inp = document.createElement('input');
                        inp.type = 'text';
                        inp.value = item;
                        inp.style.cssText = 'flex:1; padding:8px 12px; border:1px solid #e5e7eb; border-radius:12px; outline:none; background:transparent;';
                        inp.oninput = () => { block.items[idx] = inp.value; this.save(); };
                        const del = document.createElement('button');
                        del.innerHTML = '🗑';
                        del.style.cssText = 'background:#ef4444; color:white; border:none; border-radius:8px; width:32px; height:32px; cursor:pointer;';
                        del.onclick = () => { block.items.splice(idx,1); this.save(); updateItems(); };
                        row.appendChild(inp);
                        row.appendChild(del);
                        itemsContainer.appendChild(row);
                    });
                };
                updateItems();
                
                const addBtn = document.createElement('button');
                addBtn.innerHTML = '+ Добавить пункт';
                addBtn.style.cssText = 'margin-top:12px; padding:8px 16px; background:#f3f4f6; border:none; border-radius:12px; cursor:pointer; display:flex; align-items:center; gap:6px;';
                addBtn.onclick = () => {
                    block.items.push('Новый пункт');
                    this.save();
                    updateItems();
                };
                blockDiv.appendChild(addBtn);               
                blockDiv.appendChild(itemsContainer);
            
            }
            
            grid.appendChild(blockDiv);
        });
    }
    
    generateHTML() {
        if (!this.blocks.length) return '<div class="article-builder-grid"><div class="article-builder-block"><p>Статья пуста</p></div></div>';
        let html = '<div class="article-builder-grid">';
        for (const block of this.blocks) {
            const glassClass = (block.type === 'text' && block.glassFrame) ? 'glass-frame' : (block.type === 'list' && block.glassFrame) ? 'glass-frame' : '';
            const wideClass = block.wide ? 'block-wide' : '';
            switch(block.type) {
                case 'text': {
                    let styleAttr = '';
                    if (block.fontSize && block.fontSize !== 16) {
                        styleAttr = ` style="font-size: ${block.fontSize}px;"`;
                    }
                    html += `<div class="article-builder-block ${glassClass} ${wideClass}"${styleAttr}>${block.content || ''}</div>`;
                    break;
                }
                case 'image':
                    if (block.imageUrl && block.imageUrl !== 'null')
                        html += `<div class="article-builder-block image-block ${wideClass}"><img src="${block.imageUrl}" alt="Image"></div>`;
                    break;
                case 'slider':
                    if (block.images && block.images.length) {
                        html += `<div class="article-slider ${wideClass}" style="border-radius:24px; overflow:hidden; position:relative;">
                            <div class="slider-container-init" style="position:relative; width:100%; overflow:hidden; border-radius:24px;">
                                <div class="slider-track-init" style="display:flex; transition:transform 0.5s ease;">
                                    ${block.images.map(img => `<div class="slider-slide-init" style="flex-shrink:0; width:100%;"><img src="${img}" style="width:100%; display:block; border-radius:24px;" alt="slide"></div>`).join('')}
                                </div>
                                ${block.images.length > 1 ? `
                                <button class="slider-prev-init" style="position:absolute; top:50%; left:16px; transform:translateY(-50%); width:40px; height:40px; background:rgba(255,255,255,0.9); border:none; border-radius:50%; cursor:pointer;">‹</button>
                                <button class="slider-next-init" style="position:absolute; top:50%; right:16px; transform:translateY(-50%); width:40px; height:40px; background:rgba(255,255,255,0.9); border:none; border-radius:50%; cursor:pointer;">›</button>
                                <div class="slider-dots-init" style="position:absolute; bottom:16px; left:50%; transform:translateX(-50%); display:flex; gap:8px;"></div>
                                ` : ''}
                            </div>
                        </div>`;
                    }
                    break;
                case 'list':
                    let itemsHtml = '';
                    block.items.forEach(item => { itemsHtml += `<li>${escapeHtml(item)}</li>`; });
                    html += `<div class="article-builder-block list-block ${wideClass} ${glassClass}">
                        <h3 class="list-title">${escapeHtml(block.title)}</h3>
                        <ul class="styled-list">${itemsHtml}</ul>
                    </div>`;
                    break;
            }
        }
        html += '</div>';
        return html;
    }
    
    generateCSS() {
        if (!this.blocks.length) return '';
        return `<style>
            .article-builder-grid { display: grid; grid-template-columns: repeat(2,1fr); gap:24px; margin:20px 0; }
            .article-builder-block { background:#fff; border:1px solid #e5e7eb; border-radius:20px; padding:24px; line-height:1.6; overflow-wrap:break-word; transition:all 0.3s; }
            .article-builder-block.block-wide { grid-column: span 2; }
            .article-builder-block.image-block { padding:0; background:transparent; border:none; line-height:0; }
            .article-builder-block.image-block img { width:100%; border-radius:20px; display:block; }
            .article-builder-block.glass-frame { background:rgba(255,255,255,0.2); backdrop-filter:blur(12px); border:1px solid rgba(255,255,255,0.3); box-shadow:0 8px 32px rgba(0,0,0,0.1); }
            .list-block { background:#fff; }
            .list-title { margin:0 0 16px; font-size:1.5rem; font-weight:600; }
            .styled-list { margin:0; padding-left:0; list-style:none; }
            .styled-list li { margin:8px 0; padding:8px 12px; background:#f9fafb; border-radius:12px; transition:0.2s; }
            .styled-list li:hover { background:#f3f4f6; transform:translateX(4px); }
            .slider-container-init { position:relative; overflow:hidden; border-radius:24px; }
            .slider-track-init { display:flex; transition:transform 0.5s cubic-bezier(0.4,0,0.2,1); }
            .slider-slide-init { flex-shrink:0; width:100%; }
            .slider-prev-init, .slider-next-init { transition:0.2s; }
            .slider-prev-init:hover, .slider-next-init:hover { transform:translateY(-50%) scale(1.1); background:#fff; }
            @media (max-width:768px) { .article-builder-grid { grid-template-columns:1fr; } .article-builder-block.block-wide { grid-column:span 1; } }
        </style>`;
    }
}

// ===== ГЛОБАЛЬНЫЕ ФУНКЦИИ =====
function openArticleBuilder() {
    const modal = document.getElementById('articleBuilderModal');
    if (modal) {
        modal.classList.add('active');
        const contentField = document.getElementById('content');
        const existingContent = contentField ? contentField.value : null;
        if (window.isNewArticle) {
            localStorage.removeItem('articleBlocks');
            articleBuilder = new ArticleBuilder(null);
        } else {
            articleBuilder = new ArticleBuilder(existingContent);
        }
        articleBuilder.render();
    }
}

function closeArticleBuilder() { document.getElementById('articleBuilderModal')?.classList.remove('active'); }
function addTextBlock() { if (!articleBuilder) articleBuilder = new ArticleBuilder(); articleBuilder.addBlock('text'); }
function addImageBlock() { if (!articleBuilder) articleBuilder = new ArticleBuilder(); articleBuilder.addBlock('image'); }
function addSliderBlock() { if (!articleBuilder) articleBuilder = new ArticleBuilder(); articleBuilder.addBlock('slider'); }
function addListBlock() { if (!articleBuilder) articleBuilder = new ArticleBuilder(); articleBuilder.addBlock('list'); }
function clearAllBlocks() { if (articleBuilder) articleBuilder.clearAll(); }

function generateAndSave() {
    if (!articleBuilder) return;
    const contentField = document.getElementById('content');
    if (contentField) {
        const html = articleBuilder.generateHTML();
        const css = articleBuilder.generateCSS();
        const js = `<script>
(function initSliders() {
    document.querySelectorAll('.slider-container-init').forEach(container => {
        let idx = 0;
        const track = container.querySelector('.slider-track-init');
        const slides = container.querySelectorAll('.slider-slide-init');
        const prev = container.querySelector('.slider-prev-init');
        const next = container.querySelector('.slider-next-init');
        const dotsDiv = container.querySelector('.slider-dots-init');
        if (!track || !slides.length) return;
        const total = slides.length;
        const goTo = (i) => { idx = (i+total)%total; track.style.transform = 'translateX(-'+(idx*100)+'%)'; if(dotsDiv) Array.from(dotsDiv.children).forEach((d,ii)=>d.style.background = ii===idx ? 'white' : 'rgba(255,255,255,0.5)'); };
        if(prev) prev.onclick = () => goTo(idx-1);
        if(next) next.onclick = () => goTo(idx+1);
        if(dotsDiv) {
            for(let i=0;i<total;i++) {
                const dot = document.createElement('button');
                dot.style.cssText = 'width:8px;height:8px;background:rgba(255,255,255,0.5);border:none;border-radius:50%;cursor:pointer;padding:0;';
                dot.onclick = () => goTo(i);
                dotsDiv.appendChild(dot);
            }
            goTo(0);
        }
    });
})();<\/script>`;
        contentField.value = css + html + js;
        articleBuilder.showNotification('✓ HTML, CSS и JS сгенерированы!', 'success');
        closeArticleBuilder();
    }
}

window.openArticleBuilder = openArticleBuilder;
window.closeArticleBuilder = closeArticleBuilder;
window.addTextBlock = addTextBlock;
window.addImageBlock = addImageBlock;
window.addSliderBlock = addSliderBlock;
window.addListBlock = addListBlock;
window.clearAllBlocks = clearAllBlocks;
window.generateAndSave = generateAndSave;