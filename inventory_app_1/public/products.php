<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

require_login();

$error = '';

/*
|--------------------------------------------------------------------------
| Simpan produk saat form dikirim
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    verify_csrf();

    $categoryId = filter_var(
        $_POST['category_id'] ?? '',
        FILTER_VALIDATE_INT
    );

    $sku = strtoupper(trim($_POST['sku'] ?? ''));
    $name = trim($_POST['name'] ?? '');

    $price = filter_var(
        $_POST['price'] ?? '',
        FILTER_VALIDATE_FLOAT
    );

    $stock = filter_var(
        $_POST['stock'] ?? '',
        FILTER_VALIDATE_INT
    );

    $minimumStock = filter_var(
        $_POST['minimum_stock'] ?? '',
        FILTER_VALIDATE_INT
    );

    if (
        !$categoryId ||
        $sku === '' ||
        $name === '' ||
        $price === false || $price < 0 ||
        $stock === false || $stock < 0 ||
        $minimumStock === false || $minimumStock < 0
    ) {
        $error = 'Periksa isian. Harga dan stok tidak boleh negatif.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO products
                    (category_id, sku, name, price, stock, minimum_stock)
                 VALUES
                    (:category_id, :sku, :name, :price, :stock, :minimum_stock)'
            );

            $stmt->execute([
                'category_id' => $categoryId,
                'sku' => $sku,
                'name' => $name,
                'price' => $price,
                'stock' => $stock,
                'minimum_stock' => $minimumStock,
            ]);

            $productId = (int) $pdo->lastInsertId();
            $supplierIds = $_POST['suppliers'] ?? [];

            if (!is_array($supplierIds)) {
                $supplierIds = [];
            }

            $linkSupplier = $pdo->prepare(
                'INSERT INTO product_suppliers
                    (product_id, supplier_id)
                 VALUES
                    (:product_id, :supplier_id)'
            );

            foreach (array_unique(array_map('intval', $supplierIds)) as $supplierId) {
                if ($supplierId > 0) {
                    $linkSupplier->execute([
                        'product_id' => $productId,
                        'supplier_id' => $supplierId,
                    ]);
                }
            }

            $pdo->commit();

            header('Location: products.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Produk gagal disimpan. SKU mungkin sudah digunakan atau supplier tidak valid.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Ambil kategori dan supplier untuk pilihan form
|--------------------------------------------------------------------------
*/
$categories = $pdo
    ->query('SELECT id, name FROM categories ORDER BY name')
    ->fetchAll();

$suppliers = $pdo
    ->query('SELECT id, name FROM suppliers ORDER BY name')
    ->fetchAll();

/*
|--------------------------------------------------------------------------
| Siapkan pencarian, filter, pengurutan, dan pagination
|--------------------------------------------------------------------------
*/
$search = trim($_GET['search'] ?? '');

$categoryFilter = filter_var(
    $_GET['category'] ?? '',
    FILTER_VALIDATE_INT
);

$allowedSorts = [
    'name' => 'products.name',
    'sku' => 'products.sku',
    'price' => 'products.price',
    'stock' => 'products.stock',
];

$sortKey = $_GET['sort'] ?? 'name';

if (!is_string($sortKey) || !isset($allowedSorts[$sortKey])) {
    $sortKey = 'name';
}

$sortColumn = $allowedSorts[$sortKey];

$direction = strtoupper($_GET['direction'] ?? 'ASC');

if (!in_array($direction, ['ASC', 'DESC'], true)) {
    $direction = 'ASC';
}

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(products.name LIKE :search_name
                      OR products.sku LIKE :search_sku)';

    $params['search_name'] = '%' . $search . '%';
    $params['search_sku'] = '%' . $search . '%';
}

if ($categoryFilter !== false && $categoryFilter !== null) {
    $conditions[] = 'products.category_id = :category_id';
    $params['category_id'] = $categoryFilter;
}

$where = $conditions
    ? 'WHERE ' . implode(' AND ', $conditions)
    : '';

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM products $where"
);
$countStmt->execute($params);

$totalProducts = (int) $countStmt->fetchColumn();

$perPage = 5;
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$totalPages = max(1, (int) ceil($totalProducts / $perPage));

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| Ambil daftar produk dan nama supplier terkait
|--------------------------------------------------------------------------
*/
$sql = "SELECT
            products.id,
            products.sku,
            products.name,
            products.price,
            products.stock,
            products.minimum_stock,
            categories.name AS category,
            COALESCE(
                GROUP_CONCAT(DISTINCT suppliers.name ORDER BY suppliers.name SEPARATOR ', '),
                'Belum dipilih'
            ) AS supplier_names
        FROM products
        JOIN categories
            ON categories.id = products.category_id
        LEFT JOIN product_suppliers
            ON product_suppliers.product_id = products.id
        LEFT JOIN suppliers
            ON suppliers.id = product_suppliers.supplier_id
        $where
        GROUP BY
            products.id,
            products.sku,
            products.name,
            products.price,
            products.stock,
            products.minimum_stock,
            categories.name
        ORDER BY $sortColumn $direction
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll();

