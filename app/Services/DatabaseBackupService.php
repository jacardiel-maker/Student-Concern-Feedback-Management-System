<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

class DatabaseBackupService
{
    /** @var array<int, string> */
    private const TABLES = [
        'roles',
        'users',
        'categories',
        'concerns',
        'replies',
        'attachments',
        'ratings',
        'audit_logs',
        'notifications',
        'ticket_sequences',
    ];

    /** @var array<string, array<int, string>> */
    private const REDACTED_COLUMNS = [
        'users' => ['password', 'remember_token', 'google_id'],
    ];

    public function createZip(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'scfms-backup-');

        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary backup file.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($path);

            throw new RuntimeException('Unable to open the backup archive.');
        }

        foreach (self::TABLES as $table) {
            $zip->addFromString($table.'.csv', $this->tableCsv($table));
        }

        $zip->addFromString('README.txt', $this->readme());
        $zip->close();

        return $path;
    }

    private function tableCsv(string $table): string
    {
        $columns = Schema::getColumnListing($table);
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            throw new RuntimeException('Unable to write the backup CSV stream.');
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $columns);

        foreach (DB::table($table)->orderBy($this->orderColumn($table))->cursor() as $row) {
            $values = [];

            foreach ($columns as $column) {
                $value = in_array($column, self::REDACTED_COLUMNS[$table] ?? [], true)
                    ? '[redacted]'
                    : ($row->{$column} ?? null);

                $values[] = $this->csvValue($value);
            }

            fputcsv($handle, $values);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function orderColumn(string $table): string
    {
        return $table === 'ticket_sequences' ? 'year' : 'id';
    }

    private function csvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }

    private function readme(): string
    {
        return implode(PHP_EOL, [
            'SCFMS database backup',
            'Generated: '.now()->toDateTimeString(),
            '',
            'Each CSV file corresponds to one application data table. Sensitive',
            'authentication columns in users.csv are redacted, and session, cache,',
            'queue, and failed-job tables are intentionally excluded.',
            '',
            'Values beginning with =, +, -, or @ are prefixed with an apostrophe',
            'to prevent spreadsheet formula injection.',
        ]).PHP_EOL;
    }
}
