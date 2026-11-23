<?php

namespace App\Services;

use Illuminate\Support\Str;

class PdfExportService
{
    private const LINES_PER_PAGE = 50;

    /**
     * @param  list<string>  $lines
     */
    public function generate(string $title, array $lines): string
    {
        $pages = array_chunk($lines, self::LINES_PER_PAGE);

        if ($pages === []) {
            $pages = [[]];
        }

        $pageNumbers = [];

        foreach (array_keys($pages) as $index) {
            $pageNumbers[] = 3 + (2 * $index);
        }

        $fontNumber = 3 + (2 * count($pages));

        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids ['.implode(' ', array_map(
                fn (int $number): string => "{$number} 0 R",
                $pageNumbers,
            )).'] /Count '.count($pages).' >>',
        ];

        foreach ($pages as $index => $pageLines) {
            $objects[$pageNumbers[$index]] = $this->pageObject(2, $fontNumber, 4 + (2 * $index));
            $objects[4 + (2 * $index)] = $this->contentStream($title, $pageLines, $index);
        }

        $objects[$fontNumber] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        ksort($objects);

        return $this->assemble($objects);
    }

    private function pageObject(int $parentNumber, int $fontNumber, int $contentNumber): string
    {
        return '<< /Type /Page /Parent '.$parentNumber.' 0 R /MediaBox [0 0 595 842] '.
            '/Resources << /Font << /F1 '.$fontNumber.' 0 R >> >> /Contents '.$contentNumber.' 0 R >>';
    }

    /**
     * @param  list<string>  $lines
     */
    private function contentStream(string $title, array $lines, int $pageIndex): string
    {
        $stream = "BT\n/F1 11 Tf\n15 TL\n50 800 Td\n";
        $heading = $pageIndex === 0 ? $title : $title.' (cont.)';
        $stream .= '('.$this->escape($heading).") Tj\n";

        foreach ($lines as $line) {
            $stream .= "T*\n(".$this->escape(Str::limit($line, 105)).") Tj\n";
        }

        $stream .= "ET\n";

        return '<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream';
    }

    private function escape(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);

        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '';
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $converted);
    }

    /**
     * @param  array<int, string>  $objects
     */
    private function assemble(array $objects): string
    {
        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $size = count($objects) + 1;

        $pdf .= "xref\n0 {$size}\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_keys($objects) as $number) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
