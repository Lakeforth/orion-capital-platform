<?php

namespace App\Services\GoogleDrive;

use App\Models\GoogleDriveConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Drive file operations for one company.
 *
 * Always resolved per company id — never per logged-in user — because the same connection is
 * used by queued jobs where there is no authenticated user. Every method goes through
 * connection(), so a tenant can only ever reach its own credentials.
 *
 * Under the drive.file scope every call here is limited to files the user handed us through
 * the Google Picker, or files this app created. Attempting anything else returns 404 from
 * Google, which is the behaviour we want.
 */
class GoogleDriveService
{
    private const API = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD_API = 'https://www.googleapis.com/upload/drive/v3';

    /**
     * Google's own editor formats have no bytes to download — they must be exported to a
     * concrete type instead. A Sheet picked for a lead import becomes CSV.
     */
    private const EXPORT_AS = [
        'application/vnd.google-apps.spreadsheet' => ['mime' => 'text/csv', 'ext' => 'csv'],
        'application/vnd.google-apps.document' => ['mime' => 'text/csv', 'ext' => 'csv'],
    ];

    public function __construct(private readonly GoogleOAuth $oauth) {}

    public function connectionFor(int $companyId): ?GoogleDriveConnection
    {
        return GoogleDriveConnection::query()->where('company_id', $companyId)->first();
    }

    public function isConnected(int $companyId): bool
    {
        return (bool) $this->connectionFor($companyId)?->isUsable();
    }

    /** @throws GoogleDriveNotConnected */
    private function connection(int $companyId): GoogleDriveConnection
    {
        $connection = $this->connectionFor($companyId);

        if (! $connection || ! $connection->isUsable()) {
            throw new GoogleDriveNotConnected('Google Drive is not connected for this company.');
        }

        return $connection;
    }

    /** @throws GoogleDriveNotConnected */
    private function client(int $companyId): PendingRequest
    {
        $connection = $this->connection($companyId);

        return Http::withToken($this->oauth->accessTokenFor($connection))
            ->timeout(30)
            ->retry(2, 500, throw: false);
    }

    /**
     * Metadata for one file. Also the authorisation check: under drive.file a file the user
     * never granted us simply 404s, so this doubles as "may this company touch this id?".
     *
     * @return array{id:string,name:string,mimeType:string,size:?int}
     */
    public function metadata(int $companyId, string $fileId): array
    {
        $res = $this->client($companyId)->get(self::API.'/files/'.rawurlencode($fileId), [
            'fields' => 'id,name,mimeType,size,trashed',
            'supportsAllDrives' => 'true',
        ]);

        $this->guard($companyId, $res, 'fetch file metadata');

        $file = $res->json();

        if (($file['trashed'] ?? false) === true) {
            throw new RuntimeException('That file is in the Google Drive trash.');
        }

        return [
            'id' => $file['id'],
            'name' => $file['name'] ?? 'file',
            'mimeType' => $file['mimeType'] ?? 'application/octet-stream',
            'size' => isset($file['size']) ? (int) $file['size'] : null,
        ];
    }

    /**
     * Stream a Drive file onto a local disk and return where it landed.
     *
     * Size is checked twice on purpose: once against Drive's declared size before a byte moves,
     * and again while writing, because the declared size is Google's claim rather than a
     * guarantee. Exceeding the cap deletes the partial file instead of leaving it on disk.
     *
     * @param  string  $directory  disk-relative directory, e.g. "leadmax/imports/12"
     * @return array{path:string,name:string,extension:string,bytes:int}
     *
     * @throws GoogleDriveNotConnected
     */
    public function downloadToDisk(
        int $companyId,
        string $fileId,
        string $directory,
        int $maxBytes,
        string $disk = 'local',
        ?array $allowedExtensions = null,
    ): array {
        $meta = $this->metadata($companyId, $fileId);
        $native = self::EXPORT_AS[$meta['mimeType']] ?? null;

        if ($meta['size'] !== null && $meta['size'] > $maxBytes) {
            throw new RuntimeException(sprintf(
                'That file is %s, which is over the %s limit.',
                $this->humanBytes($meta['size']),
                $this->humanBytes($maxBytes),
            ));
        }

        $extension = $native
            ? $native['ext']
            : strtolower(pathinfo($meta['name'], PATHINFO_EXTENSION) ?: 'bin');

        if ($allowedExtensions !== null && ! in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException(sprintf(
                '"%s" is a .%s file. Allowed types: %s.',
                $meta['name'], $extension, implode(', ', $allowedExtensions),
            ));
        }

        // The filename is ours, never Google's: a name from Drive is attacker-influenceable
        // and must not reach the filesystem. Matches the existing uploader's uuid convention.
        $path = rtrim($directory, '/').'/'.\Illuminate\Support\Str::uuid().'.'.$extension;
        $absolute = Storage::disk($disk)->path($path);

        if (! is_dir(dirname($absolute))) {
            @mkdir(dirname($absolute), 0775, true);
        }

        [$url, $query] = $native
            ? [self::API.'/files/'.rawurlencode($fileId).'/export', ['mimeType' => $native['mime']]]
            : [self::API.'/files/'.rawurlencode($fileId), ['alt' => 'media', 'supportsAllDrives' => 'true']];

        $handle = fopen($absolute, 'w');
        $written = 0;

        try {
            $res = $this->client($companyId)
                ->withOptions([
                    'stream' => true,
                    'timeout' => 120,
                ])
                ->get($url, $query);

            $this->guard($companyId, $res, 'download file');

            $body = $res->toPsrResponse()->getBody();

            while (! $body->eof()) {
                $chunk = $body->read(256 * 1024);

                if ($chunk === '') {
                    break;
                }

                $written += strlen($chunk);

                if ($written > $maxBytes) {
                    throw new RuntimeException(sprintf(
                        'That file is larger than the %s limit.', $this->humanBytes($maxBytes)
                    ));
                }

                fwrite($handle, $chunk);
            }
        } catch (\Throwable $e) {
            fclose($handle);
            Storage::disk($disk)->delete($path);
            throw $e;
        }

        fclose($handle);

        if ($written === 0) {
            Storage::disk($disk)->delete($path);
            throw new RuntimeException('That file is empty.');
        }

        $this->connectionFor($companyId)?->markUsed();

        return [
            'path' => $path,
            'name' => $meta['name'],
            'extension' => $extension,
            'bytes' => $written,
        ];
    }

