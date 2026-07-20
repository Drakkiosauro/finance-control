<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$ano = (int) ($_GET['ano'] ?? date('Y'));
$mes = (int) ($_GET['mes'] ?? date('m'));

// Totais por categoria
$stmt = $db->query("
    SELECT c.nome as categoria, 
           COUNT(d.id) as total_dividas,
           COALESCE(SUM(d.valor_total), 0) as valor_total,
           COALESCE(SUM(CASE WHEN d.status = 'paga' THEN d.valor_total ELSE 0 END), 0) as valor_pago,
           COALESCE(SUM(d.saldo_restante), 0) as saldo_restante
    FROM categorias c
    LEFT JOIN dividas d ON d.id_categoria = c.id
    WHERE c.tipo != 'cartao'
    GROUP BY c.id, c.nome
    ORDER BY valor_total DESC
");
$porCategoria = $stmt->fetchAll();

// Totais por mes
$stmt = $db->prepare("
    SELECT strftime('%m', data_vencimento) as mes_num,
           COUNT(*) as total,
           COALESCE(SUM(valor_total), 0) as valor_total,
           COALESCE(SUM(CASE WHEN status = 'paga' THEN valor_total ELSE 0 END), 0) as valor_pago
    FROM dividas
    WHERE strftime('%Y', data_vencimento) = ?
    GROUP BY mes_num
    ORDER BY mes_num
");
$stmt->execute([(string) $ano]);
$porMes = $stmt->fetchAll();

// Pagamentos do mes
$stmt = $db->prepare("
    SELECT p.*, d.credor
    FROM pagamentos p
    JOIN dividas d ON p.id_divida = d.id
    WHERE strftime('%Y-%m', p.data_pagamento) = ?
    ORDER BY p.data_pagamento DESC
");
$stmt->execute([sprintf('%04d-%02d', $ano, $mes)]);
$pagamentosMes = $stmt->fetchAll();

$totalPagoMes = array_sum(array_column($pagamentosMes, 'valor'));

// Resumo geral
$resumo = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM dividas) as total_dividas,
        (SELECT COUNT(*) FROM dividas WHERE status = 'paga') as pagas,
        (SELECT COUNT(*) FROM dividas WHERE status = 'pendente') as pendentes,
        (SELECT COUNT(*) FROM dividas WHERE status = 'atrasada') as atrasadas,
        (SELECT COALESCE(SUM(valor_total), 0) FROM dividas) as total_geral,
        (SELECT COALESCE(SUM(valor_total), 0) FROM dividas WHERE status = 'paga') as total_pago,
        (SELECT COALESCE(SUM(saldo_restante), 0) FROM dividas WHERE status != 'paga') as total_devendo
")->fetch();

$tituloPagina = 'Relatórios';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Relatórios</h1>
        <p>Visão geral e análise das suas finanças</p>
    </div>
    <div class="btn-group">
        <a href="exportar_csv.php?tipo=relatorio" class="btn btn-sm btn-secondary">CSV</a>
        <a href="exportar_pdf.php?tipo=relatorio" class="btn btn-sm btn-secondary">PDF</a>
        <a href="?ano=<?php echo $ano - 1; ?>" class="btn btn-sm btn-secondary"><?php echo $ano - 1; ?></a>
        <a href="?ano=<?php echo $ano; ?>" class="btn btn-sm btn-primary"><?php echo $ano; ?></a>
        <a href="?ano=<?php echo $ano + 1; ?>" class="btn btn-sm btn-secondary"><?php echo $ano + 1; ?></a>
    </div>
</div>

<div class="card-grid">
    <div class="card card-stat">
        <div class="stat-label">Total de Dívidas</div>
        <div class="stat-value"><?php echo $resumo['total_dividas']; ?></div>
    </div>
    <div class="card card-stat">
        <div class="stat-label">Pagas</div>
        <div class="stat-value stat-green"><?php echo $resumo['pagas']; ?></div>
    </div>
    <div class="card card-stat">
        <div class="stat-label">Pendentes</div>
        <div class="stat-value stat-yellow"><?php echo $resumo['pendentes']; ?></div>
    </div>
    <div class="card card-stat">
        <div class="stat-label">Atrasadas</div>
        <div class="stat-value stat-red"><?php echo $resumo['atrasadas']; ?></div>
    </div>
</div>

<?php
$temDados = !empty($porCategoria) && array_filter($porCategoria, fn($c) => $c['total_dividas'] > 0);
$temMensal = !empty($porMes);
$nomes = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
?>

<?php if ($temDados): ?>
<div class="card" style="margin-bottom:24px;">
    <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Dívidas por Categoria</h2>
    <div style="max-width:500px;margin:0 auto;">
        <canvas id="chartCategorias" height="250"></canvas>
    </div>
</div>
<?php endif; ?>

<div class="report-grid">
    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Por Categoria</h2>
        <?php if (!$temDados): ?>
            <p style="color:var(--gray-400);font-size:0.875rem;">Nenhum dado disponível.</p>
        <?php else: ?>
            <div class="table-wrapper" style="border:none;">
                <table>
                    <thead>
                        <tr>
                            <th>Categoria</th>
                            <th>Qtd</th>
                            <th>Total</th>
                            <th>Pago</th>
                            <th>Restante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($porCategoria as $cat): if ($cat['total_dividas'] == 0) continue; ?>
                            <tr>
                                <td><span class="tag"><?php echo htmlspecialchars($cat['categoria']); ?></span></td>
                                <td><?php echo $cat['total_dividas']; ?></td>
                                <td><?php echo formatarMoeda($cat['valor_total']); ?></td>
                                <td><?php echo formatarMoeda($cat['valor_pago']); ?></td>
                                <td><strong><?php echo formatarMoeda($cat['saldo_restante']); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">
            Pagamentos de <?php echo str_pad($mes, 2, '0', STR_PAD_LEFT); ?>/<?php echo $ano; ?>
        </h2>
        <p style="font-size:1.5rem;font-weight:700;margin-bottom:16px;color:var(--green);">
            <?php echo formatarMoeda($totalPagoMes); ?>
        </p>
        <?php if (empty($pagamentosMes)): ?>
            <p style="color:var(--gray-400);font-size:0.875rem;">Nenhum pagamento neste mês.</p>
        <?php else: ?>
            <div class="table-wrapper" style="border:none;">
                <table>
                    <thead>
                        <tr>
                            <th>Credor</th>
                            <th>Valor</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagamentosMes as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['credor']); ?></td>
                                <td><?php echo formatarMoeda($p['valor']); ?></td>
                                <td><?php echo formatarData($p['data_pagamento']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($temMensal): ?>
<div class="card" style="margin-bottom:24px;">
    <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Evolução Mensal — <?php echo $ano; ?></h2>
    <canvas id="chartMensal" height="280"></canvas>
</div>
<?php endif; ?>

<div class="card">
    <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Resumo Mensal — <?php echo $ano; ?></h2>
    <?php if (!$temMensal): ?>
        <p style="color:var(--gray-400);font-size:0.875rem;">Nenhum dado para este ano.</p>
    <?php else: ?>
        <div class="table-wrapper" style="border:none;">
            <table>
                <thead>
                    <tr>
                        <th>Mês</th>
                        <th>Dívidas</th>
                        <th>Valor Total</th>
                        <th>Valor Pago</th>
                        <th>% Pago</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($porMes as $m): 
                        $percent = $m['valor_total'] > 0 ? round($m['valor_pago'] / $m['valor_total'] * 100) : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo $nomes[(int)$m['mes_num'] - 1]; ?></strong></td>
                            <td><?php echo $m['total']; ?></td>
                            <td><?php echo formatarMoeda($m['valor_total']); ?></td>
                            <td><?php echo formatarMoeda($m['valor_pago']); ?></td>
                            <td><?php echo $percent; ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
// Build chart data safely using json_encode (prevents XSS in JS context)
$catLabels = [];
$catData = [];
foreach ($porCategoria as $cat) {
    if ($cat['total_dividas'] == 0) continue;
    $catLabels[] = $cat['categoria'];
    $catData[] = (float) $cat['saldo_restante'];
}

$mesLabels = [];
$mesTotal = [];
$mesPago = [];
foreach ($porMes as $m) {
    $mesLabels[] = $nomes[(int)$m['mes_num'] - 1];
    $mesTotal[] = (float) $m['valor_total'];
    $mesPago[] = (float) $m['valor_pago'];
}
?>
<script>
<?php if ($temDados): ?>
new Chart(document.getElementById('chartCategorias'), {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($catLabels); ?>,
        datasets: [{
            data: <?php echo json_encode($catData); ?>,
            backgroundColor: ['#000','#404040','#606060','#909090','#b0b0b0','#d0d0d0','#202020','#303030','#808080','#a0a0a0'],
            borderWidth: 0
        }]
    },
    options: {
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 12 }, color: '#404040' } }
        },
        cutout: '55%'
    }
});
<?php endif; ?>

<?php if ($temMensal): ?>
new Chart(document.getElementById('chartMensal'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($mesLabels); ?>,
        datasets: [
            {
                label: 'Valor Total',
                data: <?php echo json_encode($mesTotal); ?>,
                backgroundColor: '#000',
                borderRadius: 4
            },
            {
                label: 'Valor Pago',
                data: <?php echo json_encode($mesPago); ?>,
                backgroundColor: '#1a7d36',
                borderRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top', labels: { font: { size: 12 }, color: '#404040' } }
        },
        scales: {
            x: { grid: { display: false } },
            y: { 
                grid: { color: '#f0f0f0' },
                ticks: { callback: function(v) { return 'R$' + v.toFixed(0); } }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
