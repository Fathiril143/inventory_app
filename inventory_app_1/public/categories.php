<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

require_admin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $error = 'Nama kategori wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO categories (name) VALUES (:name)'
            );
            $stmt->execute(['name' => $name]);

            header('Location: categories.php');
            exit;
        } catch (PDOException $exception) {
            $error = 'Kategori gagal ditambahkan. Pastikan namanya belum digunakan.';
        }
    }
}

$categories = $pdo
    ->query('SELECT id, name FROM categories ORDER BY name')
    ->fetchAll();
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kategori</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Kategori</h1>
    <p><a href="dashboard.php">Kembali ke dashboard</a></p>

    <?php if ($error): ?>
        <p><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <label>
            Nama kategori
            <input name="name" required>
        </label>

        <button type="submit">Tambah kategori</button>
    </form>

    <h2>Daftar kategori</h2>

    <ul>
        <?php foreach ($categories as $category): ?>
            <li><?= e($category['name']) ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>