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
     * Helper untuk HTTP POST multipart/form-data (Mendukung native PHP stream & cURL)
     */
    public static function httpPostMultipart(string $url, array $postFields, array $files = [], int $timeoutSeconds = 30): array {
        // Fallback Native PHP Stream Context (Tidak butuh extension curl)
        $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(12));
        $body = '';

        foreach ($postFields as $name => $val) {
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
            $body .= "{$val}\r\n";
        }

        foreach ($files as $name => $fileData) {
            if (!empty($fileData['tmp_name']) && file_exists($fileData['tmp_name'])) {
                $filename = basename($fileData['name'] ?? 'submission.pdf');
                $fileContent = file_get_contents($fileData['tmp_name']);
                $mimeType = $fileData['type'] ?? 'application/octet-stream';

                $body .= "--{$boundary}\r\n";
                $body .= "Content-Disposition: form-data; name=\"{$name}\"; filename=\"{$filename}\"\r\n";
                $body .= "Content-Type: {$mimeType}\r\n\r\n";
                $body .= $fileContent . "\r\n";
            }
        }
        $body .= "--{$boundary}--\r\n";

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: multipart/form-data; boundary={$boundary}\r\n" .
                            "Content-Length: " . strlen($body) . "\r\n" .
                            "User-Agent: Kelompok4-AssignmentService/1.0\r\n",
                'content' => $body,
                'timeout' => $timeoutSeconds,
                'ignore_errors' => true
            ]
        ]);

        $start = microtime(true);
        $response = @file_get_contents($url, false, $context);
        $latency = round((microtime(true) - $start) * 1000, 2);

        $statusCode = 200;
        $headers = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? []);
        if (!empty($headers) && is_array($headers)) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/i', $headers[0], $m)) {
                $statusCode = (int)$m[1];
            }
        }

        if ($response === false) {
            return [
                'success' => false,
                'latency_ms' => $latency,
                'status_code' => 0,
                'error' => error_get_last()['message'] ?? 'Connection refused to target URL'
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

        // 1. Cek status konektivitas ke Kelompok 5
        $healthEndpoint = (strpos($baseUrl, 'api.php') !== false) 
            ? $baseUrl . '/health' 
            : $baseUrl . '/health';
        $healthRes = self::httpGet($healthEndpoint, 2);
        $isK5Online = $healthRes['success'];

        // 2. Ambil data submission yang tersimpan di sistem
        $localSubs = Storage::getSubmissionsByAssignment($assignmentId);
        $verifiedSubs = [];

        foreach ($localSubs as $sub) {
            $subId = $sub['submission_id'] ?? null;
            if ($subId && $isK5Online) {
                // Verifikasi keberadaan file di endpoint /api.php/{id} Kelompok 5
                $verifyUrl = (strpos($baseUrl, 'api.php') !== false) 
                    ? $baseUrl . '/' . $subId 
                    : $baseUrl . '/api.php/' . $subId;
                $vRes = self::httpGet($verifyUrl, 2);
                if ($vRes['success'] && isset($vRes['data']['data'])) {
                    $d = $vRes['data']['data'];
                    $sub['file_path'] = $d['file_path'] ?? $sub['file_path'];
                    $sub['submitted_at'] = $d['submitted_at'] ?? $sub['submitted_at'];
                    $sub['source'] = 'KELOMPOK_5_LIVE (Verified DB)';
                }
            }
            $verifiedSubs[] = $sub;
        }

        if (!empty($verifiedSubs)) {
            return [
                'status' => 'success',
                'source' => $isK5Online ? 'KELOMPOK_5_LIVE (10.194.169.208)' : 'LOCAL_CACHE (K5 Offline)',
                'kelompok_5_online' => $isK5Online,
                'target_url' => $baseUrl,
                'assignment_id' => $assignmentId,
                'submissions' => $verifiedSubs,
                'note' => $isK5Online
                    ? 'Data submission terhubung langsung ke database Kelompok 5 (Port 80 XAMPP).'
                    : 'Kelompok 5 sedang offline, menampilkan berkas tersimpan sebelumnya.'
            ];
        }

        // Mock Fallback jika belum ada submission dan K5 offline
        if (!$isK5Online && !empty($cfg['use_mock_fallback'])) {
            return [
                'status' => 'success',
                'source' => 'MOCK_FALLBACK (Menunggu Kelompok 5 Online)',
                'target_url' => $baseUrl,
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
                    ]
                ],
                'note' => 'Kelompok 5 belum aktif di ' . $baseUrl . '. Mengembalikan data simulasi.'
            ];
        }

        return [
            'status' => 'success',
            'source' => $isK5Online ? 'KELOMPOK_5_LIVE (Siap Menerima File)' : 'LOCAL',
            'kelompok_5_online' => $isK5Online,
            'target_url' => $baseUrl,
            'assignment_id' => $assignmentId,
            'submissions' => [],
            'note' => 'Belum ada submission untuk tugas ini. Siap menerima unggahan tugas mahasiswa.'
        ];
    }

    /**
     * Meneruskan file submission dari Client/Kelompok 4 ke Kelompok 5 (POST)
     * Kelompok 5 akan menyimpan ke database dan OTOMATIS memicu Kelompok 6 (Notification)
     */
    public static function forwardSubmissionToKelompok5(string $assignmentId, string $studentId, ?array $fileData, ?string $studentName = null): array {
        $cfg = Config::get();
        $baseUrl = rtrim($cfg['kelompok_5_submission_url'], '/');
        $endpoint = (strpos($baseUrl, 'api.php') !== false) ? $baseUrl : ($baseUrl . '/api.php');

        // Database Kelompok 5 menyimpan assignment_id berupa integer
        $numericId = preg_match('/\d+/', $assignmentId, $m) ? (int)$m[0] : 1;

        $postFields = [
            'assignment_id' => $numericId,
            'student_id' => $studentId
        ];
        if (!empty($studentName)) {
            $postFields['student_name'] = $studentName;
        }

        $files = $fileData ? ['file' => $fileData] : [];

        // Timeout 30s karena Kelompok 5 meneruskan request ke Kelompok 6 Notifikasi
        $res = self::httpPostMultipart($endpoint, $postFields, $files, 30);

        if ($res['success']) {
            $k5Data = $res['data']['data'] ?? $res['data'];
            $newSubmission = [
                'submission_id' => $k5Data['submission_id'] ?? $k5Data['id'] ?? rand(100, 999),
                'assignment_id' => $assignmentId,
                'student_id' => $studentId,
                'student_name' => $studentName ?? 'Mahasiswa',
                'file_name' => basename($fileData['name'] ?? 'submission.pdf'),
                'file_path' => $k5Data['file_path'] ?? ('uploads/' . ($fileData['name'] ?? 'submission.pdf')),
                'submitted_at' => date('Y-m-d H:i:s'),
                'status' => 'SUBMITTED',
                'source' => 'KELOMPOK_5_LIVE'
            ];
            Storage::saveSubmission($newSubmission);

            return [
                'status' => 'success',
                'source' => 'KELOMPOK_5_LIVE (Tersimpan di DB K5 & Memicu Notifikasi K6)',
                'response_from_k5' => $res['data'],
                'saved_submission' => $newSubmission
            ];
        }

        // Mock fallback jika Kelompok 5 offline
        if (!empty($cfg['use_mock_fallback'])) {
            $simulated = [
                'submission_id' => rand(10, 99),
                'assignment_id' => $assignmentId,
                'student_id' => $studentId,
                'student_name' => $studentName ?? 'Mahasiswa',
                'file_name' => basename($fileData['name'] ?? 'tugas.pdf'),
                'file_path' => 'uploads/' . time() . '_' . ($fileData['name'] ?? 'tugas.pdf'),
                'submitted_at' => date('Y-m-d H:i:s'),
                'status' => 'SUBMITTED',
                'source' => 'MOCK_FALLBACK'
            ];
            Storage::saveSubmission($simulated);

            return [
                'status' => 'success',
                'source' => 'MOCK_FALLBACK (Simulasi Forward ke Kelompok 5)',
                'message' => 'Submission disimulasikan berhasil diunggah ke Kelompok 5 dan diteruskan ke Notifikasi Kelompok 6.',
                'data' => $simulated
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
