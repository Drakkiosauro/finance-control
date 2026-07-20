<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$db = getDB();

$id_cartao = $_GET['id_cartao'] ?? 0;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id_cartao = (int) ($_POST['id_cartao'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $valor = str_replace(',', '.', $_POST['valor'] ?? '0');
    $num_parcelas = (int) ($_POST['num_parcelas'] ?? 1);
    $data_compra = $_POST['data_compra'] ?? date('Y-m-d');

    if (!$descricao || !$valor) {
        $erro = 'Preencha todos os campos.';
    } elseif ($num_parcelas < 1) {
        $erro = 'Número de parcelas inválido.';
    } else {
        $stmt = $db->prepare("INSERT INTO compras_cartao (id_cartao, descricao, valor, num_parcelas, data_compra) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_cartao, $descricao, $valor, $num_parcelas, $data_compra]);
        backup_db();
        redirect('cartoes.php');
    }
}

$cartoes = $db->query("SELECT * FROM cartoes ORDER BY nome")->fetchAll();

$tituloPagina = 'Nova Compra';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Nova Compra</h1>
        <p>Registre uma compra no cartão de crédito</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alert alert-error"><?php echo $erro; ?></div>
<?php endif; ?>

<div class="card form-card">
    <form method="POST">
        <?php csrf_field(); ?>
        <div class="form-grid">
            <div class="form-group full-width">
                <label for="id_cartao">Cartão *</label>
                <select id="id_cartao" name="id_cartao" required>
                    <option value="">— Selecione —</option>
                    <?php foreach ($cartoes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $id_cartao == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="descricao">Descrição *</label>
                <input type="text" id="descricao" name="descricao" placeholder="O que foi comprado?" required>
            </div>

            <div class="form-group">
                <label for="valor">Valor Total *</label>
                <input type="number" step="0.01" min="0.01" id="valor" name="valor" placeholder="0,00" required>
            </div>

            <div class="form-group">
                <label for="num_parcelas">Parcelas</label>
                <input type="number" min="1" id="num_parcelas" name="num_parcelas" value="1">
                <span class="hint">1 = à vista</span>
            </div>

            <div class="form-group">
                <label for="data_compra">Data da Compra</label>
                <input type="date" id="data_compra" name="data_compra" value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Registrar Compra</button>
            <a href="cartoes.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
