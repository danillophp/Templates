<?php
namespace App\Services;

class PdfReportService
{
    public function generateMonthlyCsv(array $rows, string $month): string
    {
        $file = __DIR__ . '/../../storage/reports/relatorio_' . $month . '.csv';
        $f = fopen($file, 'w');
        fputcsv($f, ['ID', 'Protocolo', 'Status', 'Data Agendada']);
        foreach ($rows as $r) fputcsv($f, [$r['id'], $r['protocolo'], $r['status'], $r['data_agendada']]);
        fclose($f);
        return $file;
    }
}
