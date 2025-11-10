<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/db.php';
header('Content-Type: text/plain; charset=utf-8');

$rows = db()->query("SELECT id, username, email, password_hash, created_at FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    echo "id: {$r['id']}\n";
    echo "username: {$r['username']}\n";
    echo "email: {$r['email']}\n";
    echo "password_hash: {$r['password_hash']}\n";
    echo "created_at: {$r['created_at']}\n";
    echo "---------------------------\n";
}
