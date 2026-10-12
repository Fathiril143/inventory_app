<?php

require_once __DIR__ . '/../config/database.php';

$tables = $pdo->query('SHOW TABLES')->fetchAll();

echo '<h1>Koneksi berhasil!</h1>';
echo '<p>Tabel di database:</p><ul>';

foreach ($tables as $table) {
    $tableName = array_values($table)[0];
    echo '<li>' . htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') . '</li>';
}

echo '</ul>';