<?php

if (PHP_SAPI !== 'cli') {
    exit("Jalankan file ini lewat XAMPP Shell, bukan browser.\n");
}

require_once __DIR__ . '/config/database.php';

$username = readline('Username admin: ');
$fullName = readline('Nama admin: ');
$password = readline('Password admin (minimal 8 karakter): ');

if ($username === '' || $fullName === '' || strlen($password) < 8) {
    exit("Data tidak valid. Password minimal 8 karakter.\n");
}

$stmt = $pdo->prepare(
    'INSERT INTO users (username, password_hash, full_name, role)
     VALUES (:username, :password_hash, :full_name, :role)'
);

try {
    $stmt->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'full_name' => $fullName,
        'role' => 'admin',
    ]);

    echo "Akun admin berhasil dibuat.\n";
} catch (PDOException $exception) {
    echo "Gagal membuat akun. Username mungkin sudah digunakan.\n";
}