<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
atualizarStatusDividas();

$filtro = $_GET['status'] ?? '';

if ($filtro) {
    $stmt = $db->prepare("
        SELECT d.*, c.nome as categoria_nome
        FROM dividas d
        LEFT JOIN categorias c ON d.id_categoria = c.id
        WHERE d.status = ?
        ORDER BY d.data_vencimento DESC
    ");
    $stmt->execute([$filtro]);
} else {
    $stmt = $db->query("
        SELECT d.*, c.nome as categoria_nome
        FROM dividas d
        LEFT JOIN categorias c ON d.id_categoria = c.id
        ORDER BY d.data_vencimento DESC
    ");
}

$dividas = $stmt->fetchAll();

$tituloPagina = 'Dívidas';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Dívidas</h1>
        <p>Gerencie todas as suas dívidas</p>
    </div>
    <div class="btn-group">
        <a href="divida_form.php" class="btn btn-primary">Nova Dívida</a>
        <a href="exportar_csv.php?tipo=dividas" class="btn btn-secondary">CSV</a>
        <a href="exportar_pdf.php?tipo=dividas" class="btn btn-secondary">PDF</a>
    </div>
</div>

<div class="btn-group" style="margin-bottom:24px;">
    <a href="dividas.php" class="btn btn-sm <?php echo $filtro === '' ? 'btn-primary' : 'btn-secondary'; ?>">Todas</a>
    <a href="dividas.php?status=pendente" class="btn btn-sm <?php echo $filtro === 'pendente' ? 'btn-primary' : 'btn-secondary'; ?>">Pendentes</a>
    <a href="dividas.php?status=atrasada" class="btn btn-sm <?php echo $filtro === 'atrasada' ? 'btn-primary' : 'btn-secondary'; ?>">Atrasadas</a>
    <a href="dividas.php?status=paga" class="btn btn-sm <?php echo $filtro === 'paga' ? 'btn-primary' : 'btn-secondary'; ?>">Pagas</a>
</div>

<?php if (empty($dividas)): ?>
    <div class="card empty-state">
        <p>Nenhuma dívida encontrada.</p>
        <a href="divida_form.php" class="btn btn-primary">Cadastrar Primeira Dívida</a>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Credor</th>
                    <th>Categoria</th>
                    <th>Total</th>
                    <th>Saldo Restante</th>
                    <th>Parcelas</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dividas as $d): 
                    $progresso = $d['valor_total'] > 0 ? round(($d['valor_total'] - $d['saldo_restante']) / $d['valor_total'] * 100) : 0;
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($d['credor']); ?></strong></td>
                        <td><span class="tag"><?php echo htmlspecialchars($d['categoria_nome'] ?? 'Sem categoria'); ?></span></td>
                        <td><?php echo formatarMoeda($d['valor_total']); ?></td>
                        <td>
                            <?php echo formatarMoeda($d['saldo_restante']); ?>
                            <div class="progress-bar">
                                <div class="progress-bar-fill" style="width:<?php echo $progresso; ?>%;background:<?php echo $d['status'] === 'paga' ? 'var(--green)' : ($d['status'] === 'atrasada' ? 'var(--red)' : 'var(--black)'); ?>"></div>
                            </div>
                        </td>
                        <td><?php echo $d['parcelas_pagas']; ?>/<?php echo $d['num_parcelas']; ?></td>
                        <td><?php echo formatarData($d['data_vencimento']); ?></td>
                        <td>
                            <span class="status-badge <?php echo classeStatus($d['status']); ?>">
                                <span class="status-dot"></span>
                                <?php echo ucfirst($d['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <?php if ($d['status'] !== 'paga'): ?>
                                    <a href="pagamento_form.php?id_divida=<?php echo $d['id']; ?>" class="btn btn-sm btn-primary">Pagar</a>
                                <?php endif; ?>
                                <a href="divida_form.php?editar=<?php echo $d['id']; ?>" class="btn btn-sm btn-secondary">Editar</a>
                                <a href="divida_excluir.php?id=<?php echo $d['id']; ?>" class="btn btn-sm btn-danger">Excluir</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
