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

    /**
     * ПОЛНАЯ ОЧИСТКА КЭША
     * Удаляет все файлы кэша, но сохраняет .gitkeep
     * 
     * @param bool $includeGitkeep Если true, то удалит и .gitkeep (по умолчанию false)
     * @return array Статистика очистки
     */
    public function clearAll($includeGitkeep = false) {
        $result = [
            'success' => true,
            'deleted_files' => 0,
            'errors' => 0,
            'protected_files' => 0,
            'message' => ''
        ];
        
        // Проверяем существование директории
        if (!is_dir($this->cacheDir)) {
            $result['message'] = 'Директория кэша не существует';
            return $result;
        }
        
        // Получаем все файлы в директории (исключаем поддиректории)
        $files = glob($this->cacheDir . '*');
        
        foreach ($files as $file) {
            // Пропускаем директории
            if (is_dir($file)) {
                continue;
            }
            
            $filename = basename($file);
            
            // Проверяем, нужно ли защитить .gitkeep
            if (!$includeGitkeep && $filename === '.gitkeep') {
                $result['protected_files']++;
                continue;
            }
            
            // Пытаемся удалить файл
            if (unlink($file)) {
                $result['deleted_files']++;
            } else {
                $result['errors']++;
                error_log("Не удалось удалить файл кэша: $file");
            }
        }
        
        // Формируем сообщение
        $messages = [];
        if ($result['deleted_files'] > 0) {
            $messages[] = "Удалено файлов: {$result['deleted_files']}";
        }
        if ($result['protected_files'] > 0) {
            $messages[] = "Защищено файлов: {$result['protected_files']}";
        }
        if ($result['errors'] > 0) {
            $messages[] = "Ошибок: {$result['errors']}";
        }
        
        $result['message'] = implode(', ', $messages) ?: 'Нет файлов для удаления';
        
        return $result;
    }

    /**
     * ПОЛУЧИТЬ КОЛИЧЕСТВО ФАЙЛОВ В КЭШЕ
     * 
     * @return int Количество файлов
     */
    public function getStats() {
        // Проверяем существование директории
        if (!is_dir($this->cacheDir)) {
            return 0;
        }
        
        // Получаем все файлы (исключая директории)
        $files = array_filter(glob($this->cacheDir . '*'), 'is_file');
        
        // Возвращаем количество файлов
        return count($files);
    }

}

?>