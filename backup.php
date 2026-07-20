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

    if ($acao === 'backup_agora') {
        backup_db();
        $sucesso = 'Backup criado com sucesso!';
    } elseif ($acao === 'restaurar') {
        $arquivo = $_POST['arquivo'] ?? '';
        if (restaurar_backup($arquivo)) {
            $sucesso = 'Backup restaurado com sucesso!';
        } else {
            $erro = 'Erro ao restaurar backup.';
        }
    } elseif ($acao === 'baixar') {
        $arquivo = $_POST['arquivo'] ?? '';
        $caminho = __DIR__ . '/backups/' . basename($arquivo);
        if (file_exists($caminho)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($arquivo) . '"');
            header('Content-Length: ' . filesize($caminho));
            readfile($caminho);
            exit;
        }
    }
}

$backups = listar_backups();

$tituloPagina = 'Backup';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Backup</h1>
        <p>Proteja seus dados com backups</p>
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
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Criar Backup</h2>
        <p style="font-size:0.875rem;color:var(--gray-500);margin-bottom:16px;">
            Crie uma copia de seguranca do banco de dados. Os backups ficam salvos na pasta <code>backups/</code>.
        </p>
        <form method="POST">
            <?php csrf_field(); ?>
            <input type="hidden" name="acao" value="backup_agora">
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">Criar Backup Agora</button>
        </form>
    </div>

    <div class="card">
        <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Backups Disponíveis</h2>
        <?php if (empty($backups)): ?>
            <p style="color:var(--gray-400);font-size:0.875rem;">Nenhum backup encontrado.</p>
        <?php else: ?>
            <div class="table-wrapper" style="border:none;">
                <table>
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tamanho</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $b): ?>
                            <tr>
                                <td><?php echo $b['data']; ?></td>
                                <td><?php echo number_format($b['tamanho'] / 1024, 1); ?> KB</td>
                                <td>
                                    <div class="btn-group">
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Restaurar este backup? Os dados atuais serao substituidos.')">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="acao" value="restaurar">
                                            <input type="hidden" name="arquivo" value="<?php echo $b['arquivo']; ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">Restaurar</button>
                                        </form>
                                        <form method="POST" style="display:inline;">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="acao" value="baixar">
                                            <input type="hidden" name="arquivo" value="<?php echo $b['arquivo']; ?>">
                                            <button type="submit" class="btn btn-sm btn-secondary">Baixar</button>
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

<div class="card" style="margin-top:16px;">
    <h2 style="font-size:1rem;font-weight:600;margin-bottom:8px;">Dicas</h2>
    <ul style="font-size:0.875rem;color:var(--gray-500);padding-left:20px;">
        <li>Faça backup antes de editar dividas importantes</li>
        <li>Os backups sao salvos na pasta <code>backups/</code> dentro do projeto</li>
        <li>Os 30 backups mais recentes sao mantidos automaticamente</li>
        <li>Para seguranca extra, baixe os backups para outro local</li>
    </ul>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
