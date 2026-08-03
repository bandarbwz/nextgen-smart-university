<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Logger;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

/**
 * Turns the generic report shape (title, columns, rows) into a downloadable
 * file. Adding a report needs no change here.
 */
class ReportExporter
{
    public const FORMATS = ['csv', 'pdf', 'xlsx'];

    private const MIME_TYPES = [
        'csv' => 'text/csv; charset=utf-8',
        'pdf' => 'application/pdf',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function export(array $report, string $format): array
    {
        if (!in_array($format, self::FORMATS, true)) {
            throw new ApiException(
                'The export format must be one of: ' . implode(', ', self::FORMATS) . '.',
                422
            );
        }

        $contents = match ($format) {
            'csv' => $this->toCsv($report),
            'pdf' => $this->toPdf($report),
            'xlsx' => $this->toXlsx($report),
        };

        return [
            'contents' => $contents,
            'mime_type' => self::MIME_TYPES[$format],
            'file_name' => $this->fileName($report, $format),
        ];
    }

    private function toCsv(array $report): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new ApiException('Unable to build the export.', 500);
        }

        fputcsv($handle, $this->headings($report), ',', '"', '\\');

        foreach ($report['rows'] as $row) {
            fputcsv($handle, $this->orderedValues($report['columns'], $row), ',', '"', '\\');
        }

        rewind($handle);
        $contents = (string) stream_get_contents($handle);
        fclose($handle);

        return $contents;
    }

    private function toPdf(array $report): string
    {
        $html = '<html><head><meta charset="utf-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#0f172a}'
            . 'h1{font-size:16px;margin:0 0 4px}'
            . 'p.meta{color:#475569;font-size:9px;margin:0 0 12px}'
            . 'p.note{margin:0 0 12px;line-height:1.5}'
            . 'table{width:100%;border-collapse:collapse}'
            . 'th{background:#f1f5fd;text-align:left;padding:6px;border:1px solid #cbd5e1;font-size:9px}'
            . 'td{padding:6px;border:1px solid #e4ecfc}'
            . '</style></head><body>'
            . '<h1>' . $this->escape($report['title']) . '</h1>'
            . '<p class="meta">NextGen Smart University. Generated '
            . $this->escape($report['generated_at'] ?? gmdate('Y-m-d H:i:s')) . ' UTC.</p>';

        if (is_string($report['note'] ?? null)) {
            $html .= '<p class="note">' . $this->escape($report['note']) . '</p>';
        }

        if ($report['rows'] === []) {
            $html .= '<p>No data available for this report.</p>';
        } else {
            $html .= '<table><thead><tr>';

            foreach ($this->headings($report) as $heading) {
                $html .= '<th>' . $this->escape($heading) . '</th>';
            }

            $html .= '</tr></thead><tbody>';

            foreach ($report['rows'] as $row) {
                $html .= '<tr>';

                foreach ($this->orderedValues($report['columns'], $row) as $value) {
                    $html .= '<td>' . $this->escape((string) $value) . '</td>';
                }

                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '</body></html>';

        try {
            $pdf = new Dompdf();

            $pdf->loadHtml($html);
            $pdf->setPaper('A4', 'landscape');
            $pdf->render();

            return (string) $pdf->output();
        } catch (Throwable $exception) {
            Logger::error('PDF export failed', ['message' => $exception->getMessage()]);

            throw new ApiException('Unable to build the PDF export.', 500);
        }
    }

    private function toXlsx(array $report): string
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setTitle(substr(preg_replace('/[^A-Za-z0-9 ]/', '', $report['title']) ?: 'Report', 0, 31));
            $sheet->fromArray($this->headings($report), null, 'A1');

            $rowNumber = 2;

            foreach ($report['rows'] as $row) {
                $sheet->fromArray($this->orderedValues($report['columns'], $row), null, 'A' . $rowNumber);
                $rowNumber++;
            }

            foreach (range(1, max(1, count($report['columns']))) as $column) {
                $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
            }

            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

            $handle = fopen('php://temp', 'r+');
            (new Xlsx($spreadsheet))->save($handle);
            rewind($handle);
            $contents = (string) stream_get_contents($handle);
            fclose($handle);

            $spreadsheet->disconnectWorksheets();

            return $contents;
        } catch (Throwable $exception) {
            Logger::error('XLSX export failed', ['message' => $exception->getMessage()]);

            throw new ApiException('Unable to build the spreadsheet export.', 500);
        }
    }

    private function headings(array $report): array
    {
        return array_map(
            static fn (string $column): string => ucwords(str_replace('_', ' ', $column)),
            $report['columns']
        );
    }

    private function orderedValues(array $columns, array $row): array
    {
        return array_map(
            static fn (string $column): string => (string) ($row[$column] ?? ''),
            $columns
        );
    }

    private function fileName(array $report, string $format): string
    {
        $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $report['title']) ?? 'report');

        return trim($slug, '-') . '-' . gmdate('Ymd-His') . '.' . $format;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
