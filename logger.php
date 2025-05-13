<?php 

class Logger {
    public static function log($data) {
        if(is_array($data)) {
            $data = json_encode($data, JSON_PRETTY_PRINT);
        }
        file_put_contents("log3.txt", $data.PHP_EOL, FILE_APPEND);
    }
}