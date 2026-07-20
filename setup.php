<?php
// ============================================================
// Setup inicial — cria o primeiro usuario administrador.
// Acesse: http://SEU-SITE/setup.php  (apenas na primeira execucao)
// Após criar o usuario, delete este arquivo por seguranca.
// ============================================================

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

security_headers();

$db = getDB();
$mensagem = '';
$erro = '';

// Se ja existe usuario, bloqueia o setup
$qtdUsuarios = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$jaConfigurado = $qtdUsuarios > 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$jaConfigurado) {
    csrf_verify();

    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha2 = $_POST['senha2'] ?? '';

    if (!$nome || !$email || !$senha) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email invalido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter ao menos 6 caracteres.';
    } elseif ($senha !== $senha2) {
        $erro = 'As senhas nao conferem.';
    } else {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $email, $hash]);
        $mensagem = 'Usuario criado com sucesso! Agora faca login e, por seguranca, delete o arquivo setup.php.';
    }
}

$tituloPagina = 'Setup';
require_once __DIR__ . '/includes/header_simple.php';
?>

<div class="login-box" style="max-width:440px;">
    <h1>Configuração Inicial</h1>
    <p>Crie o usuario administrador do sistema</p>

    <?php if ($jaConfigurado): ?>
        <div class="alert alert-error" style="margin-top:24px;">
            O sistema ja possui um usuario. Por seguranca, este setup esta desativado.
            Se precisar recriar, delete o banco em <code>data/financas.db</code>.
        </div>
        <a href="index.php" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;margin-top:16px;">Ir para o Login</a>
    <?php else: ?>
        <?php if ($erro): ?>
            <div class="alert alert-error" style="margin-top:24px;"><?php echo $erro; ?></div>
        <?php endif; ?>
        <?php if ($mensagem): ?>
            <div class="alert alert-success" style="margin-top:24px;"><?php echo $mensagem; ?></div>
            <a href="index.php" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;margin-top:16px;">Ir para o Login</a>
        <?php else: ?>
            <form method="POST" style="margin-top:24px;">
                <?php csrf_field(); ?>
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" placeholder="Seu nome" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="voce@exemplo.com" required>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" placeholder="Min. 6 caracteres" required>
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label for="senha2">Confirmar Senha</label>
                    <input type="password" id="senha2" name="senha2" placeholder="Repita a senha" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:12px;">Criar Usuário</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer_simple.php'; ?>
