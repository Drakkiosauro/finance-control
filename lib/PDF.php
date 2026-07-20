<?php

class PDF {
    private $content = '';
    private $objects = [];
    private $n = 0;
    private $offsets = [];
    private $pages = [];
    private $currentPage = 0;
    private $x = 20;
    private $y = 20;
    private $fontSize = 10;
    private $lineHeight = 5;
    private $fonts = [];
    private $title = 'Relatorio';

    const MARGIN_LEFT = 20;
    const MARGIN_TOP = 30;
    const PAGE_WIDTH = 210;
    const PAGE_HEIGHT = 297;
    const TABLE_PADDING = 4;

    public function __construct($title = 'Relatorio') {
        $this->title = $title;
        $this->newPage();
    }

    public function newPage() {
        if ($this->currentPage > 0) {
            $this->pages[$this->currentPage]['content'] = $this->content;
        }
        $this->currentPage++;
        $this->content = '';
        $this->y = self::MARGIN_TOP;
        $this->x = self::MARGIN_LEFT;
    }

    public function addText($text, $size = 10, $bold = false) {
        $this->fontSize = $size;
        $this->checkPageBreak();
        $style = $bold ? 'Bold' : 'Regular';
        $this->content .= "BT /F{$style} {$size} Tf ET\n";
        $text = $this->escapeText($text);
        $this->content .= "BT {$this->x} {$this->y} Td ({$text}) Tj ET\n";
        $this->y += $size * 0.35;
    }

    public function addCell($w, $h, $text, $border = false, $bold = false) {
        $this->checkPageBreak();
        $style = $bold ? 'Bold' : 'Regular';
        $this->fontSize = 8;
        $text = $this->escapeText($text);
        $x = $this->x;
        $y = $this->y;

        if ($border) {
            $this->content .= "{$w} {$h} {$x} {$y} re S\n";
        }

        $this->content .= "BT /F{$style} 8 Tf ET\n";
        $tx = $x + 2;
        $ty = $y + 3;
        $this->content .= "BT {$tx} {$ty} Td ({$text}) Tj ET\n";
        $this->x += $w;
    }

    public function addRow($cells, $widths, $header = false) {
        $h = 12;
        $startX = self::MARGIN_LEFT;

        $this->checkPageBreak($h);

        foreach ($cells as $i => $cell) {
            $this->x = $startX + array_sum(array_slice($widths, 0, $i));
            $this->addCell($widths[$i], $h, $cell, true, $header);
        }

        $this->y += $h;
        $this->x = self::MARGIN_LEFT;
    }

    public function addTable($headers, $data, $widths) {
        $this->addText('', 6);
        $this->addRow($headers, $widths, true);

        foreach ($data as $row) {
            $this->addRow($row, $widths);
        }
        $this->addText('', 4);
    }

    public function addTitle($text) {
        $this->addText($text, 16, true);
        $this->addText('', 6);
    }

    public function addSubtitle($text) {
        $this->addText($text, 11, true);
        $this->addText('', 4);
    }

    public function addLine($text) {
        $this->addText($text, 9);
    }

    private function checkPageBreak($extraHeight = 0) {
        if ($this->y + $extraHeight > self::PAGE_HEIGHT - 30) {
            $this->newPage();
        }
    }

    private function escapeText($text) {
        $text = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        return $text;
    }

    public function output($filename = null) {
        if ($this->currentPage > 0) {
            $this->pages[$this->currentPage]['content'] = $this->content;
        }

        $pdf = "%PDF-1.4\n";
        $this->n = 0;

        // Font objects
        $fonts = [
            'Regular' => ['Helvetica', ''],
            'Bold' => ['Helvetica', 'Bold']
        ];

        foreach ($fonts as $name => $font) {
            $this->n++;
            $this->offsets[$this->n] = strlen($pdf);
            $pdf .= "{$this->n} 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /{$font[0]}-{$font[1]} >>\nendobj\n";
        }

        // Pages
        foreach ($this->pages as $pageNum => $page) {
            $this->n++;
            $this->offsets[$this->n] = strlen($pdf);
            $stream = $page['content'];
            $streamLen = strlen($stream);

            $pdf .= "{$this->n} 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream\nendobj\n";
            $pageObjNum = $this->n;

            $this->n++;
            $this->offsets[$this->n] = strlen($pdf);
            $resourcesObjNum = $this->n;
            $pdf .= "{$this->n} 0 obj\n<< /Font << /FRegular 1 0 R /FBold 2 0 R >> >>\nendobj\n";

            $this->n++;
            $this->offsets[$this->n] = strlen($pdf);
            $contentObjNum = $this->n;

            $pdf .= "{$this->n} 0 obj\n<< /Type /Page /Parent 3 0 R /MediaBox [0 0 " . self::PAGE_WIDTH . " " . self::PAGE_HEIGHT . "] /Contents {$pageObjNum} 0 R /Resources {$resourcesObjNum} 0 R >>\nendobj\n";

            $this->pages[$pageNum] = [
                'pageObjNum' => $pageObjNum,
                'contentObjNum' => $contentObjNum
            ];
        }

        // Pages root
        $this->n = 3;
        $this->offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Pages /Kids [";
        foreach ($this->pages as $p) {
            $pdf .= "{$p['contentObjNum']} 0 R ";
        }
        $pdf .= "] /Count " . count($this->pages) . " >>\nendobj\n";

        // Catalog
        $this->n = 4;
        $this->offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Type /Catalog /Pages 3 0 R >>\nendobj\n";

        // Cross-reference table
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . ($this->n + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $this->n; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $this->offsets[$i]);
        }

        // Trailer
        $pdf .= "trailer\n<< /Size " . ($this->n + 1) . " /Root 4 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF\n";

        if ($filename) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }

        return $pdf;
    }
}
