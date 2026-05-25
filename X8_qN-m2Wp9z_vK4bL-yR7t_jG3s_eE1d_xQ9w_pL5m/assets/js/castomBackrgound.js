document.addEventListener("DOMContentLoaded", function() {
    const sectionsData = window.backendBackgroundSettings || {};

    const sectionSelector = document.getElementById('section_selector');
    const bgTypeSelect = document.getElementById('universal_bg_type');
    const previewBox = document.getElementById('uni_preview_box');
    const previewWrapper = document.getElementById('uni_preview_wrapper'); // Обёртка предпросмотра
    const imagePrompt = document.getElementById('uni_image_prompt');
    const imageThumb = document.getElementById('uni_image_thumb'); 
    
    const colorSolid = document.getElementById('uni_color_1');
    const gradColor1 = document.getElementById('uni_grad_1');
    const gradColor2 = document.getElementById('uni_grad_2');
    const fileInput = document.getElementById('uni_file_input');

    const finalCss = document.getElementById('final_css_output');
    const finalType = document.getElementById('final_bg_type');
    const finalColor1 = document.getElementById('final_color1');
    const finalColor2 = document.getElementById('final_color2');

    function loadSectionSettings() {
        const currentSection = sectionSelector.value;
        const data = sectionsData[currentSection];

        if (!data) return;

        bgTypeSelect.value = data.type || 'solid';

        colorSolid.value = data.c1 || '#ffffff';
        gradColor1.value = data.c1 || '#ffffff';
        gradColor2.value = data.c2 || '#ffffff';

        // Проверяем наличие уже сохранённой картинки
        if (data.img && data.type === 'image') {
            imageThumb.src = '../' + data.img;
            imageThumb.style.display = 'block';
            imagePrompt.innerHTML = `<i class="fas fa-image"></i> Фон загружен. Нажмите для изменения.`;
        } else {
            imageThumb.src = '';
            imageThumb.style.display = 'none';
            imagePrompt.innerHTML = `<i class="fas fa-cloud-upload-alt"></i> Нажмите для загрузки нового фона`;
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

        if (selectedType === 'solid') {
            previewWrapper.style.display = 'block'; // Показываем блок предпросмотра
            cssString = `background: ${colorSolid.value};`;
            previewBox.style.background = colorSolid.value;
            c1_val = colorSolid.value;
        } 
        else if (selectedType === 'gradient') {
            previewWrapper.style.display = 'block'; // Показываем блок предпросмотра
            cssString = `background: linear-gradient(to bottom, ${gradColor1.value}, ${gradColor2.value});`;
            previewBox.style.background = `linear-gradient(to bottom, ${gradColor1.value}, ${gradColor2.value})`;
            c1_val = gradColor1.value;
            c2_val = gradColor2.value;
        } 
        else if (selectedType === 'image') {
            previewWrapper.style.display = 'none'; // СКРЫВАЕМ блок предпросмотра полностью!
            
            let imgPath = (data && data.img) ? data.img : '';
            if (imgPath) {
                cssString = `background: url('../${imgPath}') center/cover no-repeat;`;
            } else {
                cssString = `background: #ffffff;`;
            }
        }

        previewBox.innerText = sectionSelector.options[sectionSelector.selectedIndex].text;

        // Привязываем имена к скрытым полям отправки формы
        finalCss.name = `setting_${currentSection}`;
        finalCss.value = cssString;

        let prefix = currentSection.replace('_background', '');
        
        finalType.name = `setting_${prefix}_bg_type`;
        finalType.value = selectedType;

        finalColor1.name = `setting_${prefix}_bg_color1`;
        finalColor1.value = c1_val;

        finalColor2.name = `setting_${prefix}_bg_color2`;
        finalColor2.value = c2_val;
    }

    sectionSelector.addEventListener('change', loadSectionSettings);
    bgTypeSelect.addEventListener('change', renderUpdates);
    colorSolid.addEventListener('input', renderUpdates);
    gradColor1.addEventListener('input', renderUpdates);
    gradColor2.addEventListener('input', renderUpdates);

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Отрисовываем выбранное изображение прямо внутри drop-zone контейнера
                    imageThumb.src = e.target.result;
                    imageThumb.style.display = 'block';
                    imagePrompt.innerHTML = `<i class="fas fa-check"></i> Файл выбран для этой секции`;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    loadSectionSettings();
});