<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, ['127.0.0.1', '::1'])) {
    http_response_code(403);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$limit  = isset($_GET['limit'])  ? max(1, min(1000, (int)$_GET['limit'])) : 100;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;

$sql = "
    SELECT
        id,
        username,
        email,
        password_hash,
        is_verified,
        created_at
    FROM users
    ORDER BY id DESC
    LIMIT :lim OFFSET :off
";
$stmt = db()->prepare($sql);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'meta' => [
        'generated_at' => gmdate('c'),
        'count'        => count($rows),
        'limit'        => $limit,
        'offset'       => $offset
    ],
    'users' => $rows
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
