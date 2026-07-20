<?php

namespace Modules\Reporting\Services;

class PdfGenerator
{
    protected float $pageWidth = 595.28;  // A4 Portrait width in points (72 DPI)
    protected float $pageHeight = 841.89; // A4 Portrait height in points
    protected float $marginLeft = 36;
    protected float $marginRight = 36;
    protected float $marginTop = 40;
    protected float $marginBottom = 40;
    
    protected array $pages = [];
    protected string $currentStream = '';
    protected float $currentY = 0;
    protected int $currentPageNum = 0;

    /**
     * Generate PDF binary content for given report title, metadata, KPIs, columns, and data rows.
     */
    public function generate(
        string $title,
        array $metadata,
        array $kpis,
        array $columns,
        array $rows,
        string $orientation = 'P'
    ): string {
        if ($orientation === 'L') {
            $this->pageWidth = 841.89;
            $this->pageHeight = 595.28;
        } else {
            $this->pageWidth = 595.28;
            $this->pageHeight = 841.89;
        }

        $this->pages = [];
        $this->startNewPage($title, $metadata);

        // Render KPI summary cards block if present
        if (!empty($kpis)) {
            $this->renderKpiCards($kpis);
        }

        // Render Data Table
        if (!empty($columns) && !empty($rows)) {
            $this->renderTable($title, $metadata, $columns, $rows);
        } else {
            $this->addText("No records found for this report period.", 11, $this->marginLeft, $this->currentY, false, [0.5, 0.5, 0.5]);
        }

        return $this->buildPdfDocument();
    }

    protected function startNewPage(string $title, array $metadata): void
    {
        if ($this->currentStream !== '') {
            $this->pages[] = $this->currentStream;
        }
        $this->currentStream = '';
        $this->currentPageNum++;
        $this->currentY = $this->pageHeight - $this->marginTop;

        // Render Page Header Banner
        // Header background box
        $headerHeight = 50;
        $headerY = $this->currentY - $headerHeight;
        $printableWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;

        // Dark banner background
        $this->drawRect($this->marginLeft, $headerY, $printableWidth, $headerHeight, [0.08, 0.14, 0.24], true);

        // Company / App Name
        $this->addText("INVENTORY MANAGEMENT & REPORTING SYSTEM", 8, $this->marginLeft + 12, $headerY + 34, true, [0.4, 0.7, 1.0]);
        // Report Title
        $this->addText(strtoupper($title), 14, $this->marginLeft + 12, $headerY + 14, true, [1.0, 1.0, 1.0]);

        // Generated date / info on right
        $genText = "Generated: " . date('Y-m-d H:i');
        $this->addText($genText, 9, $this->pageWidth - $this->marginRight - 140, $headerY + 20, false, [0.8, 0.85, 0.9]);

        $this->currentY = $headerY - 15;

        // Metadata Bar
        if (!empty($metadata)) {
            $metaParts = [];
            foreach ($metadata as $k => $v) {
                $metaParts[] = ucfirst($k) . ": " . $v;
            }
            $metaStr = implode("   |   ", $metaParts);
            $this->addText($metaStr, 8.5, $this->marginLeft, $this->currentY, false, [0.3, 0.35, 0.45]);
            $this->currentY -= 15;
            $this->drawLine($this->marginLeft, $this->currentY, $this->pageWidth - $this->marginRight, $this->currentY, [0.85, 0.88, 0.92], 0.75);
            $this->currentY -= 15;
        }
    }

    protected function renderKpiCards(array $kpis): void
    {
        $count = count($kpis);
        if ($count === 0) return;

        $printableWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $gap = 10;
        $cardWidth = ($printableWidth - ($gap * ($count - 1))) / $count;
        $cardHeight = 38;
        $cardY = $this->currentY - $cardHeight;

        $i = 0;
        foreach ($kpis as $label => $value) {
            $x = $this->marginLeft + ($i * ($cardWidth + $gap));
            
            // Card background (light gray/blue)
            $this->drawRect($x, $cardY, $cardWidth, $cardHeight, [0.94, 0.96, 0.98], true);
            $this->drawRect($x, $cardY, $cardWidth, $cardHeight, [0.8, 0.85, 0.9], false, 0.8);

            // Label
            $this->addText(strtoupper($label), 7.5, $x + 8, $cardY + 24, true, [0.4, 0.45, 0.55]);
            // Value
            $this->addText((string) $value, 11, $x + 8, $cardY + 9, true, [0.1, 0.2, 0.4]);

            $i++;
        }

        $this->currentY = $cardY - 20;
    }

