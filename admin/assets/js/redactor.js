// ===== РЕДАКТОР СТАТЕЙ =====

let articleBuilder = null;

class ArticleBuilder {
    constructor(existingContent = null) {
        this.blocks = [];
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
        
        // Поиск слайдеров
        const sliders = tempDiv.querySelectorAll('.article-slider');
        if (sliders.length > 0) {
            sliders.forEach(slider => {
                const images = [];
                const imgElements = slider.querySelectorAll('img');
                imgElements.forEach(img => {
                    if (img.src) images.push(img.src);
                });
                this.blocks.push({
                    id: Date.now() + Math.random(),
                    type: 'slider',
                    images: images,
                    glassFrame: false,
                    wide: slider.classList.contains('block-wide')
                });
            });
        }
        
        const blockSelectors = [
            '.article-builder-block',
            '.generated-block',
            '.builder-block-content'
        ];
        
        let foundBlocks = [];
        for (const selector of blockSelectors) {
            foundBlocks = tempDiv.querySelectorAll(selector);
            if (foundBlocks.length > 0) break;
        }
        
        if (foundBlocks.length > 0) {
            foundBlocks.forEach(block => {
                const img = block.querySelector('img');
                const hasGlass = block.classList.contains('glass-frame');
                const isWide = block.classList.contains('block-wide');
                
                if (img && img.src) {
                    this.blocks.push({
                        id: Date.now() + Math.random(),
                        type: 'image',
                        content: null,
                        imageUrl: img.src,
                        glassFrame: hasGlass,
                        wide: isWide
                    });
                } else {
                    this.blocks.push({
                        id: Date.now() + Math.random(),
                        type: 'text',
                        content: block.innerHTML,
                        imageUrl: null,
                        glassFrame: hasGlass,
                        wide: isWide
                    });
                }
            });
        } else if (tempDiv.innerHTML.trim() && !tempDiv.innerHTML.includes('article-builder')) {
            this.blocks.push({
                id: Date.now(),
                type: 'text',
                content: tempDiv.innerHTML,
                imageUrl: null,
                glassFrame: false,
                wide: false
            });
        }
        
        this.save();
    }
    
    save() {
        localStorage.setItem('articleBlocks', JSON.stringify(this.blocks));
    }
    
    addBlock(type) {
        const newBlock = {
            id: Date.now(),
            type: type,
            content: type === 'text' ? '<p>Введите текст...</p>' : null,
            imageUrl: null,
            images: type === 'slider' ? [] : null,
            glassFrame: false,
            wide: false
        };
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
        if (block) {
            block.content = content;
            this.save();
        }
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
    
    removeSliderImage(id, index) {
        const block = this.blocks.find(b => b.id === id);
        if (block && block.type === 'slider') {
            block.images.splice(index, 1);
            this.save();
            this.render();
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
            if (!file) {
                reject('Неверный файл');
                return;
            }
            
            if (file.type === 'image/svg+xml') {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.onerror = () => reject('Ошибка чтения SVG');
                reader.readAsDataURL(file);
                return;
            }
            
            if (!file.type.startsWith('image/')) {
                reject('Неверный формат файла');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    
                    const maxWidth = 800;
                    const maxHeight = 600;
                    
                    if (width > maxWidth) {
                        height = (height * maxWidth) / width;
                        width = maxWidth;
                    }
                    if (height > maxHeight) {
                        width = (width * maxHeight) / height;
                        height = maxHeight;
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    
                    if (file.type === 'image/png') {
                        ctx.clearRect(0, 0, width, height);
                    }
                    
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    const format = file.type === 'image/png' ? 'image/png' : 'image/jpeg';
                    const quality = file.type === 'image/png' ? 1 : 0.7;
                    const base64 = canvas.toDataURL(format, quality);
                    resolve(base64);
                };
                img.onerror = () => reject('Ошибка загрузки изображения');
                img.src = e.target.result;
            };
            reader.onerror = () => reject('Ошибка чтения файла');
            reader.readAsDataURL(file);
        });
    }
    
