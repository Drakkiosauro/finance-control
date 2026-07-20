<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
atualizarStatusDividas();

$mes = (int) ($_GET['mes'] ?? date('m'));
$ano = (int) ($_GET['ano'] ?? date('Y'));

if ($mes < 1) { $mes = 12; $ano--; }
if ($mes > 12) { $mes = 1; $ano++; }

$primeiroDia = new DateTime("$ano-$mes-01");
$ultimoDia = new DateTime($primeiroDia->format('Y-m-t'));
$diaSemanaInicio = (int) $primeiroDia->format('N');
$totalDias = (int) $ultimoDia->format('d');

$mesAnterior = ($mes === 1 ? 12 : $mes - 1);
$anoAnterior = ($mes === 1 ? $ano - 1 : $ano);
$proximoMes = ($mes === 12 ? 1 : $mes + 1);
$proximoAno = ($mes === 12 ? $ano + 1 : $ano);

$nomesMeses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

$inicio = "$ano-$mes-01";
$fim = $ultimoDia->format('Y-m-d');

$dividas = $db->prepare("
    SELECT d.*, c.nome as categoria_nome
    FROM dividas d
    LEFT JOIN categorias c ON d.id_categoria = c.id
    WHERE d.data_vencimento BETWEEN ? AND ? AND d.status != 'paga'
    ORDER BY d.data_vencimento ASC
");
$dividas->execute([$inicio, $fim]);
$dividas = $dividas->fetchAll();

$eventosPorDia = [];
foreach ($dividas as $d) {
    $dia = (int) date('j', strtotime($d['data_vencimento']));
    if (!isset($eventosPorDia[$dia])) $eventosPorDia[$dia] = [];
    $eventosPorDia[$dia][] = $d;
}

$hoje = new DateTime();
$hojeStr = $hoje->format('Y-m-d');

$tituloPagina = 'Calendário';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Calendário de Vencimentos</h1>
        <p><?php echo $nomesMeses[$mes - 1] . ' de ' . $ano; ?></p>
    </div>
    <div class="btn-group">
        <a href="?mes=<?php echo $mesAnterior; ?>&ano=<?php echo $anoAnterior; ?>" class="btn btn-secondary btn-sm">&larr; Anterior</a>
        <a href="?mes=<?php echo date('m'); ?>&ano=<?php echo date('Y'); ?>" class="btn btn-secondary btn-sm">Hoje</a>
        <a href="?mes=<?php echo $proximoMes; ?>&ano=<?php echo $proximoAno; ?>" class="btn btn-secondary btn-sm">Próximo &rarr;</a>
    </div>
</div>

<div class="calendar-grid">
    <div class="calendar-header">Dom</div>
    <div class="calendar-header">Seg</div>
    <div class="calendar-header">Ter</div>
    <div class="calendar-header">Qua</div>
    <div class="calendar-header">Qui</div>
    <div class="calendar-header">Sex</div>
    <div class="calendar-header">Sáb</div>

    <?php
    $diaSemana = ($diaSemanaInicio === 7) ? 0 : $diaSemanaInicio;
    for ($i = 0; $i < $diaSemana; $i++): ?>
        <div class="calendar-day other-month"></div>
    <?php endfor; ?>

    <?php for ($dia = 1; $dia <= $totalDias; $dia++):
        $dataStr = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
        $isHoje = ($dataStr === $hojeStr);
        $temEvento = isset($eventosPorDia[$dia]);
        $diaSemanaAtual = date('w', strtotime($dataStr));
        $isFimSemana = ($diaSemanaAtual == 0 || $diaSemanaAtual == 6);
    ?>
        <div class="calendar-day <?php echo $isHoje ? 'today' : ''; echo $isFimSemana && !$isHoje ? 'other-month' : ''; ?>">
            <div class="day-number"><?php echo $dia; ?></div>
            <?php if ($temEvento): ?>
                <?php foreach ($eventosPorDia[$dia] as $evento): ?>
                    <div class="calendar-event <?php echo $evento['status']; ?>" title="<?php echo htmlspecialchars($evento['credor']) . ' - ' . formatarMoeda($evento['saldo_restante']); ?>">
                                        <?php echo htmlspecialchars($evento['credor']); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                
                    <?php
                    $totalCelulas = $diaSemana + $totalDias;
                    $resto = $totalCelulas % 7;
                    if ($resto > 0):
                        for ($i = 0; $i < 7 - $resto; $i++): ?>
                            <div class="calendar-day other-month"></div>
                        <?php endfor;
                    endif; ?>
                </div>
                
                <div class="card" style="margin-top:24px;">
                    <h2 style="font-size:1rem;font-weight:600;margin-bottom:16px;">Legenda</h2>
                    <div style="display:flex;gap:24px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="status-badge status-pendente"><span class="status-dot"></span> Pendente</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="status-badge status-atrasado"><span class="status-dot"></span> Atrasada</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="status-badge status-pago"><span class="status-dot"></span> Paga</span>
                        </div>
                    </div>
                </div>
                
                <?php require_once __DIR__ . '/includes/footer.php'; ?>
