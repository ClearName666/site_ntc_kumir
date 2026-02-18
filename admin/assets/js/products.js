// Автоматическая генерация слага
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase()
        .replace(/[^\w\u0400-\u04FF\s]/g, '')
        .replace(/\s+/g, '-')
        .replace(/^-+|-+$/g, '');
    document.getElementById('slug').value = slug;
});

// Удаление изображения
function removeImage() {
    if (confirm('Удалить текущее изображение?')) {
        const existingImage = document.querySelector('.existing-image');
        if (existingImage) {
            existingImage.remove();
        }
    }
}

// Управление характеристиками
document.getElementById('add-spec').addEventListener('click', function() {
    const container = document.getElementById('specifications-container');
    const newRow = document.createElement('div');
    newRow.className = 'specification-row row mb-2';
    newRow.innerHTML = `
        <div class="col-md-5">
            <input type="text" name="spec_key[]" class="form-control" placeholder="Название характеристики">
        </div>
        <div class="col-md-5">
            <input type="text" name="spec_value[]" class="form-control" placeholder="Значение">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger remove-spec">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
    
    // Добавляем обработчик удаления
    newRow.querySelector('.remove-spec').addEventListener('click', function() {
        newRow.remove();
    });
});

// Инициализация обработчиков удаления для существующих строк
document.querySelectorAll('.remove-spec').forEach(button => {
    button.addEventListener('click', function() {
        this.closest('.specification-row').remove();
    });
});