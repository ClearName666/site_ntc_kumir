document.addEventListener("DOMContentLoaded", function() {
    const sectionsData = window.backendBackgroundSettings || {};

    const sectionSelector = document.getElementById('section_selector');
    const bgTypeSelect = document.getElementById('universal_bg_type');
    const previewBox = document.getElementById('uni_preview_box');
    const previewWrapper = document.getElementById('uni_preview_wrapper');
    const imagePrompt = document.getElementById('uni_image_prompt');
    const imageThumb = document.getElementById('uni_image_thumb'); 
    
    const colorSolid = document.getElementById('uni_color_1');
    const gradColor1 = document.getElementById('uni_grad_1');
    const gradColor2 = document.getElementById('uni_grad_2');
    const fileInput = document.getElementById('uni_file_input');
    
    const textColorInput = document.getElementById('uni_text_color');
    const autoTextColorBtn = document.getElementById('auto_text_color_btn');
    const textColorHex = document.getElementById('text_color_hex');
    const textPreviewHeading = document.getElementById('text_preview_heading');
    const textPreviewText = document.getElementById('text_preview_text');
    const textPreviewLink = document.getElementById('text_preview_link');

    const finalCss = document.getElementById('final_css_output');
    const finalType = document.getElementById('final_bg_type');
    const finalColor1 = document.getElementById('final_color1');
    const finalColor2 = document.getElementById('final_color2');
    const finalTextColor = document.getElementById('final_text_color');

    function getBrightness(hexColor) {
        const hex = hexColor.replace('#', '');
        const r = parseInt(hex.substring(0, 2), 16);
        const g = parseInt(hex.substring(2, 4), 16);
        const b = parseInt(hex.substring(4, 6), 16);
        return (r * 299 + g * 587 + b * 114) / 1000;
    }

    function getContrastColor(bgColor) {
        const brightness = getBrightness(bgColor);
        return brightness > 128 ? '#1a1a1a' : '#ffffff';
    }

    function updateTextPreview(color) {
        if (textPreviewHeading) textPreviewHeading.style.color = color;
        if (textPreviewText) textPreviewText.style.color = color;
        if (textPreviewLink) textPreviewLink.style.color = color;
        if (textColorHex) textColorHex.textContent = color;
    }

    function loadSectionSettings() {
        const currentSection = sectionSelector.value;
        const data = sectionsData[currentSection];
        if (!data) return;

        bgTypeSelect.value = data.type || 'solid';
        colorSolid.value = data.c1 || '#ffffff';
        gradColor1.value = data.c1 || '#ffffff';
        gradColor2.value = data.c2 || '#ffffff';
        
        if (textColorInput && data.textColor && data.textColor !== '') {
            textColorInput.value = data.textColor;
            textColorInput.dataset.manuallySet = 'true';
        } else if (textColorInput) {
            textColorInput.value = getContrastColor(data.c1 || '#ffffff');
            textColorInput.dataset.manuallySet = 'false';
        }

        if (textColorHex && textColorInput) {
            textColorHex.textContent = textColorInput.value;
        }

        if (data.img && data.type === 'image') {
            imageThumb.src = '../' + data.img;
            imageThumb.style.display = 'block';
            imagePrompt.innerHTML = '<i class="fas fa-image"></i> Фон загружен. Нажмите для изменения.';
        } else {
            imageThumb.src = '';
            imageThumb.style.display = 'none';
            imagePrompt.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Нажмите для загрузки нового фона';
        }

        renderUpdates();
    }

    function renderUpdates() {
        const currentSection = sectionSelector.value;
        const selectedType = bgTypeSelect.value;
        const data = sectionsData[currentSection];
        
        document.getElementById('uni_block_solid').style.display = (selectedType === 'solid') ? 'block' : 'none';
        document.getElementById('uni_block_gradient').style.display = (selectedType === 'gradient') ? 'block' : 'none';
        document.getElementById('uni_block_image').style.display = (selectedType === 'image') ? 'block' : 'none';

        let cssString = "";
        let c1_val = colorSolid.value;
        let c2_val = gradColor2.value;
        let currentBgColor = '#ffffff';

        if (selectedType === 'solid') {
            previewWrapper.style.display = 'block';
            cssString = `background: ${colorSolid.value};`;
            previewBox.style.background = colorSolid.value;
            c1_val = colorSolid.value;
            currentBgColor = colorSolid.value;
        } else if (selectedType === 'gradient') {
            previewWrapper.style.display = 'block';
            cssString = `background: linear-gradient(to bottom, ${gradColor1.value}, ${gradColor2.value});`;
            previewBox.style.background = `linear-gradient(to bottom, ${gradColor1.value}, ${gradColor2.value})`;
            c1_val = gradColor1.value;
            c2_val = gradColor2.value;
            currentBgColor = gradColor1.value;
        } else if (selectedType === 'image') {
            previewWrapper.style.display = 'none';
            let imgPath = (data && data.img) ? data.img : '';
            cssString = imgPath ? `background: url('../${imgPath}') center/cover no-repeat;` : `background: #ffffff;`;
            currentBgColor = '#ffffff';
        }

        if (textColorInput && !textColorInput.dataset.manuallySet || textColorInput.dataset.manuallySet === 'false') {
            const autoColor = getContrastColor(currentBgColor);
            textColorInput.value = autoColor;
            if (textColorHex) textColorHex.textContent = autoColor;
        }

        previewBox.innerText = sectionSelector.options[sectionSelector.selectedIndex].text;

        let prefix = currentSection.replace('_background', '');
        
        finalCss.name = `setting_${currentSection}`;
        finalCss.value = cssString;

        finalType.name = `setting_${prefix}_bg_type`;
        finalType.value = selectedType;

        finalColor1.name = `setting_${prefix}_bg_color1`;
        finalColor1.value = c1_val;

        finalColor2.name = `setting_${prefix}_bg_color2`;
        finalColor2.value = c2_val;

        if (finalTextColor && textColorInput) {
            finalTextColor.name = `${prefix}_text_color`;
            finalTextColor.value = textColorInput.value;
        }

        if (textColorInput) {
            updateTextPreview(textColorInput.value);
        }
    }

    if (textColorInput) {
        textColorInput.addEventListener('input', function() {
            this.dataset.manuallySet = 'true';
            updateTextPreview(this.value);
            renderUpdates();
        });
    }

    if (autoTextColorBtn) {
        autoTextColorBtn.addEventListener('click', function() {
            const selectedType = bgTypeSelect.value;
            let bgColor = '#ffffff';
            if (selectedType === 'solid') bgColor = colorSolid.value;
            else if (selectedType === 'gradient') bgColor = gradColor1.value;
            
            const contrastColor = getContrastColor(bgColor);
            textColorInput.value = contrastColor;
            textColorInput.dataset.manuallySet = 'true';
            updateTextPreview(contrastColor);
            renderUpdates();
        });
    }

    sectionSelector.addEventListener('change', function() {
        loadSectionSettings();
    });
    
    bgTypeSelect.addEventListener('change', function() {
        renderUpdates();
    });
    
    colorSolid.addEventListener('input', function() {
        if (textColorInput && (!textColorInput.dataset.manuallySet || textColorInput.dataset.manuallySet === 'false')) {
            const autoColor = getContrastColor(this.value);
            textColorInput.value = autoColor;
            if (textColorHex) textColorHex.textContent = autoColor;
            updateTextPreview(autoColor);
        }
        renderUpdates();
    });
    
    gradColor1.addEventListener('input', function() {
        if (textColorInput && (!textColorInput.dataset.manuallySet || textColorInput.dataset.manuallySet === 'false')) {
            const autoColor = getContrastColor(this.value);
            textColorInput.value = autoColor;
            if (textColorHex) textColorHex.textContent = autoColor;
            updateTextPreview(autoColor);
        }
        renderUpdates();
    });
    
    gradColor2.addEventListener('input', renderUpdates);

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imageThumb.src = e.target.result;
                    imageThumb.style.display = 'block';
                    imagePrompt.innerHTML = '<i class="fas fa-check"></i> Файл выбран для этой секции';
                }
                reader.readAsDataURL(file);
            }
        });
    }

    loadSectionSettings();
});