    protected function renderTable(string $title, array $metadata, array $columns, array $rows): void
    {
        $printableWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;
        $colCount = count($columns);
        $colWidth = $printableWidth / $colCount;

        // Table Header
        $rowHeight = 22;

        $checkNewPage = function (float $neededHeight) use ($title, $metadata) {
            if ($this->currentY - $neededHeight < $this->marginBottom + 25) {
                $this->startNewPage($title, $metadata);
                return true;
            }
            return false;
        };

        $renderTableHeader = function () use ($columns, $colWidth, $rowHeight) {
            $headerY = $this->currentY - $rowHeight;
            $printableWidth = $this->pageWidth - $this->marginLeft - $this->marginRight;
            // Header background
            $this->drawRect($this->marginLeft, $headerY, $printableWidth, $rowHeight, [0.15, 0.23, 0.36], true);

            $x = $this->marginLeft;
            foreach ($columns as $col) {
                $this->addText(strtoupper($col), 8, $x + 6, $headerY + 7, true, [1.0, 1.0, 1.0]);
                $x += $colWidth;
            }
            $this->currentY = $headerY;
        };

        $renderTableHeader();

        // Rows
        $rowIndex = 0;
        foreach ($rows as $row) {
            $checkNewPage($rowHeight);

            $rowY = $this->currentY - $rowHeight;
            $bgColor = ($rowIndex % 2 === 0) ? [0.99, 0.99, 1.0] : [0.94, 0.96, 0.98];
            $this->drawRect($this->marginLeft, $rowY, $printableWidth, $rowHeight, $bgColor, true);
            $this->drawLine($this->marginLeft, $rowY, $this->pageWidth - $this->marginRight, $rowY, [0.88, 0.9, 0.93], 0.5);

            $x = $this->marginLeft;
            $colIdx = 0;
            foreach ($row as $cellValue) {
                if ($colIdx >= $colCount) break;
                $text = (string) $cellValue;
                if (strlen($text) > 28) {
                    $text = substr($text, 0, 26) . '..';
                }
                $this->addText($text, 8, $x + 6, $rowY + 7, false, [0.15, 0.15, 0.2]);
                $x += $colWidth;
                $colIdx++;
            }

            $this->currentY = $rowY;
            $rowIndex++;
        }

        // Bottom border
        $this->drawLine($this->marginLeft, $this->currentY, $this->pageWidth - $this->marginRight, $this->currentY, [0.2, 0.3, 0.4], 1.0);
    }

    protected function addText(
        string $text,
        float $fontSize,
        float $x,
        float $y,
        bool $isBold = false,
        array $colorRgb = [0, 0, 0]
    ): void {
        $fontRef = $isBold ? '/F2' : '/F1';
        $escaped = $this->escapePdfString($text);
        $r = sprintf("%.2f", $colorRgb[0]);
        $g = sprintf("%.2f", $colorRgb[1]);
        $b = sprintf("%.2f", $colorRgb[2]);

        $this->currentStream .= "BT\n";
        $this->currentStream .= "{$fontRef} {$fontSize} Tf\n";
        $this->currentStream .= "{$r} {$g} {$b} rg\n";
        $this->currentStream .= sprintf("1 0 0 1 %.2f %.2f Tm\n", $x, $y);
        $this->currentStream .= "({$escaped}) Tj\n";
        $this->currentStream .= "ET\n";
    }

    protected function drawRect(
        float $x,
        float $y,
        float $w,
        float $h,
        array $colorRgb,
        bool $fill = true,
        float $lineWidth = 1.0
    ): void {
        $r = sprintf("%.2f", $colorRgb[0]);
        $g = sprintf("%.2f", $colorRgb[1]);
        $b = sprintf("%.2f", $colorRgb[2]);

        if ($fill) {
            $this->currentStream .= "{$r} {$g} {$b} rg\n";
            $this->currentStream .= sprintf("%.2f %.2f %.2f %.2f re f\n", $x, $y, $w, $h);
        } else {
            $this->currentStream .= "{$r} {$g} {$b} RG\n";
            $this->currentStream .= sprintf("%.2f w\n", $lineWidth);
            $this->currentStream .= sprintf("%.2f %.2f %.2f %.2f re s\n", $x, $y, $w, $h);
        }
    }