    async handleImageUpload(blockId, file, isSlider = false) {
        if (!file) {
            this.showNotification('Пожалуйста, выберите изображение', 'error');
            return;
        }
        
        this.showNotification('Загрузка изображения...', 'info');
        
        try {
            const base64 = await this.compressImage(file);
            if (isSlider) {
                this.addSliderImage(blockId, base64);
            } else {
                this.updateImage(blockId, base64);
            }
            this.showNotification('Изображение успешно загружено!', 'success');
        } catch(error) {
            console.error('Ошибка загрузки:', error);
            this.showNotification('Ошибка загрузки изображения', 'error');
        }
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
            color: white;
            border-radius: 8px;
            z-index: 10001;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 300px;
            font-size: 14px;
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);
    }
    
    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    handleDrop(e, blockId, isSlider = false) {
        e.preventDefault();
        e.stopPropagation();
        
        const files = e.dataTransfer.files;
        if (files && files.length > 0) {
            this.handleImageUpload(blockId, files[0], isSlider);
        }
    }
    
    selectImageFile(blockId, isSlider = false) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = (e) => {
            if (e.target.files && e.target.files[0]) {
                this.handleImageUpload(blockId, e.target.files[0], isSlider);
            }
        };
        input.click();
    }
    
    render() {
        const grid = document.getElementById('blocksGrid');
        if (!grid) return;
        
        grid.innerHTML = '';
        
        if (this.blocks.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: span 2; text-align: center; padding: 60px; color: #6b7280; background: #f9fafb; border-radius: 12px; border: 2px dashed #e5e7eb;">
                    <i class="fas fa-plus-circle" style="font-size: 48px; margin-bottom: 16px; color: #3b82f6;"></i>
                    <p>Нет блоков. Нажмите "Текстовый блок", "Блок изображения" или "Слайдер"</p>
                </div>
            `;
            return;
        }
        
        this.blocks.forEach((block, index) => {
            const blockDiv = document.createElement('div');
            blockDiv.className = `builder-block ${block.wide ? 'block-wide' : ''}`;
            blockDiv.setAttribute('data-block-id', block.id);
            blockDiv.setAttribute('data-index', index);
            blockDiv.draggable = true;
            blockDiv.style.position = 'relative';
            blockDiv.style.borderRadius = '24px';
            blockDiv.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
            blockDiv.style.transform = 'scale(1)';
            
            if (block.wide) {
                blockDiv.style.gridColumn = 'span 2';
            }
            
            if (block.type === 'slider') {
                blockDiv.style.padding = '0';
                blockDiv.style.background = 'transparent';
                blockDiv.style.border = 'none';
                blockDiv.style.minHeight = 'auto';
                blockDiv.style.display = 'block';
            } else if (block.type === 'image') {
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
                blockDiv.style.background = 'rgba(255, 255, 255, 0.2)';
                blockDiv.style.backdropFilter = 'blur(12px)';
                blockDiv.style.border = '1px solid rgba(255, 255, 255, 0.3)';
                blockDiv.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.1)';
            } else if (block.type === 'text') {
                blockDiv.style.background = 'white';
                blockDiv.style.border = '1px solid #e5e7eb';
                blockDiv.style.boxShadow = 'none';
            }
            
            // Drag handle
            const dragHandle = document.createElement('div');
            dragHandle.className = 'builder-block__drag-handle';
            dragHandle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
            dragHandle.style.cssText = 'position: absolute; left: -12px; top: 50%; transform: translateY(-50%); width: 28px; height: 56px; background: #e5e7eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: grab; color: #6b7280; z-index: 20; transition: all 0.2s;';
            
            // Delete button
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'builder-block__delete';
            deleteBtn.innerHTML = '×';
            deleteBtn.style.cssText = 'position: absolute; top: -10px; right: -10px; width: 28px; height: 28px; background: #ef4444; color: white; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; z-index: 20; transition: all 0.2s;';
            deleteBtn.onclick = (e) => {
                e.stopPropagation();
                this.deleteBlock(block.id);
            };
            
            blockDiv.appendChild(dragHandle);
            blockDiv.appendChild(deleteBtn);
            
            if (block.type === 'text') {
                const controlsContainer = document.createElement('div');
                controlsContainer.style.cssText = 'position: absolute; bottom: 10px; left: -12px; display: flex; flex-direction: column; gap: 8px; z-index: 20;';
                
                const glassCheckbox = document.createElement('div');
                glassCheckbox.style.cssText = 'background: #f3f4f6; padding: 6px 10px; border-radius: 20px; font-size: 12px; display: flex; align-items: center; gap: 6px; cursor: pointer;';
                glassCheckbox.innerHTML = `
                    <input type="checkbox" id="glass-${block.id}" ${block.glassFrame ? 'checked' : ''} style="margin: 0; cursor: pointer;">
                    <label style="cursor: pointer; font-size: 11px;">✨ Стекло</label>
                `;
                glassCheckbox.querySelector('input').onchange = (e) => {
                    e.stopPropagation();
                    this.toggleGlassFrame(block.id);
                };
                
                const wideToggle = document.createElement('div');
                wideToggle.style.cssText = 'background: #f3f4f6; padding: 6px 10px; border-radius: 20px; font-size: 12px; display: flex; align-items: center; gap: 6px; cursor: pointer;';
                wideToggle.innerHTML = `
                    <input type="checkbox" id="wide-${block.id}" ${block.wide ? 'checked' : ''} style="margin: 0; cursor: pointer;">
                    <label style="cursor: pointer; font-size: 11px;">📐 На всю ширину</label>
                `;
                wideToggle.querySelector('input').onchange = (e) => {
                    e.stopPropagation();
                    this.toggleWide(block.id);
                };
                
                controlsContainer.appendChild(glassCheckbox);
                controlsContainer.appendChild(wideToggle);
                blockDiv.appendChild(controlsContainer);
                
                const toolbar = document.createElement('div');
                toolbar.style.cssText = 'background: rgba(249, 250, 251, 0.95); backdropFilter: blur(8px); border: 1px solid #e5e7eb; border-radius: 12px; padding: 8px; margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 8px;';
                toolbar.innerHTML = `
                    <button type="button" data-cmd="bold" style="padding: 6px 14px; border: 1px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer;"><b>B</b></button>
                    <button type="button" data-cmd="italic" style="padding: 6px 14px; border: 1px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer;"><i>I</i></button>
                    <button type="button" data-cmd="underline" style="padding: 6px 14px; border: 1px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer;"><u>U</u></button>
                    <button type="button" data-cmd="h1" style="padding: 6px 14px; border: 1px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer;">H1</button>
                    <button type="button" data-cmd="h2" style="padding: 6px 14px; border: 1px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer;">H2</button>
                    <button type="button" data-cmd="h3" style="padding: 6px 14px; border: 1px solid #d1d5db; background: white; border-radius: 8px; cursor: pointer;">H3</button>
                `;
                
                toolbar.querySelectorAll('button').forEach(btn => {
                    btn.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const cmd = btn.getAttribute('data-cmd');
                        if (cmd === 'h1' || cmd === 'h2' || cmd === 'h3') {
                            this.formatText(block.id, 'formatBlock', cmd);
                        } else {
                            this.formatText(block.id, cmd);
                        }
                    };
                });
                
                const editable = document.createElement('div');
                editable.setAttribute('contenteditable', 'true');
                editable.className = 'builder-editable';
                editable.style.cssText = 'outline: none; flex: 1; min-height: 120px; font-size: 16px; line-height: 1.6; word-wrap: break-word; overflow-wrap: break-word; white-space: pre-wrap;';
                editable.innerHTML = block.content || '<p>Введите текст...</p>';
                editable.oninput = (e) => {
                    this.updateContent(block.id, editable.innerHTML);
                };
                
                blockDiv.appendChild(toolbar);
                blockDiv.appendChild(editable);
                
            } else if (block.type === 'image') {
                const controlsContainer = document.createElement('div');
                controlsContainer.style.cssText = 'position: absolute; bottom: 10px; left: -12px; display: flex; flex-direction: column; gap: 8px; z-index: 20;';
                
                const wideToggle = document.createElement('div');
                wideToggle.style.cssText = 'background: rgba(0,0,0,0.6); padding: 6px 10px; border-radius: 20px; font-size: 12px; display: flex; align-items: center; gap: 6px; cursor: pointer; color: white;';
                wideToggle.innerHTML = `
                    <input type="checkbox" id="wide-img-${block.id}" ${block.wide ? 'checked' : ''} style="margin: 0; cursor: pointer;">
                    <label style="cursor: pointer; font-size: 11px;">📐 На всю ширину</label>
                `;
                wideToggle.querySelector('input').onchange = (e) => {
                    e.stopPropagation();
                    this.toggleWide(block.id);
                };
                
                controlsContainer.appendChild(wideToggle);
                blockDiv.appendChild(controlsContainer);
                
                const imageContainer = document.createElement('div');
                imageContainer.style.cssText = 'text-align: center; min-height: 200px; display: flex; align-items: center; justify-content: center; background: transparent; border-radius: 20px; position: relative; flex-direction: column;';
                
                imageContainer.ondragover = (e) => this.handleDragOver(e);
                imageContainer.ondrop = (e) => this.handleDrop(e, block.id, false);
                
                if (block.imageUrl && block.imageUrl !== 'null' && block.imageUrl !== 'undefined') {
                    const img = document.createElement('img');
                    img.src = block.imageUrl;
                    img.style.cssText = 'width: 100%; height: auto; border-radius: 20px; display: block; background: transparent;';
                    imageContainer.appendChild(img);
                    
                    const buttonContainer = document.createElement('div');
                    buttonContainer.style.cssText = 'position: absolute; bottom: 10px; right: 10px; display: flex; gap: 8px; z-index: 15;';
                    
                    const changeBtn = document.createElement('button');
                    changeBtn.innerHTML = '🖼 Заменить';
                    changeBtn.style.cssText = 'background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 8px; padding: 6px 12px; cursor: pointer; font-size: 12px;';
                    changeBtn.onclick = (e) => {
                        e.stopPropagation();
                        this.selectImageFile(block.id, false);
                    };
                    
                    const deleteImgBtn = document.createElement('button');
                    deleteImgBtn.innerHTML = '🗑 Удалить';
                    deleteImgBtn.style.cssText = 'background: rgba(0,0,0,0.7); color: white; border: none; border-radius: 8px; padding: 6px 12px; cursor: pointer; font-size: 12px;';
                    deleteImgBtn.onclick = (e) => {
                        e.stopPropagation();
                        this.updateImage(block.id, null);
                    };
                    
                    buttonContainer.appendChild(changeBtn);
                    buttonContainer.appendChild(deleteImgBtn);
                    imageContainer.appendChild(buttonContainer);
                } else {
                    const uploadBtn = document.createElement('button');
                    uploadBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Выбрать изображение';
                    uploadBtn.style.cssText = 'background: #3b82f6; color: white; border: none; border-radius: 12px; padding: 12px 24px; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 8px;';
                    uploadBtn.onclick = (e) => {
                        e.stopPropagation();
                        this.selectImageFile(block.id, false);
                    };
                    
                    const dragText = document.createElement('p');
                    dragText.innerHTML = 'Или перетащите изображение сюда';
                    dragText.style.cssText = 'color: #6b7280; margin-top: 10px; font-size: 12px;';
                    
                    imageContainer.appendChild(uploadBtn);
                    imageContainer.appendChild(dragText);
                }
                
                blockDiv.appendChild(imageContainer);
                
            } else if (block.type === 'slider') {
                const controlsContainer = document.createElement('div');
                controlsContainer.style.cssText = 'position: absolute; bottom: 10px; left: -12px; display: flex; flex-direction: column; gap: 8px; z-index: 20;';
                
                const wideToggle = document.createElement('div');
                wideToggle.style.cssText = 'background: rgba(0,0,0,0.6); padding: 6px 10px; border-radius: 20px; font-size: 12px; display: flex; align-items: center; gap: 6px; cursor: pointer; color: white;';
                wideToggle.innerHTML = `
                    <input type="checkbox" id="wide-slider-${block.id}" ${block.wide ? 'checked' : ''} style="margin: 0; cursor: pointer;">
                    <label style="cursor: pointer; font-size: 11px;">📐 На всю ширину</label>
                `;
                wideToggle.querySelector('input').onchange = (e) => {
                    e.stopPropagation();
                    this.toggleWide(block.id);
                };
                
                controlsContainer.appendChild(wideToggle);
                blockDiv.appendChild(controlsContainer);
                
                const sliderContainer = document.createElement('div');
                sliderContainer.className = 'builder-slider-block';
                sliderContainer.style.cssText = 'position: relative; border-radius: 24px; overflow: hidden; background: #f9fafb;';
                
                if (!block.images || block.images.length === 0) {
                    // Режим загрузки изображений
                    const uploadArea = document.createElement('div');
                    uploadArea.className = 'slider-upload-area';
                    uploadArea.innerHTML = `
                        <i class="fas fa-images" style="font-size: 48px; color: #3b82f6;"></i>
                        <h3 style="margin: 0;">Создайте слайдер</h3>
                        <p style="margin: 0; color: #6b7280;">Загрузите от 2 до 10 изображений</p>
                        <button class="builder-btn builder-btn-primary" style="margin-top: 16px;">
                            <i class="fas fa-plus"></i> Добавить изображения
                        </button>
                        <p style="font-size: 12px; color: #9ca3af;">Или перетащите файлы сюда</p>
                    `;
                    
                    const addBtn = uploadArea.querySelector('button');
                    addBtn.onclick = (e) => {
                        e.stopPropagation();
                        this.selectImageFile(block.id, true);
                    };
                    
                    uploadArea.ondragover = (e) => this.handleDragOver(e);
                    uploadArea.ondrop = (e) => this.handleDrop(e, block.id, true);
                    
                    sliderContainer.appendChild(uploadArea);
                } else {
                    // Отображение слайдера
                    let currentIndex = 0;
                    
                    const sliderInner = document.createElement('div');
                    sliderInner.className = 'slider-container';
                    sliderInner.style.cssText = 'position: relative; width: 100%; overflow: hidden; border-radius: 24px;';
                    
                    const track = document.createElement('div');
                    track.className = 'slider-track';
                    track.style.cssText = 'display: flex; transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);';
                    
                    block.images.forEach((imgSrc, idx) => {
                        const slide = document.createElement('div');
                        slide.className = 'slider-slide';
                        slide.style.cssText = 'flex-shrink: 0; width: 100%;';
                        const img = document.createElement('img');
                        img.src = imgSrc;
                        img.style.cssText = 'width: 100%; height: auto; display: block; background: transparent;';
                        slide.appendChild(img);
                        track.appendChild(slide);
                    });
                    
                    sliderInner.appendChild(track);
                    
                    // Кнопки навигации
                    if (block.images.length > 1) {
                        const prevBtn = document.createElement('button');
                        prevBtn.className = 'slider-btn slider-prev';
                        prevBtn.innerHTML = '‹';
                        prevBtn.onclick = () => {
                            currentIndex = (currentIndex - 1 + block.images.length) % block.images.length;
                            track.style.transform = `translateX(-${currentIndex * 100}%)`;
                            updateDots();
                        };
                        
                        const nextBtn = document.createElement('button');
                        nextBtn.className = 'slider-btn slider-next';
                        nextBtn.innerHTML = '›';
                        nextBtn.onclick = () => {
                            currentIndex = (currentIndex + 1) % block.images.length;
                            track.style.transform = `translateX(-${currentIndex * 100}%)`;
                            updateDots();
                        };
                        
                        sliderInner.appendChild(prevBtn);
                        sliderInner.appendChild(nextBtn);
                        
                        // Точки навигации
                        const dots = document.createElement('div');
                        dots.className = 'slider-dots';
                        
                        const updateDots = () => {
                            dots.innerHTML = '';
                            block.images.forEach((_, idx) => {
                                const dot = document.createElement('button');
                                dot.className = `slider-dot ${idx === currentIndex ? 'active' : ''}`;
                                dot.onclick = () => {
                                    currentIndex = idx;
                                    track.style.transform = `translateX(-${currentIndex * 100}%)`;
                                    updateDots();
                                };
                                dots.appendChild(dot);
                            });
                        };
                        
                        updateDots();
                        sliderInner.appendChild(dots);
                    }
                    
                    // Индикатор количества изображений
                    const countIndicator = document.createElement('div');
                    countIndicator.className = 'image-count-indicator';
                    countIndicator.innerHTML = `${block.images.length} 📷`;
                    sliderInner.appendChild(countIndicator);
                    
                    // Кнопка добавления изображения
                    const addImageBtn = document.createElement('button');
                    addImageBtn.innerHTML = '+ Добавить фото';
                    addImageBtn.style.cssText = 'position: absolute; bottom: 16px; left: 16px; background: rgba(0,0,0,0.6); color: white; border: none; border-radius: 20px; padding: 6px 12px; cursor: pointer; font-size: 12px; z-index: 10;';
                    addImageBtn.onclick = (e) => {
                        e.stopPropagation();
                        this.selectImageFile(block.id, true);
                    };
                    sliderInner.appendChild(addImageBtn);
                    
                    // Миниатюры для управления
                    const thumbnailsContainer = document.createElement('div');
                    thumbnailsContainer.style.cssText = 'position: absolute; bottom: 16px; right: 16px; display: flex; gap: 8px; z-index: 10; background: rgba(0,0,0,0.5); padding: 8px; border-radius: 12px; backdrop-filter: blur(4px);';
                    
                    block.images.forEach((imgSrc, idx) => {
                        const thumb = document.createElement('div');
                        thumb.style.cssText = 'width: 40px; height: 40px; border-radius: 8px; overflow: hidden; cursor: pointer; border: 2px solid transparent; transition: all 0.2s;';
                        if (idx === currentIndex) thumb.style.borderColor = '#3b82f6';
                        const thumbImg = document.createElement('img');
                        thumbImg.src = imgSrc;
                        thumbImg.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                        thumb.appendChild(thumbImg);
                        thumb.onclick = () => {
                            currentIndex = idx;
                            track.style.transform = `translateX(-${currentIndex * 100}%)`;
                            document.querySelectorAll('.slider-dot').forEach((dot, i) => {
                                dot.classList.toggle('active', i === currentIndex);
                            });
                        };
                        thumbnailsContainer.appendChild(thumb);
                    });
                    
                    sliderInner.appendChild(thumbnailsContainer);
                    
                    sliderContainer.appendChild(sliderInner);
                }
                
                blockDiv.appendChild(sliderContainer);
            }
            
            // Drag & Drop для перемещения блоков
            blockDiv.ondragstart = (e) => {
                e.dataTransfer.setData('text/plain', index);
                blockDiv.style.opacity = '0.4';
                blockDiv.style.transform = 'scale(0.98)';
            };
            
            blockDiv.ondragover = (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                blockDiv.style.transform = 'scale(1.01)';
                blockDiv.style.boxShadow = '0 0 0 2px #3b82f6';
            };
            
            blockDiv.ondragleave = (e) => {
                blockDiv.style.transform = 'scale(1)';
                blockDiv.style.boxShadow = 'none';
            };
            
            blockDiv.ondrop = (e) => {
                e.preventDefault();
                blockDiv.style.transform = 'scale(1)';
                blockDiv.style.boxShadow = 'none';
                
                const fromIndex = parseInt(e.dataTransfer.getData('text/plain'));
                const toIndex = index;
                
                if (!isNaN(fromIndex) && fromIndex !== toIndex) {
                    const moved = this.blocks[fromIndex];
                    this.blocks.splice(fromIndex, 1);
                    this.blocks.splice(toIndex, 0, moved);
                    this.save();
                    this.render();
                }
            };
            
            blockDiv.ondragend = () => {
                blockDiv.style.opacity = '';
                blockDiv.style.transform = 'scale(1)';
                blockDiv.style.boxShadow = 'none';
            };
            
            grid.appendChild(blockDiv);
        });
    }
    
    generateHTML() {
        if (this.blocks.length === 0) {
            return '<div class="article-builder-grid"><div class="article-builder-block"><p>Статья пуста</p></div></div>';
        }
        
        let html = '<div class="article-builder-grid">';
        for (const block of this.blocks) {
            const glassClass = (block.type === 'text' && block.glassFrame) ? 'glass-frame' : '';
            const wideClass = block.wide ? 'block-wide' : '';
            
            if (block.type === 'text') {
                let content = block.content || '';
                html += `<div class="article-builder-block ${glassClass} ${wideClass}">${content}</div>`;
            } else if (block.type === 'image' && block.imageUrl && block.imageUrl !== 'null' && block.imageUrl !== 'undefined') {
                html += `<div class="article-builder-block image-block ${wideClass}">
                    <img src="${block.imageUrl}" alt="Image">
                </div>`;
            } else if (block.type === 'slider' && block.images && block.images.length > 0) {
                html += `<div class="article-slider ${wideClass}" style="border-radius: 24px; overflow: hidden; position: relative;">
                    <div class="slider-container-init" style="position: relative; width: 100%; overflow: hidden; border-radius: 24px;">
                        <div class="slider-track-init" style="display: flex; transition: transform 0.5s ease;">
                            ${block.images.map(img => `
                                <div class="slider-slide-init" style="flex-shrink: 0; width: 100%;">
                                    <img src="${img}" style="width: 100%; height: auto; display: block; border-radius: 24px;" alt="Slide">
                                </div>
                            `).join('')}
                        </div>
                        ${block.images.length > 1 ? `
                        <button class="slider-prev-init" style="position: absolute; top: 50%; left: 16px; transform: translateY(-50%); width: 40px; height: 40px; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; cursor: pointer; font-size: 20px;">‹</button>
                        <button class="slider-next-init" style="position: absolute; top: 50%; right: 16px; transform: translateY(-50%); width: 40px; height: 40px; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; cursor: pointer; font-size: 20px;">›</button>
                        <div class="slider-dots-init" style="position: absolute; bottom: 16px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px;"></div>
                        ` : ''}
                    </div>
                </div>`;
            }
        }
        html += '</div>';
        
        return html;
    }
    
    generateCSS() {
        if (this.blocks.length === 0) return '';
        
        return `<style>
            .article-builder-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
                margin: 20px 0;
                transition: all 0.3s ease;
            }
            
            .article-builder-block {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 20px;
                padding: 24px;
                line-height: 1.6;
                overflow-wrap: break-word;
                word-wrap: break-word;
                white-space: normal;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .article-builder-block.block-wide {
                grid-column: span 2;
            }
            
            .article-builder-block.image-block {
                padding: 0;
                background: transparent;
                border: none;
                overflow: hidden;
                line-height: 0;
            }
            
            .article-builder-block.image-block img {
                width: 100%;
                height: auto;
                border-radius: 20px;
                display: block;
                margin: 0;
                background: transparent;
            }
            
            .article-builder-block.glass-frame {
                background: rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.3);
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            }
            
            /* Стили для слайдера в сгенерированном HTML */
            .slider-container-init {
                position: relative;
                width: 100%;
                overflow: hidden;
                border-radius: 24px;
            }
            
            .slider-track-init {
                display: flex;
                transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .slider-slide-init {
                flex-shrink: 0;
                width: 100%;
            }
            
            .slider-slide-init img {
                width: 100%;
                height: auto;
                display: block;
            }
            
            .slider-prev-init, .slider-next-init {
                transition: all 0.2s;
            }
            
            .slider-prev-init:hover, .slider-next-init:hover {
                transform: translateY(-50%) scale(1.1);
                background: white;
            }
            
            .slider-dots-init {
                position: absolute;
                bottom: 16px;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                gap: 8px;
            }
            
            .article-builder-block p {
                margin: 0 0 1em 0;
                white-space: normal;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            
            .article-builder-block p:last-child {
                margin-bottom: 0;
            }
            
            .article-builder-block h1, 
            .article-builder-block h2, 
            .article-builder-block h3 {
                margin-top: 0;
                margin-bottom: 0.5em;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            
            @media (max-width: 768px) {
                .article-builder-grid {
                    grid-template-columns: 1fr;
                    gap: 16px;
                }
                .article-builder-block {
                    padding: 16px;
                }
                .article-builder-block.block-wide {
                    grid-column: span 1;
                }
            }
            
            @media (max-width: 480px) {
                .article-builder-grid {
                    gap: 12px;
                }
                .article-builder-block {
                    padding: 12px;
                }
            }
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
        
        if (!articleBuilder) {
            articleBuilder = new ArticleBuilder(existingContent);
        } else {
            articleBuilder.load(existingContent);
            articleBuilder.render();
        }
    }
}

function closeArticleBuilder() {
    const modal = document.getElementById('articleBuilderModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function addTextBlock() {
    if (!articleBuilder) {
        articleBuilder = new ArticleBuilder();
    }
    articleBuilder.addBlock('text');
}

function addImageBlock() {
    if (!articleBuilder) {
        articleBuilder = new ArticleBuilder();
    }
    articleBuilder.addBlock('image');
}

function addSliderBlock() {
    if (!articleBuilder) {
        articleBuilder = new ArticleBuilder();
    }
    articleBuilder.addBlock('slider');
}

function clearAllBlocks() {
    if (articleBuilder) {
        articleBuilder.clearAll();
    }
}

function generateAndSave() {
    if (!articleBuilder) return;
    
    const contentField = document.getElementById('content');
    if (contentField) {
        const html = articleBuilder.generateHTML();
        const css = articleBuilder.generateCSS();
        const js = `
<script>
(function initSliders() {
    document.querySelectorAll('.slider-container-init').forEach(container => {
        let currentIndex = 0;
        const track = container.querySelector('.slider-track-init');
        const slides = container.querySelectorAll('.slider-slide-init');
        const prevBtn = container.querySelector('.slider-prev-init');
        const nextBtn = container.querySelector('.slider-next-init');
        const dotsContainer = container.querySelector('.slider-dots-init');
        
        if (!track || slides.length === 0) return;
        
        const totalSlides = slides.length;
        
        const updateDots = () => {
            if (!dotsContainer) return;
            dotsContainer.innerHTML = '';
            for (let i = 0; i < totalSlides; i++) {
                const dot = document.createElement('button');
                dot.className = 'slider-dot';
                dot.style.cssText = 'width: 8px; height: 8px; background: ' + (i === currentIndex ? 'white' : 'rgba(255,255,255,0.5)') + '; border: none; border-radius: 50%; cursor: pointer; padding: 0; transition: all 0.2s;';
                dot.onclick = () => goToSlide(i);
                dotsContainer.appendChild(dot);
            }
        };
        
        const goToSlide = (index) => {
            currentIndex = index;
            track.style.transform = \`translateX(-\${currentIndex * 100}%)\`;
            if (dotsContainer) {
                const dots = dotsContainer.querySelectorAll('.slider-dot');
                dots.forEach((dot, i) => {
                    dot.style.background = i === currentIndex ? 'white' : 'rgba(255,255,255,0.5)';
                });
            }
        };
        
        if (prevBtn) {
            prevBtn.onclick = () => goToSlide((currentIndex - 1 + totalSlides) % totalSlides);
        }
        if (nextBtn) {
            nextBtn.onclick = () => goToSlide((currentIndex + 1) % totalSlides);
        }
        
        updateDots();
    });
})();
</script>`;
        const fullContent = css + html + js;
        contentField.value = fullContent;
        
        articleBuilder.showNotification('✓ HTML, CSS и JS сгенерированы и сохранены!', 'success');
        closeArticleBuilder();
    }
}



// Регистрируем функции глобально
window.openArticleBuilder = openArticleBuilder;
window.closeArticleBuilder = closeArticleBuilder;
window.addTextBlock = addTextBlock;
window.addImageBlock = addImageBlock;
window.addSliderBlock = addSliderBlock;
window.clearAllBlocks = clearAllBlocks;
window.generateAndSave = generateAndSave;