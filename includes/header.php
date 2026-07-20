<?php require_once __DIR__ . '/security.php'; security_headers(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tituloPagina ?? 'Controle Financeiro'; ?> — Finanças</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" nonce="chartjs"></script>
</head>
<body>
    <nav class="nav">
        <div class="nav-inner">
            <a href="dashboard.php" class="nav-logo">Finanças</a>
            <button class="nav-toggle" id="navToggle" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-links" id="navLinks">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="dividas.php">Dívidas</a></li>
                <li><a href="cartoes.php">Cartões</a></li>
                <li><a href="contas_fixas.php">Contas Fixas</a></li>
                <li><a href="calendario.php">Calendário</a></li>
                <li><a href="renda.php">Renda</a></li>
                <li><a href="relatorios.php">Relatórios</a></li>
                <li><a href="categorias.php">Categorias</a></li>
                <li><a href="backup.php">Backup</a></li>
                <li><a href="logout.php" class="nav-sair">Sair</a></li>
            </ul>
        </div>
    </nav>
    <main class="main">
