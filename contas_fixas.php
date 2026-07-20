<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
atualizarStatusDividas();
gerarContasFixas();

$fixas = $db->query("
    SELECT d.*, c.nome as categoria_nome
    FROM dividas d
    LEFT JOIN categorias c ON d.id_categoria = c.id
    WHERE d.fixa = 1
    ORDER BY d.data_vencimento DESC
")->fetchAll();

$tituloPagina = 'Contas Fixas';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Contas Fixas</h1>
        <p>Assinaturas, academia, seguros e outras despesas recorrentes</p>
    </div>
    <div class="btn-group">
        <a href="divida_form.php" class="btn btn-primary">Nova Conta Fixa</a>
        <a href="exportar_csv.php?tipo=contas_fixas" class="btn btn-secondary">CSV</a>
        <a href="exportar_pdf.php?tipo=contas_fixas" class="btn btn-secondary">PDF</a>
    </div>
</div>

<?php if (empty($fixas)): ?>
    <div class="card empty-state">
        <p>Nenhuma conta fixa cadastrada.</p>
        <p style="font-size:0.875rem;color:var(--gray-400);">Ao cadastrar uma dívida, marque a opção "Conta Fixa" para que ela se repita automaticamente todo mês.</p>
        <a href="divida_form.php" class="btn btn-primary">Cadastrar Conta Fixa</a>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Credor</th>
                    <th>Categoria</th>
                    <th>Valor</th>
                    <th>Vencimento</th>
                    <th>Status</th>
                    <th>Mês</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fixas as $d): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($d['credor']); ?></strong></td>
                        <td><span class="tag"><?php echo htmlspecialchars($d['categoria_nome'] ?? 'Sem categoria'); ?></span></td>
                        <td><?php echo formatarMoeda($d['valor_total']); ?></td>
                        <td><?php echo formatarData($d['data_vencimento']); ?></td>
                        <td>
                            <span class="status-badge <?php echo classeStatus($d['status']); ?>">
                                <span class="status-dot"></span>
                                <?php echo ucfirst($d['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('m/Y', strtotime($d['data_vencimento'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <?php if ($d['status'] !== 'paga'): ?>
                                    <a href="pagamento_form.php?id_divida=<?php echo $d['id']; ?>" class="btn btn-sm btn-primary">Pagar</a>
                                <?php endif; ?>
                                <a href="divida_form.php?editar=<?php echo $d['id']; ?>" class="btn btn-sm btn-secondary">Editar</a>
                                <a href="divida_excluir.php?id=<?php echo $d['id']; ?>" class="btn btn-sm btn-danger" data-confirm="Excluir esta conta fixa?">Excluir</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-top:24px;">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:8px;">Como funciona?</h2>
        <p style="font-size:0.875rem;color:var(--gray-500);">
            Contas fixas são dívidas que se repetem todo mês automaticamente. Após pagar uma conta fixa,
            o sistema gera uma nova para o mês seguinte com os mesmos valores. 
            Você só precisa cadastrar uma vez.
        </p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
