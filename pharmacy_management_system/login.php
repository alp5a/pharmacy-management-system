<?php
require_once 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - <?= htmlspecialchars(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: linear-gradient(135deg,#0d6efd11,#20c99711); min-height:100vh; display:flex; align-items:center; }
.login-card { max-width: 400px; margin: auto; }
</style>
</head>
<body>
<div class="container">
    <div class="card login-card shadow">
        <div class="card-body p-4">
            <h3 class="text-center mb-1">💊 <?= htmlspecialchars(APP_NAME) ?></h3>
            <p class="text-center text-muted mb-4">Pharmacy Management System</p>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Log In</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
