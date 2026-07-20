<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$db = getDB();

$editando = false;
$divida = [
    'id' => '',
    'id_categoria' => '',
    'credor' => '',
    'valor_total' => '',
    'valor_parcela' => '',
    'num_parcelas' => '1',
    'data_vencimento' => date('Y-m-d'),
    'observacao' => '',
    'fixa' => 0
];

if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM dividas WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $d = $stmt->fetch();
    if ($d) {
        $editando = true;
        $divida = $d;
    }
}

$categorias = obterCategorias();
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $credor = trim($_POST['credor'] ?? '');
    $valor_total = str_replace(',', '.', $_POST['valor_total'] ?? '0');
    $valor_parcela = str_replace(',', '.', $_POST['valor_parcela'] ?? '0');
    $num_parcelas = (int) ($_POST['num_parcelas'] ?? 1);
    $data_vencimento = $_POST['data_vencimento'] ?? '';
    $id_categoria = $_POST['id_categoria'] ?: null;
    $observacao = trim($_POST['observacao'] ?? '');
    $fixa = isset($_POST['fixa']) ? 1 : 0;

    if (!$credor || !$valor_total || !$data_vencimento) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif ($num_parcelas < 1) {
        $erro = 'Número de parcelas inválido.';
    } else {
        if ($editando) {
            $stmt = $db->prepare("UPDATE dividas SET id_categoria=?, credor=?, valor_total=?, valor_parcela=?, num_parcelas=?, data_vencimento=?, observacao=?, fixa=? WHERE id=?");
            $stmt->execute([$id_categoria, $credor, $valor_total, $valor_parcela, $num_parcelas, $data_vencimento, $observacao, $fixa, $divida['id']]);

            $stmt = $db->prepare("UPDATE dividas SET saldo_restante = valor_total - (SELECT COALESCE(SUM(valor), 0) FROM pagamentos WHERE id_divida = ?) WHERE id = ?");
            $stmt->execute([$divida['id'], $divida['id']]);

            $sucesso = 'Dívida atualizada com sucesso!';
        } else {
            $stmt = $db->prepare("INSERT INTO dividas (id_categoria, credor, valor_total, saldo_restante, valor_parcela, num_parcelas, parcelas_pagas, data_vencimento, status, fixa, observacao) VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'pendente', ?, ?)");
            $stmt->execute([$id_categoria, $credor, $valor_total, $valor_total, $valor_parcela, $num_parcelas, $data_vencimento, $fixa, $observacao]);
            backup_db();
            $sucesso = 'Dívida cadastrada com sucesso!';
        }

        if ($fixa && !$editando) {
            redirect('contas_fixas.php');
        } else {
            redirect('dividas.php');
        }
    }
}

$tituloPagina = $editando ? 'Editar Dívida' : 'Nova Dívida';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?php echo $editando ? 'Editar Dívida' : 'Nova Dívida'; ?></h1>
        <p><?php echo $editando ? 'Altere os dados da dívida' : 'Cadastre uma nova dívida'; ?></p>
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
                <label for="credor">Credor *</label>
                <input type="text" id="credor" name="credor" placeholder="Nome do credor, banco, loja..." required value="<?php echo htmlspecialchars($divida['credor']); ?>">
            </div>

            <div class="form-group">
                <label for="id_categoria">Categoria</label>
                <select id="id_categoria" name="id_categoria">
                    <option value="">Sem categoria</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (int) $divida['id_categoria'] === (int) $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="valor_total">Valor Total *</label>
                <input type="number" step="0.01" min="0.01" id="valor_total" name="valor_total" placeholder="0,00" required value="<?php echo $divida['valor_total']; ?>">
            </div>

            <div class="form-group">
                <label for="valor_parcela">Valor da Parcela</label>
                <input type="number" step="0.01" min="0" id="valor_parcela" name="valor_parcela" placeholder="0,00" value="<?php echo $divida['valor_parcela']; ?>">
                <span class="hint">Deixe 0 se for pagamento único</span>
            </div>

            <div class="form-group">
                <label for="num_parcelas">Número de Parcelas</label>
                <input type="number" min="1" id="num_parcelas" name="num_parcelas" value="<?php echo $divida['num_parcelas']; ?>">
            </div>

            <div class="form-group">
                <label for="data_vencimento">Data de Vencimento *</label>
                <input type="date" id="data_vencimento" name="data_vencimento" required value="<?php echo $divida['data_vencimento']; ?>">
            </div>

            <div class="form-group" style="flex-direction:row;align-items:center;gap:12px;">
                <input type="checkbox" id="fixa" name="fixa" value="1" <?php echo $divida['fixa'] ? 'checked' : ''; ?> style="width:18px;height:18px;">
                <label for="fixa" style="margin-bottom:0;">Conta Fixa (repete todo mês)</label>
            </div>

            <div class="form-group full-width">
                <label for="observacao">Observação</label>
                <textarea id="observacao" name="observacao" placeholder="Observações adicionais..."><?php echo htmlspecialchars($divida['observacao']); ?></textarea>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $editando ? 'Salvar Alterações' : 'Cadastrar Dívida'; ?></button>
            <a href="dividas.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
