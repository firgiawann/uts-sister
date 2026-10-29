<?php

require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/ExternalService.php';

class HealthController {

    public static function check(): void {
        $cfg = Config::get();
        $startTime = $cfg['service_start_time'] ?? time();
        $uptime = time() - $startTime;

        // Cek dependensi ke kelompok lain
        $dependencies = ExternalService::checkAllDependencies();

        $response = [
            'status' => 'UP',
            'service' => 'Kelompok 4: Assignment Service',
            'role' => 'Assignment Management Microservice',
            'version' => $cfg['version'] ?? '1.0.0',
            'timestamp' => date('c'),
            'uptime_seconds' => $uptime,
            'uptime_human' => self::formatUptime($uptime),
            'system' => [
                'php_version' => PHP_VERSION,
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'peak_memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB'
            ],
            'dependencies' => $dependencies,
            'notes_for_kelompok_7' => 'Endpoint ini dirancang khusus untuk diperiksa oleh Kelompok 7 (Monitoring Service).'
        ];

        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private static function formatUptime(int $seconds): string {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $sec = $seconds % 60;

        $parts = [];
        if ($days > 0) $parts[] = "{$days}d";
        if ($hours > 0) $parts[] = "{$hours}h";
        if ($minutes > 0) $parts[] = "{$minutes}m";
        $parts[] = "{$sec}s";

        return implode(' ', $parts);
    }
}
