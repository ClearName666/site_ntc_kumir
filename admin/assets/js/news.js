// Автоматическая генерация слага
document.getElementById('title').addEventListener('input', function() {
    const title = this.value;
    const slug = title.toLowerCase()
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
        document.getElementById('image').required = true;
    }
}