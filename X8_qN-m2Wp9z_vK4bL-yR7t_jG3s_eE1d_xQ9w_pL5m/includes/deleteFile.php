<?php
function deleteImageFromServer($pathFromDb) {
    if (empty($pathFromDb)) {
        return false;
    }

    $fullPath = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . $pathFromDb;

    $fullPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $fullPath);

    if (file_exists($fullPath) && is_file($fullPath)) {
        return unlink($fullPath);
    }

    return false;
}

?>