<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

require_admin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'staff';

    if (
        $username === '' ||
        $fullName === '' ||
        strlen($password) < 8 ||
        !in_array($role, ['admin', 'staff'], true)
    ) {
        $error = 'Isi semua kolom. Password minimal 8 karakter.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, password_hash, full_name, role)
                 VALUES (:username, :password_hash, :full_name, :role)'
            );

            $stmt->execute([
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'full_name' => $fullName,
                'role' => $role,
            ]);

            header('Location: users.php');
            exit;
        } catch (PDOException $exception) {
            $error = 'Akun gagal dibuat. Username mungkin sudah digunakan.';
        }
    }
}

$users = $pdo
    ->query('SELECT username, full_name, role FROM users ORDER BY full_name')
    ->fetchAll();
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengguna | Inventory</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Pengguna</h1>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Produk</a>
        <a href="stock.php">Transaksi stok</a>
        <a href="categories.php">Kategori</a>
        <a href="suppliers.php">Supplier</a>
    </nav>

    <?php if ($error): ?>
        <p><?= e($error) ?></p>
    <?php endif; ?>

    <h2>Tambah akun</h2>

    <form method="post">
        <?= csrf_field() ?>

        <label>
            Username
            <input name="username" required>
        </label>

        <label>
            Nama lengkap
            <input name="full_name" required>
        </label>

        <label>
            Password, minimal 8 karakter
            <input type="password" name="password" minlength="8" required>
        </label>

        <label>
            Role
            <select name="role">
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
        </label>

        <button type="submit">Buat akun</button>
    </form>

    <h2>Daftar pengguna</h2>

    <table>
        <tr>
            <th>Username</th>
            <th>Nama</th>
            <th>Role</th>
        </tr>

        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= e($user['username']) ?></td>
                <td><?= e($user['full_name']) ?></td>
                <td><?= e($user['role']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>