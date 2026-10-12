<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

require_admin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') {
        $error = 'Nama supplier wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO suppliers (name, phone)
                 VALUES (:name, :phone)'
            );

            $stmt->execute([
                'name' => $name,
                'phone' => $phone ?: null,
            ]);

            header('Location: suppliers.php');
            exit;
        } catch (PDOException $exception) {
            $error = 'Supplier gagal disimpan.';
        }
    }
}

$suppliers = $pdo
    ->query('SELECT id, name, phone FROM suppliers ORDER BY name')
    ->fetchAll();
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Supplier</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Supplier</h1>
    <p><a href="dashboard.php">Kembali ke dashboard</a></p>

    <?php if ($error): ?>
        <p><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <label>
            Nama supplier
            <input name="name" required>
        </label>

        <label>
            Nomor telepon
            <input name="phone">
        </label>

        <button type="submit">Tambah supplier</button>
    </form>

    <h2>Daftar supplier</h2>

    <table>
        <tr>
            <th>Nama</th>
            <th>Telepon</th>
        </tr>

        <?php foreach ($suppliers as $supplier): ?>
            <tr>
                <td><?= e($supplier['name']) ?></td>
                <td><?= e($supplier['phone']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>