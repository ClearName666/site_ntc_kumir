<?php
/**
 * Удаляет файл с сервера по пути из базы данных
 *
 * @param string $pathFromDb Путь к файлу (например, 'assets/images/uploads/file.jpg')
 * @return bool Возвращает true в случае успеха, false если файл не найден или не удален
 */
function deleteImageFromServer($pathFromDb) {
    if (empty($pathFromDb)) {
        return false;
    }

    // Определяем корень сайта. 
    // __DIR__ — это папка, где лежит текущий скрипт. 
    // Если скрипт лежит в подпапке, возможно, нужно использовать $_SERVER['DOCUMENT_ROOT']
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $pathFromDb;

    // Нормализуем разделители (важно для Windows/Linux)
    $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);

    // Проверяем, существует ли файл и является ли он именно файлом
    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }

    return false;
}

?>