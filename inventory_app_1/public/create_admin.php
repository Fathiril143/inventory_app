<?php

require_once __DIR__ . '/../config/database.php';

$username = 'admin';
$password = 'Admin123!';
$fullName = 'Administrator';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$sql = 'INSERT INTO users (username, password_hash, full_name, role)
        VALUES (:username, :password_hash, :full_name, :role)';

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'username' => $username,
    'password_hash' => $passwordHash,
    'full_name' => $fullName,
    'role' => 'admin',
]);

echo 'Akun admin berhasil dibuat.';