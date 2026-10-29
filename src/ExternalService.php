<?php

class ExternalService {

    /**
     * Helper untuk HTTP GET dengan timeout aman
     */
    public static function httpGet(string $url, int $timeoutSeconds = 2): array {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeoutSeconds,
                'ignore_errors' => true,
                'header' => "Accept: application/json\r\nUser-Agent: Kelompok4-AssignmentService/1.0\r\n"
            ]
        ]);

        $start = microtime(true);
        $result = @file_get_contents($url, false, $context);
        $latency = round((microtime(true) - $start) * 1000, 2);

        if ($result === false) {
            return [
                'success' => false,
                'latency_ms' => $latency,
                'status_code' => 0,
                'error' => error_get_last()['message'] ?? 'Connection refused or timeout',
                'data' => null
            ];
        }

        $statusCode = 200;
        $headers = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? []);
        if (!empty($headers) && is_array($headers)) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/i', $headers[0], $m)) {
                $statusCode = (int)$m[1];
            }
        }

        $parsed = json_decode($result, true);
        return [
            'success' => ($statusCode >= 200 && $statusCode < 300),
            'latency_ms' => $latency,
            'status_code' => $statusCode,
            'data' => $parsed !== null ? $parsed : $result,
            'raw' => $result
        ];
    }

    /**
     * Helper untuk HTTP POST multipart/form-data
     */
    public static function httpPostMultipart(string $url, array $postFields, array $files = []): array {
        if (!function_exists('curl_init')) {
            return [
                'success' => false,
                'error' => 'cURL PHP extension tidak tersedia untuk upload multipart.'
            ];
        }

        $ch = curl_init();
        $fields = $postFields;

        foreach ($files as $key => $fileData) {
            if (!empty($fileData['tmp_name']) && file_exists($fileData['tmp_name'])) {
                $fields[$key] = new CURLFile(
                    $fileData['tmp_name'],
                    $fileData['type'] ?? 'application/octet-stream',
                    $fileData['name'] ?? 'submission.file'
                );
            }
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $start = microtime(true);
        $response = curl_exec($ch);
        $latency = round((microtime(true) - $start) * 1000, 2);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'latency_ms' => $latency,
                'status_code' => 0,
                'error' => $curlError ?: 'Connection refused to target URL'
            ];
        }

        $parsed = json_decode($response, true);
        return [
            'success' => ($statusCode >= 200 && $statusCode < 300),
            'latency_ms' => $latency,
            'status_code' => $statusCode,
            'data' => $parsed !== null ? $parsed : $response
        ];
    }

    /**
     * Memverifikasi Mata Kuliah ke Kelompok 3 (Course Service)
     * Kelompok 3 menyediakan:
     * - GET /api/courses
     * - GET /api/courses/{id} (id numerik, contoh 1, 2)
     */
    public static function verifyCourse(string $courseId): array {
        $cfg = Config::get();
        $baseUrl = rtrim($cfg['kelompok_3_course_url'], '/');

        // Normalisasi URL Kelompok 3 (mendukung direct port 8103 maupun path XAMPP)
        $coursesEndpoint = (strpos($baseUrl, '/api/courses') !== false) 
            ? $baseUrl 
            : $baseUrl . "/api/courses";

        // 1. Coba ambil seluruh list courses dari Kelompok 3
        $listRes = self::httpGet($coursesEndpoint, 2);

        if ($listRes['success'] && isset($listRes['data']['data']) && is_array($listRes['data']['data'])) {
            $courses = $listRes['data']['data'];
            foreach ($courses as $c) {
                // Cocokkan berdasarkan code (misal PTIK301) atau ID numerik
                if (strcasecmp($c['code'] ?? '', $courseId) === 0 || strval($c['id'] ?? '') === strval($courseId)) {
                    return [
                        'verified' => true,
                        'source' => 'KELOMPOK_3_LIVE',
                        'target_url' => $coursesEndpoint,
                        'course_id' => $c['code'],
                        'course_numeric_id' => $c['id'],
                        'course_name' => $c['name'],
                        'credits' => $c['credits'] ?? 3,
                        'details' => $c
                    ];
                }
            }
        }

        // 2. Jika numerik, coba langsung /api/courses/{id}
        if (ctype_digit($courseId)) {
            $singleEndpoint = $coursesEndpoint . '/' . $courseId;
            $singleRes = self::httpGet($singleEndpoint, 2);
            if ($singleRes['success'] && isset($singleRes['data']['data'])) {
                $c = $singleRes['data']['data'];
                return [
                    'verified' => true,
                    'source' => 'KELOMPOK_3_LIVE',
                    'target_url' => $singleEndpoint,
                    'course_id' => $c['code'] ?? ('COURSE-' . $courseId),
                    'course_numeric_id' => $c['id'] ?? $courseId,
                    'course_name' => $c['name'] ?? 'Mata Kuliah Kelompok 3',
                    'credits' => $c['credits'] ?? 3,
                    'details' => $c
                ];
            }
        }

        // 3. Fallback jika Kelompok 3 belum aktif
        if (!empty($cfg['use_mock_fallback'])) {
            $mockCatalogue = [
                'PTIK301' => ['name' => 'Sistem Terdistribusi', 'credits' => 3, 'id' => 1],
                'PTIK302' => ['name' => 'Pemrograman Web', 'credits' => 3, 'id' => 2],
                'PTIK303' => ['name' => 'Basis Data', 'credits' => 3, 'id' => 3],
                '1' => ['code' => 'PTIK301', 'name' => 'Sistem Terdistribusi', 'credits' => 3, 'id' => 1],
                '2' => ['code' => 'PTIK302', 'name' => 'Pemrograman Web', 'credits' => 3, 'id' => 2]
            ];

            $matched = $mockCatalogue[strtoupper($courseId)] ?? null;
            $courseCode = $matched['code'] ?? strtoupper($courseId);
            $courseName = $matched['name'] ?? ('Mata Kuliah ' . $courseId . ' (Simulasi Kelompok 3)');

            return [
                'verified' => true,
                'source' => 'MOCK_FALLBACK (Sesuai database Kelompok 3 PTIK301/302)',
                'target_url' => $coursesEndpoint,
                'course_id' => $courseCode,
                'course_name' => $courseName,
                'credits' => $matched['credits'] ?? 3,
                'note' => 'Kelompok 3 belum aktif di ' . $baseUrl . '. Data disimulasikan sesuai struktur course_db Kelompok 3 agar alur demo tetap lancar.'
            ];
        }

        return [
            'verified' => false,
            'source' => 'KELOMPOK_3_ERROR',
            'error' => $listRes['error'] ?? 'Gagal menghubungi Kelompok 3 Course Service',
            'target_url' => $coursesEndpoint
        ];
    }

    /**
     * Mengambil data pengumpulan tugas dari Kelompok 5 (Submission Service)
     * Kelompok 5 menyediakan:
     * - GET /api.php/{id}
     * - GET /health atau /api.php/health
     */
    public static function getSubmissions(string $assignmentId): array {
        $cfg = Config::get();
        $baseUrl = rtrim($cfg['kelompok_5_submission_url'], '/');

        // Endpoint Kelompok 5 bisa berupa root / api.php
        $targetEndpoint = (strpos($baseUrl, 'api.php') !== false) 
            ? $baseUrl . "?assignment_id=" . urlencode($assignmentId)
            : $baseUrl . "/api/submissions?assignment_id=" . urlencode($assignmentId);

        $res = self::httpGet($targetEndpoint, 2);

        if ($res['success'] && !empty($res['data'])) {
            return [
                'status' => 'success',
                'source' => 'KELOMPOK_5_LIVE',
                'target_url' => $targetEndpoint,
                'assignment_id' => $assignmentId,
                'submissions' => $res['data']
            ];
        }

        // Mock Fallback jika Kelompok 5 belum aktif
        if (!empty($cfg['use_mock_fallback'])) {
            return [
                'status' => 'success',
                'source' => 'MOCK_FALLBACK (Menunggu Kelompok 5 Online)',
                'target_url' => $targetEndpoint,
                'assignment_id' => $assignmentId,
                'submissions' => [
                    [
                        'submission_id' => 1,
                        'assignment_id' => $assignmentId,
                        'student_id' => '2209106001',
                        'student_name' => 'Ahmad Fathir',
                        'file_name' => '2209106001_Tugas_Socket.zip',
                        'file_path' => 'uploads/1730172000_2209106001_Tugas_Socket.zip',
                        'submitted_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                        'status' => 'SUBMITTED'
                    ],
                    [
                        'submission_id' => 2,
                        'assignment_id' => $assignmentId,
                        'student_id' => '2209106042',
                        'student_name' => 'Budi Santoso',
                        'file_name' => '2209106042_Laporan_Microservice.pdf',
                        'file_path' => 'uploads/1730168000_2209106042_Laporan_Microservice.pdf',
                        'submitted_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                        'status' => 'SUBMITTED'
                    ]
                ],
                'note' => 'Kelompok 5 belum aktif di ' . $baseUrl . '. Mengembalikan data simulasi sesuai struktur db_kelompok5.'
            ];
        }

        return [
            'status' => 'error',
            'source' => 'KELOMPOK_5_ERROR',
            'error' => $res['error'] ?? 'Gagal menghubungi Kelompok 5 Submission Service',
            'target_url' => $targetEndpoint
        ];
    }

    /**
     * Meneruskan file submission dari Client/Kelompok 4 ke Kelompok 5 (POST)
     * Kelompok 5 akan menyimpan ke database dan OTOMATIS memicu Kelompok 6 (Notification)
     */
    public static function forwardSubmissionToKelompok5(string $assignmentId, string $studentId, ?array $fileData): array {
        $cfg = Config::get();
        $baseUrl = rtrim($cfg['kelompok_5_submission_url'], '/');
        $endpoint = (strpos($baseUrl, 'api.php') !== false) ? $baseUrl : ($baseUrl . '/api.php');

        $postFields = [
            'assignment_id' => $assignmentId,
            'student_id' => $studentId
        ];
        $files = $fileData ? ['file' => $fileData] : [];

        $res = self::httpPostMultipart($endpoint, $postFields, $files);

        if ($res['success']) {
            return [
                'status' => 'success',
                'source' => 'KELOMPOK_5_LIVE (Memicu Notifikasi Kelompok 6)',
                'response_from_k5' => $res['data']
            ];
        }

        // Mock fallback jika Kelompok 5 offline
        if (!empty($cfg['use_mock_fallback'])) {
            return [
                'status' => 'success',
                'source' => 'MOCK_FALLBACK (Simulasi Forward ke Kelompok 5)',
                'message' => 'Submission disimulasikan berhasil diunggah ke Kelompok 5 dan diteruskan ke Notifikasi Kelompok 6.',
                'data' => [
                    'submission_id' => rand(10, 99),
                    'assignment_id' => $assignmentId,
                    'student_id' => $studentId,
                    'file_path' => 'uploads/' . time() . '_' . ($fileData['name'] ?? 'tugas.pdf'),
                    'notification_trigger' => 'Terkirim ke Kelompok 6 (Notification Service)'
                ]
            ];
        }

        return [
            'status' => 'error',
            'message' => 'Gagal meneruskan submission ke Kelompok 5: ' . ($res['error'] ?? 'Unknown error')
        ];
    }

    /**
     * Memeriksa konektivitas semua kelompok (untuk health check Kelompok 7)
     */
    public static function checkAllDependencies(): array {
        $cfg = Config::get();

        // Cek Kelompok 1 (API Gateway)
        $k1Url = rtrim($cfg['kelompok_1_gateway_url'], '/');
        $k1 = self::httpGet($k1Url . '/api/health', 1);
        if (!$k1['success']) {
            $k1 = self::httpGet($k1Url . '/health', 1);
        }

        // Cek Kelompok 3 (Course Service - port 8103)
        $k3Url = rtrim($cfg['kelompok_3_course_url'], '/');
        $k3 = self::httpGet($k3Url . '/api/health', 1);

        // Cek Kelompok 5 (Submission Service - port 8105 / api.php)
        $k5Url = rtrim($cfg['kelompok_5_submission_url'], '/');
        $k5 = self::httpGet($k5Url . '/api.php/health', 1);
        if (!$k5['success']) {
            $k5 = self::httpGet($k5Url . '/health', 1);
        }

        return [
            'kelompok_1_api_gateway' => [
                'name' => 'Kelompok 1: API Gateway',
                'url' => $cfg['kelompok_1_gateway_url'],
                'status' => $k1['success'] ? 'ONLINE' : 'WAITING_CONNECTION',
                'latency_ms' => $k1['latency_ms']
            ],
            'kelompok_3_course' => [
                'name' => 'Kelompok 3: Course Service (Port 8103)',
                'url' => $cfg['kelompok_3_course_url'],
                'status' => $k3['success'] ? 'ONLINE' : 'WAITING_CONNECTION',
                'latency_ms' => $k3['latency_ms']
            ],
            'kelompok_5_submission' => [
                'name' => 'Kelompok 5: Submission Service (Port 8105)',
                'url' => $cfg['kelompok_5_submission_url'],
                'status' => $k5['success'] ? 'ONLINE' : 'WAITING_CONNECTION',
                'latency_ms' => $k5['latency_ms']
            ]
        ];
    }
}
