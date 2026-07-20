<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$db = getDB();
$erro = '';
$sucesso = '';

$usuario_id = $_SESSION['usuario_id'];

// Add salary columns if not exists
$cols = $db->query("PRAGMA table_info(usuarios)")->fetchAll(PDO::FETCH_COLUMN, 1);
if (!in_array('salario', $cols)) $db->exec("ALTER TABLE usuarios ADD COLUMN salario REAL DEFAULT 0");
if (!in_array('salario_data', $cols)) $db->exec("ALTER TABLE usuarios ADD COLUMN salario_data TEXT");

$stmt = $db->prepare("SELECT salario, salario_data FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $salario = str_replace(',', '.', $_POST['salario'] ?? '0');
    $salario_data = $_POST['salario_data'] ?? date('Y-m-d');

    if ($salario < 0) {
        $erro = 'Salário não pode ser negativo.';
    } else {
        $stmt = $db->prepare("UPDATE usuarios SET salario = ?, salario_data = ? WHERE id = ?");
        $stmt->execute([$salario, $salario_data, $usuario_id]);
        backup_db();
        $_SESSION['usuario_salario'] = $salario;
        $sucesso = 'Salário atualizado com sucesso!';
        $usuario['salario'] = $salario;
        $usuario['salario_data'] = $salario_data;
    }
}

$totalDevido = $db->query("SELECT COALESCE(SUM(saldo_restante), 0) FROM dividas WHERE status != 'paga'")->fetchColumn();
$totalPago = $db->query("SELECT COALESCE(SUM(valor), 0) FROM pagamentos")->fetchColumn();
$salario = $usuario['salario'] ?? 0;

$tituloPagina = 'Renda';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Renda</h1>
        <p>Cadastre seu salário para comparar com as dívidas</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alert alert-error"><?php echo $erro; ?></div>
<?php endif; ?>
<?php if ($sucesso): ?>
    <div class="alert alert-success"><?php echo $sucesso; ?></div>
<?php endif; ?>

<div class="card-grid" style="grid-template-columns:1fr 1fr;">
    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:20px;">Seu Salário</h2>
        <form method="POST">
            <?php csrf_field(); ?>
            <div class="form-group" style="margin-bottom:16px;">
                <label for="salario">Salário / Renda Mensal</label>
                <input type="number" step="0.01" min="0" id="salario" name="salario" placeholder="0,00" required value="<?php echo $salario ? number_format($salario, 2, '.', '') : ''; ?>">
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <label for="salario_data">Referente ao mês</label>
                <input type="month" id="salario_data" name="salario_data" value="<?php echo $usuario['salario_data'] ? substr($usuario['salario_data'], 0, 7) : date('Y-m'); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Salvar</button>
        </form>
    </div>

    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:20px;">Comparativo</h2>
        <?php if ($salario > 0): ?>
            <div style="display:flex;flex-direction:column;gap:16px;">
                <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--gray-100);">
                    <span style="color:var(--gray-500);">Salário</span>
                    <span style="font-weight:700;font-size:1.1rem;"><?php echo formatarMoeda($salario); ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--gray-100);">
                    <span style="color:var(--gray-500);">Total devido</span>
                    <span style="font-weight:700;font-size:1.1rem;color:var(--red);"><?php echo formatarMoeda($totalDevido); ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--gray-100);">
                    <span style="color:var(--gray-500);">Total pago</span>
                    <span style="font-weight:700;font-size:1.1rem;color:var(--green);"><?php echo formatarMoeda($totalPago); ?></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:12px 0;">
                    <span style="color:var(--gray-500);">Salário - Dívidas</span>
                    <?php $diferenca = $salario - $totalDevido; ?>
                    <span style="font-weight:700;font-size:1.25rem;color:<?php echo $diferenca >= 0 ? 'var(--green)' : 'var(--red)'; ?>;">
                        <?php echo formatarMoeda($diferenca); ?>
                    </span>
                </div>

                <div style="margin-top:8px;">
                    <p style="font-size:0.875rem;color:var(--gray-400);margin-bottom:8px;">
                        Comprometimento da renda
                    </p>
                    <?php $percent = $salario > 0 ? round($totalDevido / $salario * 100) : 0; ?>
                    <div class="progress-bar" style="height:12px;">
                        <div class="progress-bar-fill" style="width:<?php echo min(100, $percent); ?>%;background:<?php echo $percent > 70 ? 'var(--red)' : ($percent > 40 ? 'var(--yellow)' : 'var(--green)'); ?>;"></div>
                    </div>
                    <p style="text-align:right;font-size:0.8rem;color:var(--gray-400);margin-top:4px;">
                        <?php echo $percent; ?>% comprometido
                        <?php if ($percent > 70): ?>
                            <span style="color:var(--red);"> — Alerta!</span>
                        <?php elseif ($percent > 40): ?>
                            <span style="color:var(--yellow);"> — Atenção</span>
                        <?php else: ?>
                            <span style="color:var(--green);"> — Saudável</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state" style="padding:20px;">
                <p style="font-size:0.875rem;color:var(--gray-400);">Cadastre seu salário ao lado para ver o comparativo.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($salario > 0 && $totalDevido > 0): ?>
<div class="card-grid" style="grid-template-columns:1fr 1fr;margin-top:16px;">
    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Distribuição</h2>
        <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
            <div style="flex:1;min-width:200px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:0.875rem;">Dívidas</span>
                    <span style="font-size:0.875rem;font-weight:600;"><?php echo formatarMoeda($totalDevido); ?></span>
                </div>
                <div class="progress-bar" style="height:24px;background:var(--gray-100);">
                    <div class="progress-bar-fill" style="width:<?php echo min(100, $percent); ?>%;background:var(--red);border-radius:3px;"></div>
                </div>
            </div>
            <div style="flex:1;min-width:200px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="font-size:0.875rem;">Disponível</span>
                    <span style="font-size:0.875rem;font-weight:600;"><?php echo formatarMoeda(max(0, $salario - $totalDevido)); ?></span>
                </div>
                <div class="progress-bar" style="height:24px;background:var(--gray-100);">
                    <div class="progress-bar-fill" style="width:<?php echo min(100, 100 - $percent); ?>%;background:var(--green);border-radius:3px;"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Gráfico</h2>
        <div style="max-width:300px;margin:0 auto;">
            <canvas id="chartRenda" height="250"></canvas>
        </div>
    </div>
</div>

<script>
new Chart(document.getElementById('chartRenda'), {
    type: 'doughnut',
    data: {
        labels: ['Dívidas', 'Disponível'],
        datasets: [{
            data: [<?php echo $totalDevido; ?>, <?php echo max(0, $salario - $totalDevido); ?>],
            backgroundColor: ['#c0392b', '#1a7d36'],
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
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
