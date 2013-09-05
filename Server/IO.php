<?php
namespace Server;
class IO {
    public function loadJSON($filename) {
        if(!is_file($filename)) {
            throw new \Exception(__METHOD__ . ': File does not exist "' . $filename . '"');
        }
        $file = file_get_contents($filename);
        return json_decode($file);
    }
    
    public function write($filename, $data) {
        if(!is_file($filename)) {
            throw new \Exception(__METHOD__ . ': File does not exist "' . $filename . '"');
        }
        file_put_contents($filename, $data, FILE_APPEND);
    }
    
    public function create($filename, $overwrite = false) {
        if(!$overwrite && is_file($filename)) {
            throw new \Exception(__METHOD__ . ': File already exists "' . $filename . '"');
        }
        file_put_contents($filename, '');
    }
}
?>
