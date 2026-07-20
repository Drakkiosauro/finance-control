<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$db = getDB();
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'add') {
        $nome = trim($_POST['nome'] ?? '');
        $tipo = $_POST['tipo'] ?? 'despesa';
        if ($nome) {
            $stmt = $db->prepare("INSERT INTO categorias (nome, tipo) VALUES (?, ?)");
            $stmt->execute([$nome, $tipo]);
            backup_db();
            $sucesso = 'Categoria adicionada.';
        }
    } elseif ($acao === 'edit') {
        $id = (int) ($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $tipo = $_POST['tipo'] ?? 'despesa';
        if ($id && $nome) {
            $stmt = $db->prepare("UPDATE categorias SET nome = ?, tipo = ? WHERE id = ?");
            $stmt->execute([$nome, $tipo, $id]);
            backup_db();
            $sucesso = 'Categoria atualizada.';
        }
    } elseif ($acao === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $db->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            backup_db();
            $sucesso = 'Categoria removida.';
        }
    }
}

$categorias = $db->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM dividas WHERE id_categoria = c.id) as total_dividas
    FROM categorias c
    ORDER BY c.tipo, c.nome
")->fetchAll();

$tituloPagina = 'Categorias';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Categorias</h1>
        <p>Organize suas dívidas por categoria</p>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alert alert-error"><?php echo $erro; ?></div>
<?php endif; ?>
<?php if ($sucesso): ?>
    <div class="alert alert-success"><?php echo $sucesso; ?></div>
<?php endif; ?>

<div class="card-grid" style="grid-template-columns:1fr 2fr;">
    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Nova Categoria</h2>
        <form method="POST">
            <?php csrf_field(); ?>
            <input type="hidden" name="acao" value="add">
            <div class="form-group" style="margin-bottom:12px;">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" placeholder="Ex: Alimentação" required>
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label for="tipo">Tipo</label>
                <select id="tipo" name="tipo">
                    <option value="despesa">Despesa</option>
                    <option value="fixa">Conta Fixa</option>
                    <option value="cartao">Cartão</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Adicionar</button>
        </form>
    </div>

    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Categorias Existentes</h2>
        <?php if (empty($categorias)): ?>
            <p style="color:var(--gray-400);font-size:0.875rem;">Nenhuma categoria cadastrada.</p>
        <?php else: ?>
            <div class="table-wrapper" style="border:none;">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Dívidas</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td>
                                    <span class="tag"><?php echo htmlspecialchars($cat['nome']); ?></span>
                                </td>
                                <td><?php echo ucfirst($cat['tipo']); ?></td>
                                <td><?php echo $cat['total_dividas']; ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-secondary" onclick="editarCat(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['nome'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($cat['tipo'], ENT_QUOTES); ?>')">Editar</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir categoria?')">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="acao" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de edição -->
<div id="editModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);z-index:200;align-items:center;justify-content:center;">
    <div class="card" style="max-width:400px;width:90%;">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Editar Categoria</h2>
        <form method="POST">
            <?php csrf_field(); ?>
            <input type="hidden" name="acao" value="edit">
            <input type="hidden" name="id" id="editId">
            <div class="form-group" style="margin-bottom:12px;">
                <label for="editNome">Nome</label>
                <input type="text" id="editNome" name="nome" required>
            </div>
            <div class="form-group" style="margin-bottom:16px;">
                <label for="editTipo">Tipo</label>
                <select id="editTipo" name="tipo">
                    <option value="despesa">Despesa</option>
                    <option value="fixa">Conta Fixa</option>
                    <option value="cartao">Cartão</option>
                </select>
            </div>
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('editModal').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarCat(id, nome, tipo) {
    document.getElementById('editId').value = id;
    document.getElementById('editNome').value = nome;
    document.getElementById('editTipo').value = tipo;
    document.getElementById('editModal').style.display = 'flex';
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
