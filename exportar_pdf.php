<?php
session_start();
if (!isset($_SESSION['usuario_id'])) redirect('index.php');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/lib/PDF.php';

$db = getDB();
$tipo = $_GET['tipo'] ?? 'dividas';

$pdf = new PDF('Relatorio Financeiro');

switch ($tipo) {
    case 'dividas':
        $pdf->addTitle('Relatorio de Dividas');
        $pdf->addLine('Gerado em: ' . date('d/m/Y H:i'));
        $pdf->addText('', 8);

        $stmt = $db->query("
            SELECT d.*, c.nome as categoria_nome
            FROM dividas d LEFT JOIN categorias c ON d.id_categoria = c.id
            ORDER BY d.status, d.data_vencimento
        ");
        $dividas = $stmt->fetchAll();

        $resumo = resumoDashboard();
        $pdf->addSubtitle('Resumo');
        $pdf->addLine('Total Devido: R$ ' . number_format($resumo['total_devido'], 2, ',', '.'));
        $pdf->addLine('Total Pago: R$ ' . number_format($resumo['total_pago'], 2, ',', '.'));
        $pdf->addLine('Dividas Pendentes: ' . $resumo['qtd_dividas']);
        $pdf->addLine('Atrasadas: ' . $resumo['qtd_atrasadas']);
        $pdf->addText('', 6);

        $pdf->addSubtitle('Listagem');

        if (empty($dividas)) {
            $pdf->addLine('Nenhuma divida cadastrada.');
        } else {
            $widths = [50, 30, 30, 25, 30, 25];
            $headers = ['Credor', 'Total', 'Saldo', 'Parcelas', 'Vencimento', 'Status'];
            $data = [];
            foreach ($dividas as $d) {
                $data[] = [
                    $d['credor'],
                    'R$' . number_format($d['valor_total'], 2, ',', '.'),
                    'R$' . number_format($d['saldo_restante'], 2, ',', '.'),
                    $d['parcelas_pagas'] . '/' . $d['num_parcelas'],
                    date('d/m/Y', strtotime($d['data_vencimento'])),
                    ucfirst($d['status'])
                ];
            }
            $pdf->addTable($headers, $data, $widths);
        }
        break;

    case 'pagamentos':
        $pdf->addTitle('Relatorio de Pagamentos');
        $pdf->addLine('Gerado em: ' . date('d/m/Y H:i'));
        $pdf->addText('', 8);

        $stmt = $db->query("
            SELECT p.*, d.credor FROM pagamentos p
            JOIN dividas d ON p.id_divida = d.id
            ORDER BY p.data_pagamento DESC
        ");
        $pagamentos = $stmt->fetchAll();

        $total = array_sum(array_column($pagamentos, 'valor'));

        $pdf->addSubtitle('Total Pago: R$ ' . number_format($total, 2, ',', '.'));
        $pdf->addText('', 6);

        if (empty($pagamentos)) {
            $pdf->addLine('Nenhum pagamento registrado.');
        } else {
            $widths = [55, 30, 30, 55];
            $headers = ['Credor', 'Valor', 'Data', 'Observacao'];
            $data = [];
            foreach ($pagamentos as $p) {
                $data[] = [
                    $p['credor'],
                    'R$' . number_format($p['valor'], 2, ',', '.'),
                    date('d/m/Y', strtotime($p['data_pagamento'])),
                    $p['observacao']
                ];
            }
            $pdf->addTable($headers, $data, $widths);
        }
        break;

    case 'contas_fixas':
        $pdf->addTitle('Contas Fixas');
        $pdf->addLine('Gerado em: ' . date('d/m/Y H:i'));
        $pdf->addText('', 8);

        $stmt = $db->query("
            SELECT d.*, c.nome as categoria_nome
            FROM dividas d LEFT JOIN categorias c ON d.id_categoria = c.id
            WHERE d.fixa = 1
            ORDER BY d.status, d.data_vencimento
        ");
        $fixas = $stmt->fetchAll();

        if (empty($fixas)) {
            $pdf->addLine('Nenhuma conta fixa cadastrada.');
        } else {
            $widths = [50, 30, 30, 30, 30];
            $headers = ['Credor', 'Categoria', 'Valor', 'Vencimento', 'Status'];
            $data = [];
            foreach ($fixas as $d) {
                $data[] = [
                    $d['credor'],
                    $d['categoria_nome'] ?? '-',
                    'R$' . number_format($d['valor_total'], 2, ',', '.'),
                    date('d/m/Y', strtotime($d['data_vencimento'])),
                    ucfirst($d['status'])
                ];
            }
            $pdf->addTable($headers, $data, $widths);
        }
        break;

    case 'relatorio':
        $pdf->addTitle('Relatorio Financeiro Completo');
        $pdf->addLine('Gerado em: ' . date('d/m/Y H:i'));
        $pdf->addText('', 8);

        $resumo = resumoDashboard();
        $pdf->addSubtitle('Resumo Geral');
        $pdf->addLine('Total de Dividas: ' . $resumo['qtd_dividas']);
        $pdf->addLine('Total Devido: R$ ' . number_format($resumo['total_devido'], 2, ',', '.'));
        $pdf->addLine('Total Pago: R$ ' . number_format($resumo['total_pago'], 2, ',', '.'));
        $pdf->addLine('Atrasadas: ' . $resumo['qtd_atrasadas']);
        $pdf->addLine('Pagas: ' . $resumo['qtd_pagas']);
        $pdf->addText('', 8);

        $pdf->addSubtitle('Todas as Dividas');
        $stmt = $db->query("
            SELECT d.*, c.nome as categoria_nome
            FROM dividas d LEFT JOIN categorias c ON d.id_categoria = c.id
            ORDER BY d.status, d.data_vencimento
        ");
        $dividas = $stmt->fetchAll();

        if (!empty($dividas)) {
            $widths = [45, 25, 25, 22, 22, 25, 22];
            $headers = ['Credor', 'Categoria', 'Total', 'Saldo', 'Parcelas', 'Vencimento', 'Status'];
            $data = [];
            foreach ($dividas as $d) {
                $data[] = [
                    $d['credor'],
                    $d['categoria_nome'] ?? '-',
                    'R$' . number_format($d['valor_total'], 2, ',', '.'),
                    'R$' . number_format($d['saldo_restante'], 2, ',', '.'),
                    $d['parcelas_pagas'] . '/' . $d['num_parcelas'],
                    date('d/m/Y', strtotime($d['data_vencimento'])),
                    ucfirst($d['status'])
                ];
            }
            $pdf->addTable($headers, $data, $widths);
        }

        // Salary info
        $usuario_id = $_SESSION['usuario_id'];
        $stmt = $db->prepare("SELECT salario FROM usuarios WHERE id = ?");
        $stmt->execute([$usuario_id]);
        $salario = (float) ($stmt->fetchColumn() ?: 0);
        if ($salario > 0) {
            $pdf->addText('', 4);
            $pdf->addSubtitle('Renda vs Dividas');
            $pdf->addLine('Salario: R$ ' . number_format($salario, 2, ',', '.'));
            $pdf->addLine('Comprometimento: ' . ($salario > 0 ? round($resumo['total_devido'] / $salario * 100) : 0) . '%');
        }
        break;

    default:
        redirect('dividas.php');
}

$pdf->output('relatorio_' . $tipo . '_' . date('Y-m-d') . '.pdf');
