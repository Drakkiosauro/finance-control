<?php
session_start();
require_once __DIR__ . '/includes/security.php';
security_headers();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $_SESSION = [];
    session_destroy();
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sair — Finanças</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-box" style="text-align:center;">
        <h1>Sair</h1>
        <p>Tem certeza que deseja sair?</p>
        <form method="POST" style="margin-top:24px;">
            <?php csrf_field(); ?>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">Sim, sair</button>
            <a href="dashboard.php" class="btn btn-secondary" style="width:100%;justify-content:center;padding:12px;margin-top:8px;">Cancelar</a>
        </form>
    </div>
</body>
</html>
