<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';

$db = getDB();
$tipo = $_GET['tipo'] ?? 'dividas';

function csvVal($v) {
    return str_replace(['"', "\r", "\n"], ['""', '', ''], (string) $v);
}

function csvRow($data) {
    $escaped = array_map(function($v) {
        $v = csvVal($v);
        if (strpbrk($v, ',";') !== false) {
            $v = '"' . $v . '"';
        }
        return $v;
    }, $data);
    return implode(',', $escaped) . "\r\n";
}

function fmtNum($v) {
    return number_format((float) $v, 2, ',', '.');
}

function csvOutput($filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache');
    echo "\xEF\xBB\xBF";
}

switch ($tipo) {
    case 'dividas':
        csvOutput('dividas.csv');
        echo csvRow(['Credor', 'Categoria', 'Valor Total', 'Saldo Restante', 'Valor Parcela', 'Parcelas', 'Pagas', 'Vencimento', 'Status', 'Fixa', 'Observacao']);
        $stmt = $db->query("
            SELECT d.*, c.nome as categoria_nome
            FROM dividas d LEFT JOIN categorias c ON d.id_categoria = c.id
            ORDER BY d.data_vencimento DESC
        ");
        while ($d = $stmt->fetch()) {
            echo csvRow([
                $d['credor'],
                $d['categoria_nome'] ?? '',
                fmtNum($d['valor_total']),
                fmtNum($d['saldo_restante']),
                fmtNum($d['valor_parcela']),
                $d['num_parcelas'],
                $d['parcelas_pagas'],
                date('d/m/Y', strtotime($d['data_vencimento'])),
                $d['status'],
                $d['fixa'] ? 'Sim' : 'Nao',
                $d['observacao']
            ]);
        }
        break;

    case 'pagamentos':
        csvOutput('pagamentos.csv');
        echo csvRow(['Credor', 'Valor', 'Data', 'Observacao']);
        $stmt = $db->query("
            SELECT p.*, d.credor FROM pagamentos p
            JOIN dividas d ON p.id_divida = d.id
            ORDER BY p.data_pagamento DESC
        ");
        while ($p = $stmt->fetch()) {
            echo csvRow([
                $p['credor'],
                fmtNum($p['valor']),
                date('d/m/Y', strtotime($p['data_pagamento'])),
                $p['observacao']
            ]);
        }
        break;

    case 'contas_fixas':
        csvOutput('contas_fixas.csv');
        echo csvRow(['Credor', 'Categoria', 'Valor', 'Vencimento', 'Status', 'Observacao']);
        $stmt = $db->query("
            SELECT d.*, c.nome as categoria_nome
            FROM dividas d LEFT JOIN categorias c ON d.id_categoria = c.id
            WHERE d.fixa = 1
            ORDER BY d.data_vencimento DESC
        ");
        while ($d = $stmt->fetch()) {
            echo csvRow([
                $d['credor'],
                $d['categoria_nome'] ?? '',
                fmtNum($d['valor_total']),
                date('d/m/Y', strtotime($d['data_vencimento'])),
                $d['status'],
                $d['observacao']
            ]);
        }
        break;

    case 'relatorio':
        csvOutput('relatorio_completo.csv');
        echo csvRow(['=== RESUMO ===']);
        $resumo = resumoDashboard();
        echo csvRow(['Total Devido', fmtNum($resumo['total_devido'])]);
        echo csvRow(['Total Pago', fmtNum($resumo['total_pago'])]);
        echo csvRow(['Dividas Pendentes', $resumo['qtd_dividas']]);
        echo csvRow(['Atrasadas', $resumo['qtd_atrasadas']]);
        echo csvRow(['']);
        echo csvRow(['=== DETALHAMENTO ===']);
        echo csvRow(['Credor', 'Categoria', 'Valor Total', 'Saldo', 'Parcelas', 'Vencimento', 'Status']);
        $stmt = $db->query("
            SELECT d.*, c.nome as categoria_nome
            FROM dividas d LEFT JOIN categorias c ON d.id_categoria = c.id
            ORDER BY d.status, d.data_vencimento
        ");
        while ($d = $stmt->fetch()) {
            echo csvRow([
                $d['credor'],
                $d['categoria_nome'] ?? '',
                fmtNum($d['valor_total']),
                fmtNum($d['saldo_restante']),
                $d['parcelas_pagas'] . '/' . $d['num_parcelas'],
                date('d/m/Y', strtotime($d['data_vencimento'])),
                $d['status']
            ]);
        }
        break;

    default:
        redirect('dividas.php');
}
exit;
