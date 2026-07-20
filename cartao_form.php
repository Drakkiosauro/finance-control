<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$db = getDB();

$editando = false;
$cartao = [
    'id' => '',
    'nome' => '',
    'limite' => '',
    'dia_fechamento' => date('d'),
    'dia_vencimento' => date('d')
];

if (isset($_GET['editar'])) {
    $stmt = $db->prepare("SELECT * FROM cartoes WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $c = $stmt->fetch();
    if ($c) {
        $editando = true;
        $cartao = $c;
    }
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $nome = trim($_POST['nome'] ?? '');
    $limite = str_replace(',', '.', $_POST['limite'] ?? '0');
    $dia_fechamento = (int) ($_POST['dia_fechamento'] ?? 1);
    $dia_vencimento = (int) ($_POST['dia_vencimento'] ?? 1);

    if (!$nome || !$limite) {
        $erro = 'Preencha todos os campos.';
    } elseif ($dia_fechamento < 1 || $dia_fechamento > 31 || $dia_vencimento < 1 || $dia_vencimento > 31) {
        $erro = 'Dia inválido (1-31).';
    } else {
        if ($editando) {
            $stmt = $db->prepare("UPDATE cartoes SET nome=?, limite=?, dia_fechamento=?, dia_vencimento=? WHERE id=?");
            $stmt->execute([$nome, $limite, $dia_fechamento, $dia_vencimento, $cartao['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO cartoes (nome, limite, dia_fechamento, dia_vencimento) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $limite, $dia_fechamento, $dia_vencimento]);
        }
        backup_db();
        redirect('cartoes.php');
    }
}

$tituloPagina = $editando ? 'Editar Cartão' : 'Novo Cartão';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><?php echo $editando ? 'Editar Cartão' : 'Novo Cartão'; ?></h1>
        <p><?php echo $editando ? 'Altere os dados do cartão' : 'Cadastre um novo cartão de crédito'; ?></p>
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
                <label for="nome">Nome do Cartão *</label>
                <input type="text" id="nome" name="nome" placeholder="Ex: Nubank, Inter, Santander..." required value="<?php echo htmlspecialchars($cartao['nome']); ?>">
            </div>

            <div class="form-group">
                <label for="limite">Limite Total *</label>
                <input type="number" step="0.01" min="0" id="limite" name="limite" placeholder="0,00" required value="<?php echo $cartao['limite']; ?>">
            </div>

            <div class="form-group">
                <label for="dia_fechamento">Dia de Fechamento</label>
                <input type="number" min="1" max="31" id="dia_fechamento" name="dia_fechamento" value="<?php echo $cartao['dia_fechamento']; ?>">
            </div>

            <div class="form-group">
                <label for="dia_vencimento">Dia de Vencimento</label>
                <input type="number" min="1" max="31" id="dia_vencimento" name="dia_vencimento" value="<?php echo $cartao['dia_vencimento']; ?>">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $editando ? 'Salvar' : 'Cadastrar'; ?></button>
            <a href="cartoes.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
