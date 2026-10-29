<?php

class Config {
    private static string $configFile = __DIR__ . '/../config.json';
    private static array $cachedConfig = [];

    public static function get(): array {
        if (file_exists(self::$configFile)) {
            $raw = file_get_contents(self::$configFile);
            $data = json_decode($raw, true);
            if (is_array($data)) {
                return $data;
            }
        }

        static $runtimeStart = null;
        if ($runtimeStart === null) {
            $runtimeStart = time();
        }

        self::$cachedConfig = [
            'port' => 4000,
            'service_name' => 'Kelompok 4 - Assignment Service',
            'version' => '1.0.0',
            'kelompok_1_gateway_url' => 'http://localhost:8080',
            'kelompok_3_course_url' => 'http://localhost:3000',
            'kelompok_5_submission_url' => 'http://localhost:5000',
            'use_mock_fallback' => true,
            'service_start_time' => $runtimeStart
        ];
        return self::$cachedConfig;
    }

    public static function update(array $newData): array {
        $current = self::get();
        foreach ($newData as $key => $value) {
            if (array_key_exists($key, $current)) {
                $current[$key] = $value;
            }
        }
        self::$cachedConfig = $current;
        file_put_contents(self::$configFile, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $current;
    }
}
