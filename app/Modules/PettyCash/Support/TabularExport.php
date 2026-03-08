<?php

namespace App\Modules\PettyCash\Support;

use DateTimeInterface;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TabularExport
{
    /**
     * @param array<string,string> $columns Label => row key
     * @param iterable<int,mixed> $rows
     */
    public static function download(string $format, string $baseName, array $columns, iterable $rows): Response|StreamedResponse
    {
        $format = strtolower(trim($format));

        return match ($format) {
            'csv' => self::toCsv($baseName . '.csv', $columns, $rows),
            'excel', 'xls', 'xlsx' => self::toExcel($baseName . '.xls', $columns, $rows),
            default => throw new \InvalidArgumentException('Unsupported export format: ' . $format),
        };
    }

    /**
     * @param array<string,string> $columns Label => row key
     * @param iterable<int,mixed> $rows
     */
    private static function toCsv(string $fileName, array $columns, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($columns, $rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Excel compatibility.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_keys($columns));

            foreach ($rows as $row) {
                $line = [];
                foreach ($columns as $key) {
                    $line[] = self::stringValue(data_get($row, $key));
                }
                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param array<string,string> $columns Label => row key
     * @param iterable<int,mixed> $rows
     */
    private static function toExcel(string $fileName, array $columns, iterable $rows): Response
    {
        $html = [];
        $html[] = '<html><head><meta charset="UTF-8"></head><body>';
        $html[] = '<table border="1" cellspacing="0" cellpadding="4">';
        $html[] = '<thead><tr>';

        foreach (array_keys($columns) as $label) {
            $html[] = '<th>' . e($label) . '</th>';
        }

        $html[] = '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html[] = '<tr>';
            foreach ($columns as $key) {
                $value = nl2br(e(self::stringValue(data_get($row, $key))));
                $html[] = '<td>' . $value . '</td>';
            }
            $html[] = '</tr>';
        }

        $html[] = '</tbody></table></body></html>';

        return response(implode('', $html), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private static function stringValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
