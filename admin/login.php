<?php
require_once __DIR__ . '/_bootstrap.php';
if (is_admin()) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    if (hash_equals(ADMIN_EMAIL, $email) && hash_equals(ADMIN_PASSWORD, $password)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_name'] = 'Admin';
        header('Location: dashboard.php'); exit;
    }
    $error = 'That email or password is not correct.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin login | GreensPk</title><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet"><link rel="stylesheet" href="admin.css"></head>
<body class="login-page"><main class="login-box"><a class="admin-brand" href="../index.php">🥬 Greens<span>Pk</span></a><p class="kicker">ADMIN PORTAL</p><h1>Welcome back.</h1><p class="muted">Sign in to manage your fresh market.</p><?php if ($error): ?><div class="alert"><?= clean($error) ?></div><?php endif; ?><form method="post"><label>Email<input name="email" type="email" autocomplete="email" required placeholder="admin@Greens.pk"></label><label>Password<input name="password" type="password" autocomplete="current-password" required placeholder="••••••••"></label><button class="primary" type="submit">Sign in to dashboard</button></form><p class="login-hint">Demo: admin@greens.pk · Fresh@123</p></main></body></html>
