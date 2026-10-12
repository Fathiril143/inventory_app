<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

require_login();

$message = '';

if (!empty($_SESSION['flash'])) {
    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $productId = filter_var(
        $_POST['product_id'] ?? '',
        FILTER_VALIDATE_INT
    );
    $quantity = filter_var(
        $_POST['quantity'] ?? '',
        FILTER_VALIDATE_INT
    );
    $type = $_POST['type'] ?? '';
    $note = trim($_POST['note'] ?? '');

    if (
        !$productId ||
        !$quantity ||
        $quantity < 1 ||
        !in_array($type, ['IN', 'OUT'], true)
    ) {
        $message = 'Pilih produk dan masukkan jumlah positif.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT stock FROM products WHERE id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $productId]);
            $product = $stmt->fetch();

            if (!$product) {
                throw new RuntimeException('Produk tidak ditemukan.');
            }

            if ($type === 'OUT' && $quantity > $product['stock']) {
                throw new RuntimeException('Stok tidak cukup untuk barang keluar.');
            }

            if ($type === 'IN') {
                $update = $pdo->prepare(
                    'UPDATE products SET stock = stock + :quantity WHERE id = :id'
                );
            } else {
                $update = $pdo->prepare(
                    'UPDATE products SET stock = stock - :quantity WHERE id = :id'
                );
            }

            $update->execute([
                'quantity' => $quantity,
                'id' => $productId,
            ]);

            $movement = $pdo->prepare(
                'INSERT INTO stock_movements
                    (product_id, user_id, type, quantity, note)
                 VALUES
                    (:product_id, :user_id, :type, :quantity, :note)'
            );

            $movement->execute([
                'product_id' => $productId,
                'user_id' => $_SESSION['user']['id'],
                'type' => $type,
                'quantity' => $quantity,
                'note' => $note ?: null,
            ]);

            $pdo->commit();

            $_SESSION['flash'] = 'Transaksi berhasil dicatat.';
            header('Location: stock.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = $exception->getMessage();
        }
    }
}

$products = $pdo
    ->query('SELECT id, sku, name, stock FROM products ORDER BY name')
    ->fetchAll();

$movements = $pdo->query(
    'SELECT stock_movements.type, stock_movements.quantity,
            stock_movements.note, stock_movements.created_at,
            products.name AS product_name, users.full_name
     FROM stock_movements
     JOIN products ON products.id = stock_movements.product_id
     JOIN users ON users.id = stock_movements.user_id
     ORDER BY stock_movements.id DESC
     LIMIT 20'
)->fetchAll();
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Transaksi Stok</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Transaksi stok</h1>
    <p><a href="dashboard.php">Kembali ke dashboard</a></p>

    <?php if ($message): ?>
        <p><?= e($message) ?></p>
    <?php endif; ?>

    <h2>Catat transaksi</h2>

    <form method="post">
        <?= csrf_field() ?>

        <label>
            Produk
            <select name="product_id" required>
                <option value="">Pilih produk</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= e($product['id']) ?>">
                        <?= e($product['sku'] . ' — ' . $product['name']
                            . ' (stok: ' . $product['stock'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Jenis
            <select name="type">
                <option value="IN">Barang masuk</option>
                <option value="OUT">Barang keluar</option>
            </select>
        </label>

        <label>
            Jumlah
            <input type="number" name="quantity" min="1" required>
        </label>

        <label>
            Catatan
            <input name="note">
        </label>

        <button type="submit">Simpan transaksi</button>
    </form>

    <h2>Riwayat transaksi</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>Waktu</th>
            <th>Produk</th>
            <th>Jenis</th>
            <th>Jumlah</th>
            <th>Catatan</th>
            <th>Petugas</th>
        </tr>

        <?php foreach ($movements as $movement): ?>
            <tr>
                <td><?= e($movement['created_at']) ?></td>
                <td><?= e($movement['product_name']) ?></td>
                <td><?= e($movement['type'] === 'IN' ? 'Masuk' : 'Keluar') ?></td>
                <td><?= e($movement['quantity']) ?></td>
                <td><?= e($movement['note']) ?></td>
                <td><?= e($movement['full_name']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>