    protected function drawLine(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        array $colorRgb = [0, 0, 0],
        float $lineWidth = 1.0
    ): void {
        $r = sprintf("%.2f", $colorRgb[0]);
        $g = sprintf("%.2f", $colorRgb[1]);
        $b = sprintf("%.2f", $colorRgb[2]);

        $this->currentStream .= "{$r} {$g} {$b} RG\n";
        $this->currentStream .= sprintf("%.2f w\n", $lineWidth);
        $this->currentStream .= sprintf("%.2f %.2f m %.2f %.2f l S\n", $x1, $y1, $x2, $y2);
    }

    protected function escapePdfString(string $str): string
    {
        // Convert non-ASCII / special currency symbols to standard readable representation
        $str = str_replace(['₹', '€', '£'], ['INR ', 'EUR ', 'GBP '], $str);
        $str = iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $str);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $str);
    }

    protected function buildPdfDocument(): string
    {
        if ($this->currentStream !== '') {
            $this->pages[] = $this->currentStream;
        }

        $totalPages = count($this->pages);
        $objects = [];
        $objIndex = 1;

        // Obj 1: Catalog
        $catalogObjNum = $objIndex++;
        // Obj 2: Pages
        $pagesObjNum = $objIndex++;
        // Obj 3: Font Helvetica
        $fontRegularObjNum = $objIndex++;
        // Obj 4: Font Helvetica-Bold
        $fontBoldObjNum = $objIndex++;

        $pageObjNums = [];
        $contentObjNums = [];

        for ($p = 0; $p < $totalPages; $p++) {
            $pageObjNums[$p] = $objIndex++;
            $contentObjNums[$p] = $objIndex++;
        }

        // Build Footer on streams before final length calculation
        for ($p = 0; $p < $totalPages; $p++) {
            $footerY = 20;
            $pageNumStr = sprintf("Page %d of %d", $p + 1, $totalPages);
            $footerText = "Confidential - For Internal Management Use Only";

            $footerStream = "BT\n/F1 8 Tf\n0.4 0.4 0.5 rg\n";
            $footerStream .= sprintf("1 0 0 1 %.2f %.2f Tm\n(%s) Tj\n", $this->marginLeft, $footerY, $this->escapePdfString($footerText));
            $footerStream .= sprintf("1 0 0 1 %.2f %.2f Tm\n(%s) Tj\n", $this->pageWidth - $this->marginRight - 50, $footerY, $this->escapePdfString($pageNumStr));
            $footerStream .= "ET\n";

            $this->pages[$p] .= $footerStream;
        }

        // Assemble Objects
        $objects[$catalogObjNum] = "<< /Type /Catalog /Pages {$pagesObjNum} 0 R >>";

        $pageRefsStr = implode(' ', array_map(fn($num) => "{$num} 0 R", $pageObjNums));
        $objects[$pagesObjNum] = "<< /Type /Pages /Count {$totalPages} /Kids [ {$pageRefsStr} ] >>";

        $objects[$fontRegularObjNum] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
        $objects[$fontBoldObjNum] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";

        for ($p = 0; $p < $totalPages; $p++) {
            $pageNum = $pageObjNums[$p];
            $contentNum = $contentObjNums[$p];

            $objects[$pageNum] = "<< /Type /Page /Parent {$pagesObjNum} 0 R " .
                "/MediaBox [0 0 " . sprintf("%.2f %.2f", $this->pageWidth, $this->pageHeight) . "] " .
                "/Contents {$contentNum} 0 R " .
                "/Resources << /Font << /F1 {$fontRegularObjNum} 0 R /F2 {$fontBoldObjNum} 0 R >> >> >>";

            $streamContent = $this->pages[$p];
            $len = strlen($streamContent);
            $objects[$contentNum] = "<< /Length {$len} >>\nstream\n{$streamContent}\nendstream";
        }

        // Render binary buffer with XREF table
        $output = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        ksort($objects);
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($output);
            $output .= "{$num} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($output);
        $totalObjCount = count($objects) + 1;
        $output .= "xref\n0 {$totalObjCount}\n";
        $output .= "0000000000 65535 f \n";

        for ($i = 1; $i < $totalObjCount; $i++) {
            $offset = $offsets[$i] ?? 0;
            $output .= sprintf("%010d 00000 n \n", $offset);
        }

        $output .= "trailer\n<< /Size {$totalObjCount} /Root {$catalogObjNum} 0 R >>\n";
        $output .= "startxref\n{$xrefOffset}\n%%EOF";

        return $output;
    }
}
