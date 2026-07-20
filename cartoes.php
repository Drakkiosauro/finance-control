<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();

$cartoes = $db->query("
    SELECT c.*,
        (SELECT COALESCE(SUM(valor), 0) FROM compras_cartao WHERE id_cartao = c.id) as total_compras
    FROM cartoes c
    ORDER BY c.nome
")->fetchAll();

$tituloPagina = 'Cartões de Crédito';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Cartões de Crédito</h1>
        <p>Gerencie seus cartões e compras parceladas</p>
    </div>
    <a href="cartao_form.php" class="btn btn-primary">Novo Cartão</a>
</div>

<?php if (empty($cartoes)): ?>
    <div class="card empty-state">
        <p>Nenhum cartão cadastrado.</p>
        <a href="cartao_form.php" class="btn btn-primary">Adicionar Cartão</a>
    </div>
<?php else: ?>
    <div class="card-grid" style="grid-template-columns:1fr 1fr;">
        <?php foreach ($cartoes as $cartao): 
            $disponivel = $cartao['limite'] - $cartao['total_compras'];
            $percentual = $cartao['limite'] > 0 ? round($cartao['total_compras'] / $cartao['limite'] * 100) : 0;
            $fechamento = sprintf('%02d', $cartao['dia_fechamento']);
            $vencimento = sprintf('%02d', $cartao['dia_vencimento']);

            $stmt = $db->prepare("SELECT * FROM compras_cartao WHERE id_cartao = ? ORDER BY data_compra DESC");
            $stmt->execute([$cartao['id']]);
            $compras = $stmt->fetchAll();
        ?>
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
                    <div>
                        <h2 style="font-size:1.1rem;font-weight:700;"><?php echo htmlspecialchars($cartao['nome']); ?></h2>
                        <p style="font-size:0.8rem;color:var(--gray-400);">
                            Fecha dia <?php echo $fechamento; ?> • Vence dia <?php echo $vencimento; ?>
                        </p>
                    </div>
                    <div class="btn-group">
                        <a href="compra_form.php?id_cartao=<?php echo $cartao['id']; ?>" class="btn btn-sm btn-primary">+ Compra</a>
                        <a href="cartao_form.php?editar=<?php echo $cartao['id']; ?>" class="btn btn-sm btn-secondary">Editar</a>
                        <a href="cartao_excluir.php?id=<?php echo $cartao['id']; ?>" class="btn btn-sm btn-danger">Excluir</a>
                    </div>
                </div>

                <div style="display:flex;gap:24px;margin-bottom:16px;flex-wrap:wrap;">
                    <div>
                        <small style="color:var(--gray-400);">Limite</small>
                        <div style="font-weight:700;font-size:1.1rem;"><?php echo formatarMoeda($cartao['limite']); ?></div>
                    </div>
                    <div>
                        <small style="color:var(--gray-400);">Usado</small>
                        <div style="font-weight:700;font-size:1.1rem;color:var(--red);"><?php echo formatarMoeda($cartao['total_compras']); ?></div>
                    </div>
                    <div>
                        <small style="color:var(--gray-400);">Disponível</small>
                        <div style="font-weight:700;font-size:1.1rem;color:var(--green);"><?php echo formatarMoeda($disponivel); ?></div>
                    </div>
                </div>

                <div class="progress-bar" style="margin-bottom:16px;">
                    <div class="progress-bar-fill" style="width:<?php echo $percentual; ?>%;<?php echo $percentual > 80 ? 'background:var(--red);' : ($percentual > 50 ? 'background:var(--yellow);' : ''); ?>"></div>
                </div>

                <?php if (!empty($compras)): ?>
                    <div class="table-wrapper" style="border:none;border-top:1px solid var(--gray-200);border-radius:0;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th>Valor</th>
                                    <th>Parcelas</th>
                                    <th>Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($compras, 0, 5) as $compra): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($compra['descricao']); ?></td>
                                        <td><?php echo formatarMoeda($compra['valor']); ?></td>
                                        <td><?php echo $compra['num_parcelas']; ?>x</td>
                                        <td><?php echo formatarData($compra['data_compra']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($compras) > 5): ?>
                        <p style="text-align:center;font-size:0.8rem;color:var(--gray-400);margin-top:8px;">
                            +<?php echo count($compras) - 5; ?> compras
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p style="color:var(--gray-400);font-size:0.875rem;text-align:center;padding:16px 0;">
                        Nenhuma compra registrada
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
