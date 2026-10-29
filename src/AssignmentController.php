<?php

require_once __DIR__ . '/Storage.php';
require_once __DIR__ . '/ExternalService.php';

class AssignmentController {

    public static function index(): void {
        $courseId = $_GET['course_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $search = $_GET['search'] ?? null;

        $assignments = Storage::getAll($courseId, $status, $search);

        self::jsonResponse([
            'status' => 'success',
            'service' => 'Kelompok 4 - Assignment Service',
            'count' => count($assignments),
            'filters' => [
                'course_id' => $courseId,
                'status' => $status,
                'search' => $search
            ],
            'data' => $assignments
        ], 200);
    }

    public static function show(string $id): void {
        $item = Storage::getById($id);
        if (!$item) {
            self::jsonResponse([
                'status' => 'error',
                'message' => "Assignment dengan ID '{$id}' tidak ditemukan."
            ], 404);
            return;
        }

        self::jsonResponse([
            'status' => 'success',
            'service' => 'Kelompok 4 - Assignment Service',
            'data' => $item
        ], 200);
    }

    public static function create(array $body): void {
        if (empty($body['title'])) {
            self::jsonResponse([
                'status' => 'error',
                'message' => "Field 'title' wajib diisi."
            ], 400);
            return;
        }

        $courseId = $body['course_id'] ?? 'CS101';

        // Hubungkan ke Kelompok 3 (Course Service) untuk verifikasi mata kuliah
        $courseVerification = ExternalService::verifyCourse($courseId);

        $payload = [
            'course_id' => $courseId,
            'course_name' => $courseVerification['course_name'] ?? ($body['course_name'] ?? 'Mata Kuliah Kelompok 3'),
            'title' => $body['title'],
            'description' => $body['description'] ?? '',
            'due_date' => $body['due_date'] ?? date('Y-m-d 23:59:00', strtotime('+7 days')),
            'max_points' => $body['max_points'] ?? 100,
            'status' => $body['status'] ?? 'OPEN',
            'course_verified' => !empty($courseVerification['verified'])
        ];

        $created = Storage::create($payload);

        self::jsonResponse([
            'status' => 'success',
            'message' => 'Assignment berhasil dibuat oleh Kelompok 4.',
            'kelompok_3_integration' => $courseVerification,
            'data' => $created
        ], 201);
    }

    public static function update(string $id, array $body): void {
        $existing = Storage::getById($id);
        if (!$existing) {
            self::jsonResponse([
                'status' => 'error',
                'message' => "Assignment dengan ID '{$id}' tidak ditemukan untuk diperbarui."
            ], 404);
            return;
        }

        $updated = Storage::update($id, $body);

        self::jsonResponse([
            'status' => 'success',
            'message' => "Assignment '{$id}' berhasil diperbarui.",
            'data' => $updated
        ], 200);
    }

    public static function delete(string $id): void {
        $existing = Storage::getById($id);
        if (!$existing) {
            self::jsonResponse([
                'status' => 'error',
                'message' => "Assignment dengan ID '{$id}' tidak ditemukan untuk dihapus."
            ], 404);
            return;
        }

        Storage::delete($id);

        self::jsonResponse([
            'status' => 'success',
            'message' => "Assignment '{$id}' berhasil dihapus dari sistem.",
            'deleted_id' => $id
        ], 200);
    }

    public static function getSubmissions(string $id): void {
        $existing = Storage::getById($id);
        if (!$existing) {
            self::jsonResponse([
                'status' => 'error',
                'message' => "Assignment dengan ID '{$id}' tidak ditemukan."
            ], 404);
            return;
        }

        // Hubungi Kelompok 5 (Submission Service)
        $submissionResult = ExternalService::getSubmissions($id);

        self::jsonResponse([
            'status' => 'success',
            'service' => 'Kelompok 4 - Assignment Service (menghubungi Kelompok 5)',
            'assignment' => [
                'id' => $existing['id'],
                'title' => $existing['title'],
                'course_id' => $existing['course_id']
            ],
            'kelompok_5_integration' => $submissionResult
        ], 200);
    }

    public static function submitToKelompok5(string $id): void {
        $existing = Storage::getById($id);
        if (!$existing) {
            self::jsonResponse([
                'status' => 'error',
                'message' => "Assignment dengan ID '{$id}' tidak ditemukan."
            ], 404);
            return;
        }

        $studentId = $_POST['student_id'] ?? ($_GET['student_id'] ?? '2209106001');
        $studentName = $_POST['student_name'] ?? ($_GET['student_name'] ?? 'Mahasiswa');
        $fileData = $_FILES['file'] ?? null;

        $result = ExternalService::forwardSubmissionToKelompok5($id, $studentId, $fileData, $studentName);

        self::jsonResponse([
            'status' => 'success',
            'message' => 'Pengumpulan tugas berhasil diteruskan ke Kelompok 5 (Submission Service).',
            'assignment' => [
                'id' => $existing['id'],
                'title' => $existing['title']
            ],
            'integration_result' => $result
        ], 200);
    }

    public static function jsonResponse(array $data, int $statusCode = 200): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
