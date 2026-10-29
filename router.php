<?php

// Enable error reporting during development
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Global CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Autoload / includes
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/Storage.php';
require_once __DIR__ . '/src/ExternalService.php';
require_once __DIR__ . '/src/AssignmentController.php';
require_once __DIR__ . '/src/HealthController.php';
require_once __DIR__ . '/src/ConfigController.php';

// Parse Request URI & Method
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = strtoupper($_SERVER['REQUEST_METHOD']);

// Serve static files from public/ if requested
$publicFile = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($publicFile) && !is_dir($publicFile)) {
    $ext = pathinfo($publicFile, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon'
    ];
    $contentType = $mimes[$ext] ?? 'application/octet-stream';
    header("Content-Type: {$contentType}");
    readfile($publicFile);
    exit;
}

// Serve root as Web UI Dashboard
if ($uri === '/' || $uri === '/index.html') {
    $indexPath = __DIR__ . '/public/index.html';
    if (file_exists($indexPath)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($indexPath);
        exit;
    }
}

// Helper to get JSON body
function getRequestBody(): array {
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        return $_POST;
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

// ---------------- ROUTING TABLE ----------------

// Kelompok 7: Monitoring Service
if ($uri === '/health' && $method === 'GET') {
    HealthController::check();
}

// Config Endpoints
if ($uri === '/api/config') {
    if ($method === 'GET') {
        ConfigController::get();
    } elseif ($method === 'POST') {
        ConfigController::update(getRequestBody());
    }
}

// Assignment Endpoints
if ($uri === '/api/assignments') {
    if ($method === 'GET') {
        AssignmentController::index();
    } elseif ($method === 'POST') {
        AssignmentController::create(getRequestBody());
    }
}

// Helper route: Ambil daftar mata kuliah langsung dari Kelompok 3 (Live Course List)
if ($uri === '/api/external/courses' && $method === 'GET') {
    $cfg = Config::get();
    $target = rtrim($cfg['kelompok_3_course_url'], '/') . '/api/courses';
    $res = ExternalService::httpGet($target, 2);
    if ($res['success']) {
        echo json_encode($res['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode([
            'success' => false,
            'source' => 'MOCK_CATALOGUE',
            'data' => [
                ['id' => 1, 'code' => 'PTIK301', 'name' => 'Sistem Terdistribusi', 'credits' => 3],
                ['id' => 2, 'code' => 'PTIK302', 'name' => 'Pemrograman Web', 'credits' => 3],
                ['id' => 3, 'code' => 'PTIK303', 'name' => 'Basis Data', 'credits' => 3]
            ],
            'note' => 'Kelompok 3 belum aktif, menampilkan data acuan course_db.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    exit;
}

// Submissions for specific assignment: /api/assignments/{id}/submissions
if (preg_match('#^/api/assignments/([^/]+)/submissions/?$#', $uri, $matches)) {
    $id = urldecode($matches[1]);
    if ($method === 'GET') {
        AssignmentController::getSubmissions($id);
    } elseif ($method === 'POST') {
        AssignmentController::submitToKelompok5($id);
    }
}

// Single Assignment detail/update/delete: /api/assignments/{id}
if (preg_match('#^/api/assignments/([^/]+)/?$#', $uri, $matches)) {
    $id = urldecode($matches[1]);
    if ($method === 'GET') {
        AssignmentController::show($id);
    } elseif ($method === 'PUT' || $method === 'PATCH') {
        AssignmentController::update($id, getRequestBody());
    } elseif ($method === 'DELETE') {
        AssignmentController::delete($id);
    }
}

// Fallback 404 for API
http_response_code(404);
header('Content-Type: application/json; charset=UTF-8');
echo json_encode([
    'status' => 'error',
    'message' => "Endpoint '{$method} {$uri}' tidak ditemukan.",
    'service' => 'Kelompok 4 - Assignment Service',
    'available_endpoints' => [
        'GET /health' => 'Health check untuk Kelompok 7 Monitoring',
        'GET /api/assignments' => 'List semua tugas (opsional query: course_id, status, search)',
        'POST /api/assignments' => 'Buat tugas baru (otomatis verifikasi ke Kelompok 3 Course)',
        'GET /api/assignments/{id}' => 'Detail satu tugas',
        'PUT /api/assignments/{id}' => 'Update tugas',
        'DELETE /api/assignments/{id}' => 'Hapus tugas',
        'GET /api/assignments/{id}/submissions' => 'Ambil data submission tugas dari Kelompok 5',
        'GET /api/config' => 'Lihat IP / URL konfigurasi kelompok lain',
        'POST /api/config' => 'Ubah IP / URL konfigurasi kelompok lain secara dinamis'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
