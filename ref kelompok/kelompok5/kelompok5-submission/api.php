<?php
header("Content-Type: application/json; charset=UTF-8");
require_once 'db.php'; // Memanggil koneksi database (PDO)

// Membuat folder uploads jika belum ada
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$method = $_SERVER['REQUEST_METHOD'];
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathSegments = explode('/', trim($requestUri, '/'));
$endpoint = $pathSegments[count($pathSegments) - 1] ?? '';

switch ($method) {
    case 'POST':
        // Endpoint: POST /api/submissions (Unggah Submission & Simpan ke DB)
        $assignmentId = $_POST['assignment_id'] ?? '';
        $studentId = $_POST['student_id'] ?? '';
        $file = $_FILES['file'] ?? null;

        if (!$assignmentId || !$studentId || !$file) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap (assignment_id, student_id, dan file wajib diisi)"]);
            exit;
        }

        $originalName = basename($file['name']);
        $fileName = time() . '_' . preg_replace("/\s+/", "_", $originalName);
        $targetFilePath = $uploadDir . $fileName;
        $relativePath = 'uploads/' . $fileName;

        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            try {
                // Simpan ke Database menggunakan Prepared Statements
                $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file_name, file_path) VALUES (?, ?, ?, ?)");
                $stmt->execute([$assignmentId, $studentId, $originalName, $relativePath]);
                $submissionId = $pdo->lastInsertId();

                // === OTOMATIS MENTRIGGER KELOMPOK 6 (NOTIFICATION) ===
                triggerNotificationToGroup6($studentId, $assignmentId);

                http_response_code(201);
                echo json_encode([
                    "status" => "success",
                    "message" => "Submission berhasil diunggah, disimpan, dan notifikasi dikirim",
                    "data" => [
                        "submission_id" => (int)$submissionId,
                        "assignment_id" => $assignmentId,
                        "student_id" => $studentId,
                        "file_path" => $relativePath
                    ]
                ]);
            } catch (\PDOException $e) {
                http_response_code(500);
                echo json_encode(["status" => "error", "message" => "Gagal menyimpan ke database: " . $e->getMessage()]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal mengunggah file ke server"]);
        }
        break;

    case 'GET':
        // Endpoint Monitoring untuk Kelompok 7
        if ($endpoint === 'health') {
            http_response_code(200);
            echo json_encode(["status" => "healthy", "service" => "Kelompok 5 Submission/File Service"]);
            exit;
        }

        // Endpoint: GET /api/submissions/{id} (Ambil data dari DB)
        if (is_numeric($endpoint)) {
            $submissionId = $endpoint;

            $stmt = $pdo->prepare("SELECT * FROM submissions WHERE id = ?");
            $stmt->execute([$submissionId]);
            $data = $stmt->fetch();

            if ($data) {
                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "data" => $data
                ]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Data submission tidak ditemukan"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID Submission tidak valid"]);
        }
        break;

    case 'DELETE':
        // Endpoint: DELETE /api/submissions/{id} (Hapus file fisik dan data dari DB)
        if (is_numeric($endpoint)) {
            $submissionId = $endpoint;

            // Ambil path file terlebih dahulu untuk dihapus foldernya
            $stmt = $pdo->prepare("SELECT file_path FROM submissions WHERE id = ?");
            $stmt->execute([$submissionId]);
            $data = $stmt->fetch();

            if ($data) {
                // Hapus file fisik jika ada
                if (file_exists(__DIR__ . '/' . $data['file_path'])) {
                    unlink(__DIR__ . '/' . $data['file_path']);
                }

                // Hapus dari database
                $deleteStmt = $pdo->prepare("DELETE FROM submissions WHERE id = ?");
                $deleteStmt->execute([$submissionId]);

                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "Submission dengan ID {$submissionId} berhasil dihapus dari database"
                ]);
            } else {
                http_response_code(404);
                echo json_encode(["status" => "error", "message" => "Data submission tidak ditemukan"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID Submission tidak valid"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Metode HTTP tidak didukung"]);
        break;
}

/**
 * Fungsi untuk mengirim data POST otomatis ke Kelompok 6 (Notification Service)
 */
function triggerNotificationToGroup6($studentId, $assignmentId) {
    // Sesuaikan URL endpoint milik Kelompok 6 Anda
    $urlKelompok6 = "http://10.236.161.36/notification/api.php"; 

    // Data yang dikirim disesuaikan dengan $_POST yang diterima Kelompok 6
    $data = [
        "recipient" => $studentId,
        "message" => "Tugas untuk assignment ID {$assignmentId} berhasil diunggah."
    ];

    $ch = curl_init($urlKelompok6);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    
    // Eksekusi cURL ke Kelompok 6
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
?>