<?php 

class Cache {
    private $cacheDir = __DIR__ . '/cache/';

    public function __construct() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    // ВАЖНО: Убираем md5 из имен файлов, чтобы мы могли искать их по префиксу
    private function getFilePath($key) {
        // Заменяем плохие символы в ключе на подчеркивание
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cacheDir . $safeKey . '.cache';
    }

    public function get($key) {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return unserialize(file_get_contents($file));
        }
        return null;
    }

    public function set($key, $data) {
        $file = $this->getFilePath($key);
        return file_put_contents($file, serialize($data));
    }

    public function delete($key) {
        $file = $this->getFilePath($key);
        if (file_exists($file)) unlink($file);
    }

    // Новый метод: удаляет все файлы, которые начинаются на $prefix
    public function deleteByPrefix($prefix) {
        $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefix);
        $files = glob($this->cacheDir . $safePrefix . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                if (file_exists($file)) unlink($file);
            }
        }
    }
}

?>