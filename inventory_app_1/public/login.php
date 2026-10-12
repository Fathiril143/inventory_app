<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/functions.php';

if (!empty($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        'SELECT id, username, password_hash, full_name, role
         FROM users
         WHERE username = :username'
    );
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['full_name'],
            'role' => $user['role'],
        ];

        header('Location: dashboard.php');
        exit;
    }

    $error = 'Username atau password salah.';
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login Inventory</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Masuk ke Inventory</h1>

    <?php if ($error): ?>
        <p><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <label>
            Username
            <input name="username" required>
        </label>

        <br>

        <label>
            Password
            <input type="password" name="password" required>
        </label>

        <br>

        <button type="submit">Masuk</button>
    </form>
</body>
</html>