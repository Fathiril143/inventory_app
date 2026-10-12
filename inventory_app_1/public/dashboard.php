<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

require_login();

$user = $_SESSION['user'];

$stats = $pdo->query(
    'SELECT
        COUNT(*) AS total_products,
        COALESCE(SUM(stock), 0) AS total_stock,
        COALESCE(SUM(stock <= minimum_stock), 0) AS low_stock
     FROM products'
)->fetch();

$categoryReport = $pdo->query(
    'SELECT
        categories.name,
        COUNT(products.id) AS product_count,
        COALESCE(SUM(products.stock), 0) AS total_stock,
        COALESCE(AVG(products.price), 0) AS average_price
     FROM categories
     LEFT JOIN products
        ON products.category_id = categories.id
     GROUP BY categories.id, categories.name
     ORDER BY categories.name'
)->fetchAll();

$recentMovements = $pdo->query(
    'SELECT
        products.name AS product_name,
        stock_movements.type,
        stock_movements.quantity,
        stock_movements.created_at,
        users.full_name
     FROM stock_movements
     JOIN products
        ON products.id = stock_movements.product_id
     JOIN users
        ON users.id = stock_movements.user_id
     ORDER BY stock_movements.id DESC
     LIMIT 5'
)->fetchAll();
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Inventory</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Inventory Management System</h1>

    <p>
        Halo, <?= e($user['name']) ?>.
        Role kamu: <?= e($user['role']) ?>.
    </p>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Produk</a>
        <a href="stock.php">Transaksi stok</a>

        <?php if ($user['role'] === 'admin'): ?>
            <a href="categories.php">Kategori</a>
            <a href="suppliers.php">Supplier</a>
            <a href="users.php">Pengguna</a>
        <?php endif; ?>

        <a href="logout.php">Keluar</a>
    </nav>

    <h2>Ringkasan stok</h2>

    <div class="stats">
        <section class="card">
            <p>Jumlah produk</p>
            <strong><?= e($stats['total_products']) ?></strong>
        </section>

        <section class="card">
            <p>Total unit stok</p>
            <strong><?= e($stats['total_stock']) ?></strong>
        </section>

        <section class="card">
            <p>Produk stok rendah</p>
            <strong><?= e($stats['low_stock']) ?></strong>
        </section>
    </div>

    <h2>Ringkasan per kategori</h2>

    <table>
        <tr>
            <th>Kategori</th>
            <th>Jumlah produk</th>
            <th>Total stok</th>
            <th>Rata-rata harga</th>
        </tr>

        <?php foreach ($categoryReport as $row): ?>
            <tr>
                <td><?= e($row['name']) ?></td>
                <td><?= e($row['product_count']) ?></td>
                <td><?= e($row['total_stock']) ?></td>
                <td>
                    Rp <?= number_format((float) $row['average_price'], 0, ',', '.') ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Transaksi stok terbaru</h2>

    <table>
        <tr>
            <th>Waktu</th>
            <th>Produk</th>
            <th>Jenis</th>
            <th>Jumlah</th>
            <th>Petugas</th>
        </tr>

        <?php foreach ($recentMovements as $movement): ?>
            <tr>
                <td><?= e($movement['created_at']) ?></td>
                <td><?= e($movement['product_name']) ?></td>
                <td>
                    <?= e($movement['type'] === 'IN' ? 'Masuk' : 'Keluar') ?>
                </td>
                <td><?= e($movement['quantity']) ?></td>
                <td><?= e($movement['full_name']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>