    /**
     * Upload a local file into Drive and return the created file.
     *
     * Multipart upload, which Google supports up to 5 MB — ample for a CSV export. Anything
     * larger should move to a resumable upload; the size guard below makes that explicit
     * rather than letting a big export fail obscurely.
     *
     * @return array{id:string,name:string,webViewLink:?string}
     */
    public function upload(
        int $companyId,
        string $contents,
        string $filename,
        string $mimeType = 'text/csv',
        ?string $folderId = null,
    ): array {
        if (strlen($contents) > 5 * 1024 * 1024) {
            throw new RuntimeException('That export is too large to send to Drive in one piece.');
        }

        $metadata = array_filter([
            'name' => $filename,
            'parents' => $folderId ? [$folderId] : null,
        ]);

        $res = $this->client($companyId)
            ->attach('metadata', json_encode($metadata), 'metadata.json', ['Content-Type' => 'application/json; charset=UTF-8'])
            ->attach('file', $contents, $filename, ['Content-Type' => $mimeType])
            ->post(self::UPLOAD_API.'/files?uploadType=multipart&supportsAllDrives=true&fields=id,name,webViewLink');

        $this->guard($companyId, $res, 'upload file');
        $this->connectionFor($companyId)?->markUsed();

        return [
            'id' => $res->json('id'),
            'name' => $res->json('name'),
            'webViewLink' => $res->json('webViewLink'),
        ];
    }

    /** Create a folder (used when the admin wants a fresh destination for exports). */
    public function createFolder(int $companyId, string $name, ?string $parentId = null): array
    {
        $res = $this->client($companyId)->post(self::API.'/files?fields=id,name,webViewLink', array_filter([
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => $parentId ? [$parentId] : null,
        ]));

        $this->guard($companyId, $res, 'create folder');

        return ['id' => $res->json('id'), 'name' => $res->json('name'), 'webViewLink' => $res->json('webViewLink')];
    }

    /**
     * Turn a Google API failure into something a human can act on, and record terminal auth
     * failures on the connection so the UI can offer "reconnect" instead of failing forever.
     */
    private function guard(int $companyId, $res, string $action): void
    {
        if ($res->successful()) {
            return;
        }

        $status = $res->status();
        $reason = (string) $res->json('error.message', '');

        if (in_array($status, [401, 403], true)) {
            // 403 is ambiguous: it is both "insufficient permission" and "rate limited".
            $isRateLimit = str_contains(strtolower($reason), 'rate limit')
                || str_contains(strtolower($reason), 'quota');

            if (! $isRateLimit) {
                $this->connectionFor($companyId)?->markFailed('Google refused access. Reconnect required.');
                throw new GoogleDriveNotConnected('Google refused access to Drive. Please reconnect the account in Settings.');
            }

            throw new RuntimeException('Google Drive is rate limiting us. Please try again shortly.');
        }

        if ($status === 404) {
            throw new RuntimeException('That file is no longer available, or this company was never given access to it.');
        }

        Log::warning('Google Drive API error', [
            'company_id' => $companyId,
            'action' => $action,
            'status' => $status,
            'reason' => $reason,
        ]);

        throw new RuntimeException('Google Drive could not '.$action.' right now.');
    }

    private function humanBytes(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).' MB'
            : max(1, (int) round($bytes / 1024)).' KB';
    }
}
