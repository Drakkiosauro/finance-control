<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

atualizarStatusDividas();
gerarContasFixas();

$resumo = resumoDashboard();
$proximos = obterProximosVencimentos(15);
$qtdProximos = count($proximos);

$db = getDB();
$usuario_id = $_SESSION['usuario_id'];
$stmt = $db->prepare("SELECT salario FROM usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$salario = (float) ($stmt->fetchColumn() ?: 0);

$totalDevido = $resumo['total_devido'];
$percentComprometido = $salario > 0 ? round($totalDevido / $salario * 100) : 0;

$tituloPagina = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></h1>
        <p>Resumo financeiro do momento</p>
    </div>
</div>

<div class="card-grid">
    <div class="card card-stat">
        <div class="stat-label">Total Devido</div>
        <div class="stat-value stat-red"><?php echo formatarMoeda($resumo['total_devido']); ?></div>
    </div>
    <div class="card card-stat">
        <div class="stat-label">Total Pago</div>
        <div class="stat-value stat-green"><?php echo formatarMoeda($resumo['total_pago']); ?></div>
    </div>
    <div class="card card-stat">
        <div class="stat-label">Dívidas Pendentes</div>
        <div class="stat-value"><?php echo $resumo['qtd_dividas']; ?></div>
    </div>
    <div class="card card-stat">
        <div class="stat-label">Atrasadas</div>
        <div class="stat-value stat-red"><?php echo $resumo['qtd_atrasadas']; ?></div>
    </div>
    <?php if ($salario > 0): ?>
    <div class="card card-stat">
        <div class="stat-label">Salário</div>
        <div class="stat-value"><?php echo formatarMoeda($salario); ?></div>
    </div>
    <div class="card card-stat">
        <div class="stat-label">Comprometido</div>
        <div class="stat-value <?php echo $percentComprometido > 70 ? 'stat-red' : ($percentComprometido > 40 ? 'stat-yellow' : 'stat-green'); ?>">
            <?php echo $percentComprometido; ?>%
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($salario > 0): ?>
<div class="card" style="margin-bottom:32px;">
    <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Salário vs Dívidas</h2>
    <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center;">
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:0.8rem;color:var(--gray-500);">Dívidas</span>
                <span style="font-size:0.8rem;font-weight:600;color:var(--red);"><?php echo formatarMoeda($totalDevido); ?></span>
            </div>
            <div class="progress-bar" style="height:20px;background:var(--gray-100);border-radius:4px;">
                <div class="progress-bar-fill" style="width:<?php echo min(100, $percentComprometido); ?>%;background:<?php echo $percentComprometido > 70 ? 'var(--red)' : ($percentComprometido > 40 ? 'var(--yellow)' : 'var(--green)'); ?>;border-radius:4px;"></div>
            </div>
        </div>
        <div style="flex:1;min-width:200px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                <span style="font-size:0.8rem;color:var(--gray-500);">Disponível</span>
                <span style="font-size:0.8rem;font-weight:600;color:var(--green);"><?php echo formatarMoeda(max(0, $salario - $totalDevido)); ?></span>
            </div>
            <div class="progress-bar" style="height:20px;background:var(--gray-100);border-radius:4px;">
                <div class="progress-bar-fill" style="width:<?php echo min(100, 100 - $percentComprometido); ?>%;background:var(--gray-300);border-radius:4px;"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card-grid" style="grid-template-columns: 1fr 1fr;">
    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Próximos Vencimentos</h2>
        <?php if (empty($proximos)): ?>
            <div class="empty-state" style="padding:24px;">
                <p style="color:var(--gray-400);font-size:0.875rem;">Nenhum vencimento nos próximos 15 dias</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper" style="border:none;">
                <table>
                    <thead>
                        <tr>
                            <th>Credor</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proximos as $d): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($d['credor']); ?></strong></td>
                                <td><?php echo formatarMoeda($d['saldo_restante']); ?></td>
                                <td><?php echo formatarData($d['data_vencimento']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo classeStatus($d['status']); ?>">
                                        <span class="status-dot"></span>
                                        <?php echo ucfirst($d['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Ações Rápidas</h2>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <a href="divida_form.php" class="btn btn-primary">Nova Dívida</a>
            <a href="pagamento_form.php" class="btn btn-secondary">Registrar Pagamento</a>
            <a href="cartao_form.php" class="btn btn-secondary">Novo Cartão</a>
            <a href="calendario.php" class="btn btn-secondary">Ver Calendário</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
