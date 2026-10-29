<?php

require_once __DIR__ . '/Config.php';

class ConfigController {

    public static function get(): void {
        $cfg = Config::get();
        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode([
            'status' => 'success',
            'service' => 'Kelompok 4 - Assignment Service',
            'config' => $cfg
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function update(array $body): void {
        $updated = Config::update($body);
        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode([
            'status' => 'success',
            'message' => 'Konfigurasi IP / URL Kelompok lain berhasil diperbarui.',
            'config' => $updated
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
