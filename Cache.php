<?php 

class Cache {
    private $cacheDir = __DIR__ . '/cache/';

    public function __construct() {
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    private function getFilePath($key) {
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

    public function deleteByPrefix($prefix) {
        $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefix);
        $files = glob($this->cacheDir . $safePrefix . '*.cache');
        if ($files) {
            foreach ($files as $file) {
                if (file_exists($file)) unlink($file);
            }
        }
    }

    public function clearAll($includeGitkeep = false) {
        $result = [
            'success' => true,
            'deleted_files' => 0,
            'errors' => 0,
            'protected_files' => 0,
            'message' => ''
        ];
        
        if (!is_dir($this->cacheDir)) {
            $result['message'] = 'Директория кэша не существует';
            return $result;
        }
        
        $files = glob($this->cacheDir . '*');
        
        foreach ($files as $file) {
            if (is_dir($file)) {
                continue;
            }
            
            $filename = basename($file);
            
            if (!$includeGitkeep && $filename === '.gitkeep') {
                $result['protected_files']++;
                continue;
            }
            
            if (unlink($file)) {
                $result['deleted_files']++;
            } else {
                $result['errors']++;
                error_log("Не удалось удалить файл кэша: $file");
            }
        }
        
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

    public function getStats() {
        if (!is_dir($this->cacheDir)) {
            return 0;
        }
        
        $files = array_filter(glob($this->cacheDir . '*'), 'is_file');
        
        return count($files);
    }

}

?>