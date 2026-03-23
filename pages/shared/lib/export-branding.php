<?php

if (!function_exists('exportBrandingAssetMap')) {
    function exportBrandingAssetMap(string $projectRoot): array
    {
        $normalizedRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');

        return [
            'bagong_pilipinas' => [
                'full_path' => $normalizedRoot . '/assets/images/Bagong_Pilipinas_logo.png',
                'app_path' => '/assets/images/Bagong_Pilipinas_logo.png',
            ],
            'ati' => [
                'full_path' => $normalizedRoot . '/assets/images/ati-logo/logo.png',
                'app_path' => '/assets/images/ati-logo/logo.png',
            ],
        ];
    }
}

if (!function_exists('exportBrandingMimeType')) {
    function exportBrandingMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }
}

if (!function_exists('exportBrandingImageDataUri')) {
    function exportBrandingImageDataUri(string $filePath): ?string
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return null;
        }

        $contents = file_get_contents($filePath);
        if ($contents === false || $contents === '') {
            return null;
        }

        return 'data:' . exportBrandingMimeType($filePath) . ';base64,' . base64_encode($contents);
    }
}

if (!function_exists('exportBrandingBuildPdfHeaderHtml')) {
    function exportBrandingBuildPdfHeaderHtml(string $projectRoot, string $title, array $metaLines = []): string
    {
        $assets = exportBrandingAssetMap($projectRoot);
        $atiLogo = exportBrandingImageDataUri($assets['ati']['full_path']);
        $bagongLogo = exportBrandingImageDataUri($assets['bagong_pilipinas']['full_path']);

        $html = '<div style="width:100%; border-bottom: 1px solid #cbd5e1; padding-bottom: 14px; margin-bottom: 16px; font-family: Arial, sans-serif;">';
        $html .= '<table cellspacing="0" cellpadding="0" style="border-collapse: collapse; border: none; width: auto; margin: 0 auto;"><tr>';
        $html .= '<td style="vertical-align: middle; border: none;">';
        $html .= '<table cellspacing="0" cellpadding="0" style="border-collapse: collapse; border: none;"><tr>';

        if ($bagongLogo !== null) {
            $html .= '<td style="vertical-align: middle; border: none; padding-right: 14px;"><img src="' . $bagongLogo . '" alt="Bagong Pilipinas logo" style="height: 62px; width: auto;"></td>';
        }

        if ($atiLogo !== null) {
            $html .= '<td style="vertical-align: middle; border: none; padding-right: 20px;"><img src="' . $atiLogo . '" alt="ATI logo" style="height: 66px; width: auto;"></td>';
        }

        $html .= '</tr></table>';
        $html .= '</td>';
        $html .= '<td style="vertical-align: middle; border: none; text-align: left;">';
        $html .= '<div style="font-size: 20px; font-weight: 400; color: #0f172a; line-height: 1.08;">Department of Agriculture</div>';
        $html .= '<div style="font-size: 18px; font-weight: 400; color: #0f172a; line-height: 1.08; margin-top: 3px;">Agricultural Training Institute</div>';
        $html .= '<div style="font-size: 15px; font-weight: 400; color: #0f172a; line-height: 1.08; margin-top: 3px;">ATI Bldg., Diliman, Q.C.</div>';
        $html .= '</td>';
        $html .= '</tr></table>';

        $html .= '<div style="font-size: 23px; font-weight: 700; color: #0f172a; margin: 18px 0 8px 0; text-align: left;">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';

        foreach ($metaLines as $line) {
            $normalizedLine = trim((string)$line);
            if ($normalizedLine === '') {
                continue;
            }

            $html .= '<div style="font-size: 12px; color: #334155; margin: 0 0 4px 0; text-align: left;">' . htmlspecialchars($normalizedLine, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('exportBrandingApplySpreadsheetHeader')) {
    function exportBrandingApplySpreadsheetHeader($sheet, string $projectRoot, int $columnCount, string $title, array $metaLines = []): int
    {
        $coordinateClass = 'PhpOffice\\PhpSpreadsheet\\Cell\\Coordinate';
        $drawingClass = 'PhpOffice\\PhpSpreadsheet\\Worksheet\\Drawing';

        $usableColumnCount = max(10, $columnCount);
        $lastColumn = $coordinateClass::stringFromColumnIndex($usableColumnCount);

        $sheet->getRowDimension(1)->setRowHeight(34);
        $sheet->getRowDimension(2)->setRowHeight(32);
        $sheet->getRowDimension(3)->setRowHeight(28);
        $sheet->getRowDimension(4)->setRowHeight(30);
        $sheet->getRowDimension(5)->setRowHeight(22);
        $sheet->getRowDimension(6)->setRowHeight(12);

        $sheet->mergeCells('D1:' . $lastColumn . '1');
        $sheet->mergeCells('D2:' . $lastColumn . '2');
        $sheet->mergeCells('D3:' . $lastColumn . '3');
        $sheet->setCellValue('D1', 'Department of Agriculture');
        $sheet->setCellValue('D2', 'Agricultural Training Institute');
        $sheet->setCellValue('D3', 'ATI Bldg., Diliman, Q.C.');
        $sheet->getStyle('D1:' . $lastColumn . '3')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('D1:' . $lastColumn . '3')->getAlignment()->setVertical('center');
        $sheet->getStyle('D1')->getFont()->setSize(16);
        $sheet->getStyle('D2')->getFont()->setSize(14);
        $sheet->getStyle('D3')->getFont()->setSize(12);

        $sheet->mergeCells('A4:' . $lastColumn . '4');
        $sheet->setCellValue('A4', $title);
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(18);

        $metaText = implode(' | ', array_values(array_filter(array_map(static fn($line): string => trim((string)$line), $metaLines), static fn(string $line): bool => $line !== '')));
        if ($metaText !== '') {
            $sheet->mergeCells('A5:' . $lastColumn . '5');
            $sheet->setCellValue('A5', $metaText);
            $sheet->getStyle('A5')->getFont()->setSize(11);
            $sheet->getStyle('A5')->getFont()->getColor()->setARGB('FF475569');
        }

        $sheet->getStyle('A4:' . $lastColumn . '5')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A4:' . $lastColumn . '5')->getAlignment()->setVertical('center');

        $assets = exportBrandingAssetMap($projectRoot);
        if (is_file($assets['bagong_pilipinas']['full_path'])) {
            $bagongDrawing = new $drawingClass();
            $bagongDrawing->setName('Bagong Pilipinas Logo');
            $bagongDrawing->setDescription('Bagong Pilipinas Logo');
            $bagongDrawing->setPath($assets['bagong_pilipinas']['full_path']);
            $bagongDrawing->setHeight(62);
            $bagongDrawing->setCoordinates('B1');
            $bagongDrawing->setOffsetX(8);
            $bagongDrawing->setOffsetY(6);
            $bagongDrawing->setWorksheet($sheet);
        }

        if (is_file($assets['ati']['full_path'])) {
            $atiDrawing = new $drawingClass();
            $atiDrawing->setName('ATI Logo');
            $atiDrawing->setDescription('ATI Logo');
            $atiDrawing->setPath($assets['ati']['full_path']);
            $atiDrawing->setHeight(66);
            $atiDrawing->setCoordinates('C1');
            $atiDrawing->setOffsetX(8);
            $atiDrawing->setOffsetY(4);
            $atiDrawing->setWorksheet($sheet);
        }

        return 7;
    }
}