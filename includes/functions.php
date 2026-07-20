<?php
function formatarMoeda($valor) {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

function calcularDiasAtraso($dataVencimento) {
    $vencimento = new DateTime($dataVencimento);
    $hoje = new DateTime();
    if ($vencimento < $hoje) {
        return (int) $hoje->diff($vencimento)->days;
    }
    return 0;
}

function calcularMulta($valor, $diasAtraso) {
    if ($diasAtraso <= 0) return 0;
    $multa = $valor * 0.02;
    $juros = $valor * 0.0033 * $diasAtraso;
    return $multa + $juros;
}

function obterStatusDivida($dataVencimento, $status, $saldoRestante) {
    if ($saldoRestante <= 0) return 'paga';
    if ($status === 'paga') return 'paga';
    
    $vencimento = new DateTime($dataVencimento);
    $hoje = new DateTime();
    
    if ($vencimento < $hoje) return 'atrasada';
    return 'pendente';
}

function classeStatus($status) {
    $classes = [
        'paga' => 'status-pago',
        'pendente' => 'status-pendente',
        'atrasada' => 'status-atrasado'
    ];
    return $classes[$status] ?? 'status-pendente';
}

function resumoDashboard() {
    $db = getDB();
    
    $totalDevido = $db->query("SELECT COALESCE(SUM(saldo_restante), 0) FROM dividas WHERE status != 'paga'")->fetchColumn();
    $totalPago = $db->query("SELECT COALESCE(SUM(valor), 0) FROM pagamentos")->fetchColumn();
    $totalOriginal = $db->query("SELECT COALESCE(SUM(valor_total), 0) FROM dividas")->fetchColumn();
    $qtdDividas = $db->query("SELECT COUNT(*) FROM dividas WHERE status != 'paga'")->fetchColumn();
    $qtdAtrasadas = $db->query("SELECT COUNT(*) FROM dividas WHERE status = 'atrasada'")->fetchColumn();
    $qtdPagas = $db->query("SELECT COUNT(*) FROM dividas WHERE status = 'paga'")->fetchColumn();
    
    return [
        'total_devido' => $totalDevido,
        'total_pago' => $totalPago,
        'total_original' => $totalOriginal,
        'qtd_dividas' => $qtdDividas,
        'qtd_atrasadas' => $qtdAtrasadas,
        'qtd_pagas' => $qtdPagas
    ];
}

function obterCategorias() {
    $db = getDB();
    return $db->query("SELECT * FROM categorias ORDER BY nome")->fetchAll();
}

function obterProximosVencimentos($dias = 30) {
    $db = getDB();
    $dias = max(1, (int) $dias);
    $stmt = $db->prepare("
        SELECT d.*, c.nome as categoria_nome
        FROM dividas d
        LEFT JOIN categorias c ON d.id_categoria = c.id
        WHERE d.status != 'paga'
        AND d.data_vencimento BETWEEN date('now') AND date('now', '+' || ? || ' days')
        ORDER BY d.data_vencimento ASC
    ");
    $stmt->execute([$dias]);
    return $stmt->fetchAll();
}

function obterContasFixas() {
    $db = getDB();
    $stmt = $db->query("
        SELECT d.*, c.nome as categoria_nome
        FROM dividas d
        LEFT JOIN categorias c ON d.id_categoria = c.id
        WHERE d.fixa = 1 AND d.status != 'paga'
        ORDER BY d.data_vencimento ASC
    ");
    return $stmt->fetchAll();
}

function gerarContasFixas() {
    $db = getDB();
    $fixas = $db->query("SELECT * FROM dividas WHERE fixa = 1 AND status = 'paga'")->fetchAll();
    
    $mesAtual = date('Y-m');
    
    foreach ($fixas as $fixa) {
        $dataVenc = date('Y-m-d', strtotime($fixa['data_vencimento']));
        $mesVenc = date('Y-m', strtotime($dataVenc));
        
        if ($mesVenc < $mesAtual) {
            $novaData = date('Y-m-d', strtotime($dataVenc . ' +1 month'));
            while (date('Y-m', strtotime($novaData)) < $mesAtual) {
                $novaData = date('Y-m-d', strtotime($novaData . ' +1 month'));
            }
            
            $stmt = $db->prepare("
                INSERT INTO dividas (id_categoria, credor, valor_total, saldo_restante, valor_parcela, num_parcelas, parcelas_pagas, data_vencimento, status, fixa, observacao)
                VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'pendente', 1, ?)
            ");
            $stmt->execute([
                $fixa['id_categoria'],
                $fixa['credor'],
                $fixa['valor_total'],
                $fixa['saldo_restante'],
                $fixa['valor_parcela'],
                $fixa['num_parcelas'],
                $novaData,
                $fixa['observacao']
            ]);
        }
    }
}

function atualizarStatusDividas() {
    $db = getDB();
    $db->exec("
        UPDATE dividas 
        SET status = 'atrasada' 
        WHERE status = 'pendente' 
        AND data_vencimento < date('now')
        AND saldo_restante > 0
    ");
    
    $db->exec("
        UPDATE dividas 
        SET status = 'paga' 
        WHERE saldo_restante <= 0
    ");
}

function redirect($url) {
    header("Location: $url");
    exit;
}
