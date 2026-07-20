<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

security_headers();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id) {
        backup_db();
        $stmt = $db->prepare("DELETE FROM dividas WHERE id = ?");
        $stmt->execute([$id]);
    }
    redirect('dividas.php');
}

// Show confirmation page
$id = (int) ($_GET['id'] ?? 0);
if (!$id) redirect('dividas.php');

$stmt = $db->prepare("SELECT credor FROM dividas WHERE id = ?");
$stmt->execute([$id]);
$d = $stmt->fetch();
if (!$d) redirect('dividas.php');

$tituloPagina = 'Excluir Dívida';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Excluir Dívida</h1>
    </div>
</div>

<div class="card" style="max-width:500px;text-align:center;">
    <p style="margin-bottom:24px;font-size:1.1rem;">
        Excluir a dívida <strong><?php echo htmlspecialchars($d['credor']); ?></strong>?
    </p>
    <form method="POST">
        <?php csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="btn-group" style="justify-content:center;">
            <button type="submit" class="btn btn-danger">Sim, excluir</button>
            <a href="dividas.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
