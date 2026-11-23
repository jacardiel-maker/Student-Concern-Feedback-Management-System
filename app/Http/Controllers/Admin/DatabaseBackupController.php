<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    public function __construct(
        private readonly DatabaseBackupService $backups,
        private readonly AuditService $audit,
    ) {}

    public function download(Request $request): StreamedResponse
    {
        $path = $this->backups->createZip();
        $filename = 'scfms-database-'.now()->format('Y-m-d_His').'.zip';

        $this->audit->record(
            'database_exported',
            $request->user(),
            null,
            null,
            $filename,
        );

        return response()->streamDownload(function () use ($path): void {
            try {
                $handle = fopen($path, 'rb');

                if ($handle !== false) {
                    fpassthru($handle);
                    fclose($handle);
                }
            } finally {
                @unlink($path);
            }
        }, $filename, ['Content-Type' => 'application/zip']);
    }
}