?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk | Inventory</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h1>Produk</h1>

    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="categories.php">Kategori</a>
        <a href="suppliers.php">Supplier</a>
        <a href="stock.php">Transaksi stok</a>
    </nav>

    <?php if ($error): ?>
        <p><?= e($error) ?></p>
    <?php endif; ?>

    <?php if ($_SESSION['user']['role'] === 'admin'): ?>
        <h2>Tambah produk</h2>

        <form method="post">
            <?= csrf_field() ?>

            <label>
                SKU
                <input name="sku" required>
            </label>

            <label>
                Nama produk
                <input name="name" required>
            </label>

            <label>
                Kategori
                <select name="category_id" required>
                    <option value="">Pilih kategori</option>

                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['id']) ?>">
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Harga
                <input
                    type="number"
                    name="price"
                    min="0"
                    step="0.01"
                    required
                >
            </label>

            <label>
                Stok awal
                <input
                    type="number"
                    name="stock"
                    min="0"
                    value="0"
                    required
                >
            </label>

            <label>
                Stok minimum
                <input
                    type="number"
                    name="minimum_stock"
                    min="0"
                    value="5"
                    required
                >
            </label>

            <label>
                Supplier
                <select name="suppliers[]" multiple>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= e($supplier['id']) ?>">
                            <?= e($supplier['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>Tahan Ctrl untuk memilih lebih dari satu supplier.</small>
            </label>

            <button type="submit">Simpan produk</button>
        </form>
    <?php endif; ?>

    <h2>Daftar produk</h2>

    <form method="get">
        <label>
            Cari nama atau SKU
            <input name="search" value="<?= e($search) ?>">
        </label>

        <label>
            Kategori
            <select name="category">
                <option value="">Semua kategori</option>

                <?php foreach ($categories as $category): ?>
                    <option
                        value="<?= e($category['id']) ?>"
                        <?= (int) $categoryFilter === (int) $category['id']
                            ? 'selected'
                            : '' ?>
                    >
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Urutkan
            <select name="sort">
                <option value="name" <?= $sortKey === 'name' ? 'selected' : '' ?>>
                    Nama
                </option>
                <option value="sku" <?= $sortKey === 'sku' ? 'selected' : '' ?>>
                    SKU
                </option>
                <option value="price" <?= $sortKey === 'price' ? 'selected' : '' ?>>
                    Harga
                </option>
                <option value="stock" <?= $sortKey === 'stock' ? 'selected' : '' ?>>
                    Stok
                </option>
            </select>
        </label>

        <label>
            Arah urutan
            <select name="direction">
                <option value="ASC" <?= $direction === 'ASC' ? 'selected' : '' ?>>
                    Naik
                </option>
                <option value="DESC" <?= $direction === 'DESC' ? 'selected' : '' ?>>
                    Turun
                </option>
            </select>
        </label>

        <button type="submit">Terapkan</button>
    </form>

    <table>
        <tr>
            <th>SKU</th>
            <th>Nama</th>
            <th>Kategori</th>
            <th>Supplier</th>
            <th>Harga</th>
            <th>Stok</th>
            <th>Status stok</th>
        </tr>

        <?php foreach ($products as $product): ?>
            <tr>
                <td><?= e($product['sku']) ?></td>
                <td><?= e($product['name']) ?></td>
                <td><?= e($product['category']) ?></td>
                <td><?= e($product['supplier_names']) ?></td>
                <td><?= e($product['price']) ?></td>
                <td><?= e($product['stock']) ?></td>
                <td>
                    <?= $product['stock'] <= $product['minimum_stock']
                        ? 'Stok rendah'
                        : 'Aman' ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <p>
        Halaman <?= e($currentPage) ?> dari <?= e($totalPages) ?>
        — total <?= e($totalProducts) ?> produk
    </p>

    <?php if ($currentPage > 1): ?>
        <a href="?search=<?= urlencode($search) ?>&category=<?= urlencode((string) $categoryFilter) ?>&sort=<?= e($sortKey) ?>&direction=<?= e($direction) ?>&page=<?= $currentPage - 1 ?>">
            Sebelumnya
        </a>
    <?php endif; ?>

    <?php if ($currentPage < $totalPages): ?>
        <a href="?search=<?= urlencode($search) ?>&category=<?= urlencode((string) $categoryFilter) ?>&sort=<?= e($sortKey) ?>&direction=<?= e($direction) ?>&page=<?= $currentPage + 1 ?>">
            Berikutnya
        </a>
    <?php endif; ?>

</body>
</html>