<?php

class Storage {
    private static string $dataFile = __DIR__ . '/../data/assignments.json';

    public static function loadAll(): array {
        if (!file_exists(self::$dataFile)) {
            return [];
        }
        $raw = file_get_contents(self::$dataFile);
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function saveAll(array $assignments): bool {
        $dir = dirname(self::$dataFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return (bool) file_put_contents(
            self::$dataFile,
            json_encode($assignments, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    public static function getAll(?string $courseId = null, ?string $status = null, ?string $search = null): array {
        $items = self::loadAll();
        return array_values(array_filter($items, function ($item) use ($courseId, $status, $search) {
            if ($courseId !== null && strcasecmp($item['course_id'] ?? '', $courseId) !== 0) {
                return false;
            }
            if ($status !== null && strcasecmp($item['status'] ?? '', $status) !== 0) {
                return false;
            }
            if ($search !== null && $search !== '') {
                $term = strtolower($search);
                $title = strtolower($item['title'] ?? '');
                $desc = strtolower($item['description'] ?? '');
                $cname = strtolower($item['course_name'] ?? '');
                if (strpos($title, $term) === false && strpos($desc, $term) === false && strpos($cname, $term) === false) {
                    return false;
                }
            }
            return true;
        }));
    }

    public static function getById(string $id): ?array {
        $items = self::loadAll();
        foreach ($items as $item) {
            if (strcasecmp($item['id'] ?? '', $id) === 0) {
                return $item;
            }
        }
        return null;
    }

    public static function create(array $payload): array {
        $items = self::loadAll();

        // Generate next ASN ID: e.g. ASN-004
        $maxNum = 0;
        foreach ($items as $it) {
            if (isset($it['id']) && preg_match('/^ASN-(\d+)$/i', $it['id'], $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }
        $newId = sprintf("ASN-%03d", $maxNum + 1);

        $now = date('Y-m-d H:i:s');
        $newItem = [
            'id' => $payload['id'] ?? $newId,
            'course_id' => trim($payload['course_id'] ?? 'CS101'),
            'course_name' => trim($payload['course_name'] ?? 'Mata Kuliah Belum Terhubung'),
            'title' => trim($payload['title'] ?? 'Judul Tugas Baru'),
            'description' => trim($payload['description'] ?? ''),
            'due_date' => $payload['due_date'] ?? date('Y-m-d 23:59:00', strtotime('+7 days')),
            'max_points' => isset($payload['max_points']) ? (int)$payload['max_points'] : 100,
            'status' => strtoupper($payload['status'] ?? 'OPEN'),
            'course_verified' => (bool)($payload['course_verified'] ?? false),
            'created_at' => $now,
            'updated_at' => $now
        ];

        $items[] = $newItem;
        self::saveAll($items);
        return $newItem;
    }

    public static function update(string $id, array $payload): ?array {
        $items = self::loadAll();
        $found = false;
        $updatedItem = null;

        foreach ($items as &$item) {
            if (strcasecmp($item['id'] ?? '', $id) === 0) {
                if (isset($payload['title'])) $item['title'] = trim($payload['title']);
                if (isset($payload['description'])) $item['description'] = trim($payload['description']);
                if (isset($payload['course_id'])) $item['course_id'] = trim($payload['course_id']);
                if (isset($payload['course_name'])) $item['course_name'] = trim($payload['course_name']);
                if (isset($payload['due_date'])) $item['due_date'] = $payload['due_date'];
                if (isset($payload['max_points'])) $item['max_points'] = (int)$payload['max_points'];
                if (isset($payload['status'])) $item['status'] = strtoupper($payload['status']);
                if (isset($payload['course_verified'])) $item['course_verified'] = (bool)$payload['course_verified'];

                $item['updated_at'] = date('Y-m-d H:i:s');
                $updatedItem = $item;
                $found = true;
                break;
            }
        }

        if ($found) {
            self::saveAll($items);
            return $updatedItem;
        }

        return null;
    }

    public static function delete(string $id): bool {
        $items = self::loadAll();
        $initialCount = count($items);
        $items = array_values(array_filter($items, function ($it) use ($id) {
            return strcasecmp($it['id'] ?? '', $id) !== 0;
        }));

        if (count($items) < $initialCount) {
            self::saveAll($items);
            return true;
        }
        return false;
    }

    private static string $submissionsFile = __DIR__ . '/../data/submissions.json';

    public static function loadSubmissions(): array {
        if (!file_exists(self::$submissionsFile)) {
            return [];
        }
        $raw = file_get_contents(self::$submissionsFile);
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    public static function saveSubmission(array $submission): void {
        $subs = self::loadSubmissions();
        $subs[] = $submission;
        $dir = dirname(self::$submissionsFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents(self::$submissionsFile, json_encode($subs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function getSubmissionsByAssignment(string $assignmentId): array {
        $subs = self::loadSubmissions();
        $numericId = preg_match('/\d+/', $assignmentId, $m) ? (int)$m[0] : null;
        return array_values(array_filter($subs, function ($it) use ($assignmentId, $numericId) {
            $aid = $it['assignment_id'] ?? '';
            return strcasecmp((string)$aid, (string)$assignmentId) === 0 || ($numericId !== null && intval($aid) === $numericId);
        }));
    }
}
