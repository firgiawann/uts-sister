<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/db.php";

function respond($status, $body) {
    http_response_code($status);
    echo json_encode($body);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Menemukan prefix /api/courses,
// baik di URL langsung maupun URL dengan prefix folder.
$parts = array_values(array_filter(
    explode("/", trim($path, "/")),
    "strlen"
));

$apiIndex = array_search("api", $parts, true);

// GET /api/health
// Endpoint tambahan untuk Kelompok 7 (Monitoring Service):
// memeriksa apakah Course Service hidup dan database terhubung.
// db.php di atas sudah memastikan koneksi database berhasil,
// jadi jika sampai ke baris ini berarti service + database sehat.
if (
    $method === "GET" &&
    $apiIndex !== false &&
    isset($parts[$apiIndex + 1]) &&
    $parts[$apiIndex + 1] === "health"
) {
    respond(200, [
        "success" => true,
        "status" => "UP",
        "service" => "course-service"
    ]);
}

if (
    $apiIndex === false ||
    !isset($parts[$apiIndex + 1]) ||
    $parts[$apiIndex + 1] !== "courses"
) {
    respond(404, [
        "success" => false,
        "message" => "Endpoint tidak ditemukan"
    ]);
}

$remaining = array_slice($parts, $apiIndex + 2);

if (count($remaining) > 1) {
    respond(404, [
        "success" => false,
        "message" => "Endpoint tidak ditemukan"
    ]);
}

$id = null;

if (count($remaining) === 1) {
    if (
        !ctype_digit($remaining[0]) ||
        (int)$remaining[0] < 1
    ) {
        respond(400, [
            "success" => false,
            "message" => "ID tidak valid"
        ]);
    }

    $id = (int)$remaining[0];
}

// GET /api/courses
if ($method === "GET" && $id === null) {
    $result = $conn->query(
        "SELECT id, code, name, credits
         FROM courses ORDER BY id"
    );

    if (!$result) {
        respond(500, [
            "success" => false,
            "message" => "Gagal mengambil data"
        ]);
    }

    $courses = [];

    while ($row = $result->fetch_assoc()) {
        $row["id"] = (int)$row["id"];
        $row["credits"] = (int)$row["credits"];
        $courses[] = $row;
    }

    respond(200, [
        "success" => true,
        "data" => $courses
    ]);
}

// GET /api/courses/{id}
if ($method === "GET" && $id !== null) {
    $stmt = $conn->prepare(
        "SELECT id, code, name, credits
         FROM courses WHERE id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $course = $result->fetch_assoc();

    if (!$course) {
        respond(404, [
            "success" => false,
            "message" => "Course tidak ditemukan"
        ]);
    }

    $course["id"] = (int)$course["id"];
    $course["credits"] = (int)$course["credits"];

    respond(200, [
        "success" => true,
        "data" => $course
    ]);
}

// POST /api/courses
if ($method === "POST" && $id === null) {
    $input = json_decode(
        file_get_contents("php://input"),
        true
    );

    if (
        !is_array($input) ||
        !isset($input["code"], $input["name"], $input["credits"]) ||
        !is_string($input["code"]) ||
        !is_string($input["name"]) ||
        !is_int($input["credits"]) ||
        trim($input["code"]) === "" ||
        trim($input["name"]) === "" ||
        strlen($input["code"]) > 20 ||
        strlen($input["name"]) > 100 ||
        $input["credits"] < 1 ||
        $input["credits"] > 255
    ) {
        respond(400, [
            "success" => false,
            "message" => "Data course tidak valid"
        ]);
    }

    $code = trim($input["code"]);
    $name = trim($input["name"]);
    $credits = $input["credits"];

    $stmt = $conn->prepare(
        "INSERT INTO courses (code, name, credits)
         VALUES (?, ?, ?)"
    );

    $stmt->bind_param("ssi", $code, $name, $credits);

    if (!$stmt->execute()) {
        if ($stmt->errno === 1062) {
            respond(409, [
                "success" => false,
                "message" => "Kode course sudah digunakan"
            ]);
        }

        respond(500, [
            "success" => false,
            "message" => "Gagal menambahkan course"
        ]);
    }

    respond(201, [
        "success" => true,
        "message" => "Course berhasil ditambahkan",
        "data" => [
            "id" => $conn->insert_id,
            "code" => $code,
            "name" => $name,
            "credits" => $credits
        ]
    ]);
}

// DELETE /api/courses/{id}
if ($method === "DELETE" && $id !== null) {
    $stmt = $conn->prepare(
        "DELETE FROM courses WHERE id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        respond(404, [
            "success" => false,
            "message" => "Course tidak ditemukan"
        ]);
    }

    respond(200, [
        "success" => true,
        "message" => "Course berhasil dihapus"
    ]);
}

// Method tidak diizinkan pada endpoint tersebut
if ($method === "POST" && $id !== null) {
    header("Allow: GET, DELETE");
    respond(405, [
        "success" => false,
        "message" => "Method tidak diizinkan"
    ]);
}

if ($method === "DELETE" && $id === null) {
    header("Allow: GET, POST");
    respond(405, [
        "success" => false,
        "message" => "Method tidak diizinkan"
    ]);
}

header("Allow: GET, POST, DELETE");
respond(405, [
    "success" => false,
    "message" => "Method tidak diizinkan"
]);
