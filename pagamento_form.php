<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$db = getDB();
atualizarStatusDividas();

$id_divida_selecionada = $_GET['id_divida'] ?? 0;
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id_divida = (int) ($_POST['id_divida'] ?? 0);
    $valor = str_replace(',', '.', $_POST['valor'] ?? '0');
    $data_pagamento = $_POST['data_pagamento'] ?? date('Y-m-d');
    $observacao = trim($_POST['observacao'] ?? '');

    if (!$id_divida || $valor <= 0 || !$data_pagamento) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        $stmt = $db->prepare("SELECT * FROM dividas WHERE id = ?");
        $stmt->execute([$id_divida]);
        $divida = $stmt->fetch();

        if (!$divida) {
            $erro = 'Dívida não encontrada.';
        } elseif ($divida['status'] === 'paga') {
            $erro = 'Esta dívida já está paga.';
        } elseif ($valor > $divida['saldo_restante']) {
            $erro = 'O valor do pagamento não pode ser maior que o saldo restante (R$ ' . number_format($divida['saldo_restante'], 2, ',', '.') . ').';
        } else {
            $db->beginTransaction();
            try {
                $stmt = $db->prepare("INSERT INTO pagamentos (id_divida, valor, data_pagamento, observacao) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id_divida, $valor, $data_pagamento, $observacao]);

                $novoSaldo = $divida['saldo_restante'] - $valor;
                $novasParcelasPagas = $divida['parcelas_pagas'];
                if ($divida['valor_parcela'] > 0) {
                    $novasParcelasPagas = (int) ($divida['valor_total'] - $novoSaldo) / $divida['valor_parcela'];
                }

                $novoStatus = $novoSaldo <= 0 ? 'paga' : $divida['status'];

                $stmt = $db->prepare("UPDATE dividas SET saldo_restante = ?, parcelas_pagas = ?, status = ? WHERE id = ?");
                $stmt->execute([max(0, $novoSaldo), $novasParcelasPagas, $novoStatus, $id_divida]);

                $db->commit();
                backup_db();
                $sucesso = 'Pagamento registrado com sucesso!';
            } catch (Exception $e) {
                $db->rollBack();
                $erro = 'Erro ao registrar pagamento.';
            }
        }
    }
}

$dividasPendentes = $db->query("
    SELECT d.*, c.nome as categoria_nome
    FROM dividas d
    LEFT JOIN categorias c ON d.id_categoria = c.id
    WHERE d.status != 'paga'
    ORDER BY d.data_vencimento ASC
")->fetchAll();

$dividaInfo = null;
if ($id_divida_selecionada) {
    $stmt = $db->prepare("SELECT d.*, c.nome as categoria_nome FROM dividas d LEFT JOIN categorias c ON d.id_categoria = c.id WHERE d.id = ?");
    $stmt->execute([$id_divida_selecionada]);
    $dividaInfo = $stmt->fetch();
}

$tituloPagina = 'Registrar Pagamento';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Registrar Pagamento</h1>
        <p>Registre pagamento total ou parcial de uma dívida</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alert alert-error"><?php echo $erro; ?></div>
<?php endif; ?>
<?php if ($sucesso): ?>
    <div class="alert alert-success"><?php echo $sucesso; ?></div>
<?php endif; ?>

<div class="card form-card">
    <form method="POST">
        <?php csrf_field(); ?>
        <div class="form-grid">
            <div class="form-group full-width">
                <label for="id_divida">Selecione a Dívida *</label>
                <select id="id_divida" name="id_divida" required>
                    <option value="">— Selecione —</option>
                    <?php foreach ($dividasPendentes as $d): ?>
                        <option value="<?php echo $d['id']; ?>" <?php echo $id_divida_selecionada == $d['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['credor']); ?> — 
                            Restante: <?php echo formatarMoeda($d['saldo_restante']); ?> — 
                            Vence: <?php echo formatarData($d['data_vencimento']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($dividaInfo): ?>
                <div class="full-width" style="display:flex;gap:16px;flex-wrap:wrap;">
                    <div class="card" style="flex:1;padding:16px;border:1px solid var(--gray-200);">
                        <small style="color:var(--gray-400);">Credor</small>
                        <div style="font-weight:600;"><?php echo htmlspecialchars($dividaInfo['credor']); ?></div>
                    </div>
                    <div class="card" style="flex:1;padding:16px;border:1px solid var(--gray-200);">
                        <small style="color:var(--gray-400);">Total</small>
                        <div style="font-weight:600;"><?php echo formatarMoeda($dividaInfo['valor_total']); ?></div>
                    </div>
                    <div class="card" style="flex:1;padding:16px;border:1px solid var(--gray-200);">
                        <small style="color:var(--gray-400);">Saldo Restante</small>
                        <div style="font-weight:600;"><?php echo formatarMoeda($dividaInfo['saldo_restante']); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="valor">Valor do Pagamento *</label>
                <input type="number" step="0.01" min="0.01" id="valor" name="valor" placeholder="0,00" required>
                <span class="hint">Pode ser parcial — o saldo é atualizado automaticamente</span>
            </div>

            <div class="form-group">
                <label for="data_pagamento">Data do Pagamento *</label>
                <input type="date" id="data_pagamento" name="data_pagamento" value="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group full-width">
                <label for="observacao">Observação</label>
                <textarea id="observacao" name="observacao" placeholder="Opcional: forma de pagamento, desconto, etc."></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Registrar Pagamento</button>
            <a href="dividas.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php if ($sucesso): ?>
<script>
setTimeout(function() { window.location.href = 'dividas.php'; }, 1500);
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
