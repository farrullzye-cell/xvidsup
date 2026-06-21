<?php require_once __DIR__ . '/../config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === ADMIN_USER && $password === ADMIN_PASS) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - <?= $site_title ?></title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="login-page">

<div class="login-box">
    <h1>Admin <?= $site_title ?></h1>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <a href="../index.php" class="back-link">Kembali ke Beranda</a>
</div>


<script>
window.addEventListener('scroll',function(){var n=document.getElementById('navbar');if(n&&window.scrollY>50)n.classList.add('scrolled');else if(n)n.classList.remove('scrolled');});
</script>
</body>
</html>
