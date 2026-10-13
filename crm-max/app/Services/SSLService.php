<?php

namespace App\Services;

use App\Jobs\CheckDomainCertStatus;
use App\Models\Domain;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SSLService
{
    private string $token;
    private string $forgeOrg;

    /**
     * Initialize the service with necessary configuration
     */
    public function __construct()
    {
        $this->token = (string) config('app.forge_token');
        $this->forgeOrg = (string) config('app.forge_org');
    }

    public function provisionDomain(Domain $domain): array
    {
        try {
            $this->ensureDomainRecord($domain);

            $certificateResult = $this->getDomainCertificateStatus($domain);
            if ($certificateResult['success']) {
                $certificate = $certificateResult['certificate'];
                $status = $certificateResult['status'];

                if (in_array($status, ['active', 'installed'], true)) {
                    $domain->status = 'active';
                    $domain->save();

                    return [
                        'success' => true,
                        'message' => 'SSL certificate already active',
                        'certificate' => $certificate,
                    ];
                }

                if ($status === 'failed') {
                    $certificate = $this->createDomainCertificate($domain);
                    $domain->status = 'ssl_initiated';
                    $domain->save();
                }
            } else {
                $certificate = $this->createDomainCertificate($domain);
                $domain->status = 'ssl_initiated';
                $domain->save();
            }

            CheckDomainCertStatus::dispatch($domain->id);

            return [
                'success' => true,
                'message' => 'SSL certificate generation started',
                'certificate' => $certificate,
            ];
        } catch (Exception $e) {
            Log::error('Failed to provision domain SSL', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            $domain->status = 'failed';
            $domain->save();

            return [
                'success' => false,
                'message' => 'Failed to provision SSL certificate',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function ensureDomainRecord(Domain $domain): string
    {
        if ($domain->domain_record_id) {
            return (string) $domain->domain_record_id;
        }

        try {
            $record = $this->createDomainRecord($domain);
        } catch (RequestException $e) {
            if ($e->response?->status() === 422) {
                $record = $this->findDomainRecordByName($domain, $domain->domain);
                if ($record) {
                    Log::info('Forge domain record matched existing domain', [
                        'domain' => $domain->domain,
                        'domain_record_id' => $record['id'] ?? null,
                    ]);
                } else {
                    $message = $e->response?->json('message') ?? $e->getMessage();
                    throw new Exception($message, $e->getCode(), $e);
                }
            } else {
                throw $e;
            }
        } catch (Exception $e) {
            $record = $this->findDomainRecordByName($domain, $domain->domain);
            if (!$record) {
                throw $e;
            }
            Log::info('Forge domain record matched existing domain', [
                'domain' => $domain->domain,
                'domain_record_id' => $record['id'] ?? null,
            ]);
        }

        $domain->domain_record_id = $record['id'];
        $domain->save();

        return (string) $domain->domain_record_id;
    }

    public function createDomainRecord(Domain $domain): array
    {
        try {
            $response = $this->request()->post($this->domainsUrl($domain), [
                'name' => $domain->domain,
                'allow_wildcard_subdomains' => false,
                'www_redirect_type' => 'none',
            ]);

            Log::info('Forge create domain response', [
                'domain' => $domain->domain,
                'server_id' => $domain->server_id,
                'site_id' => $domain->site_id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            $payload = $response->throw()->json();
        } catch (RequestException $e) {
            Log::error('Forge create domain failed', [
                'domain' => $domain->domain,
                'server_id' => $domain->server_id,
                'site_id' => $domain->site_id,
                'status' => $e->response?->status(),
                'body' => $e->response?->json(),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        $record = $payload['data'] ?? null;

        if (!$record || !isset($record['id'])) {
            throw new Exception('Invalid response from Forge API while creating domain record');
        }

        return $record;
    }

    public function createDomainCertificate(Domain $domain): array
    {
        $this->assertDomainRecord($domain);

        $response = $this->request()
            ->post($this->domainCertificatesUrl($domain, $domain->domain_record_id), [
                'type' => 'letsencrypt',
                'letsencrypt' => [
                    'verification_method' => 'http-01',
                    'key_type' => 'ecdsa',
                    'preferred_chain' => null,
                ],
            ])
            ->throw()
            ->json();

        $certificate = $response['data'] ?? null;

        if (!$certificate) {
            throw new Exception('Invalid response from Forge API while creating certificate');
        }

        if (isset($certificate['id'])) {
            $domain->cert_id = $certificate['id'];
            $domain->save();
        }

        return $certificate;
    }

    public function getDomainCertificateStatus(Domain $domain): array
    {
        try {
            $this->assertDomainRecord($domain);

            $certificate = $domain->cert_id
                ? $this->fetchCertificateById($domain, $domain->cert_id)
                : $this->fetchLatestCertificate($domain);

            if (!$certificate) {
                throw new Exception('No certificate found for domain');
            }

            if (!$domain->cert_id && isset($certificate['id'])) {
                $domain->cert_id = $certificate['id'];
                $domain->save();
            }

            $status = $certificate['attributes']['status'] ?? null;

            return [
                'success' => true,
                'status' => $status,
                'certificate' => $certificate,
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch domain certificate status', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to get certificate status',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * No-op kept for backwards compatibility with existing call sites (CheckDomainCertStatus,
     * CheckSSLStatusJob, ActivateSSLJob). The Forge org API no longer exposes a manual
     * activate/enable action for domain-alias certificates (confirmed via OPTIONS on
     * .../certificates/{id} -- only GET, HEAD, DELETE are allowed). Certificates issued via
     * http-01 are activated by Forge automatically once issued, so there is nothing to enable.
     */
    public function enableDomainCertificate(Domain $domain): array
    {
        return [
            'success' => true,
            'message' => 'No manual activation needed -- Forge activates domain-alias certificates automatically.',
        ];
    }

    public function deleteDomainCertificate(Domain $domain): array
    {
        try {
            if (!$domain->domain_record_id) {
                return [
                    'success' => true,
                    'message' => 'No domain record to delete certificate from',
                ];
            }

            $certId = $domain->cert_id ?? ($this->fetchLatestCertificate($domain)['id'] ?? null);

            if (!$certId) {
                return [
                    'success' => true,
                    'message' => 'No certificate to delete',
                ];
            }

            $this->request()
                ->delete($this->domainCertificateUrl($domain, $domain->domain_record_id, $certId))
                ->throw();

            $domain->cert_id = null;
            $domain->save();

            return [
                'success' => true,
                'message' => 'Certificate deleted successfully',
            ];
        } catch (Exception $e) {
            Log::error('Failed to delete domain certificate', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete certificate',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function fetchCertificateById(Domain $domain, string $certificateId): ?array
    {
        $response = $this->request()
            ->get($this->domainCertificateUrl($domain, $domain->domain_record_id, $certificateId))
            ->throw()
            ->json();

        return $response['data'] ?? null;
    }

    private function fetchLatestCertificate(Domain $domain): ?array
    {
        $response = $this->request()
            ->get($this->domainCertificatesUrl($domain, $domain->domain_record_id))
            ->throw()
            ->json();

        $certificates = $response['data'] ?? [];

        return end($certificates) ?: null;
    }

    public function deleteDomainRecord(Domain $domain): array
    {
        try {
            if (!$domain->domain_record_id) {
                return [
                    'success' => true,
                    'message' => 'No domain record to delete.',
                ];
            }

            $this->request()
                ->delete($this->domainUrl($domain, $domain->domain_record_id))
                ->throw();

            return [
                'success' => true,
                'message' => 'Domain record deleted successfully.',
            ];
        } catch (Exception $e) {
            Log::error('Failed to delete domain record', [
                'domain' => $domain->domain,
                'domain_record_id' => $domain->domain_record_id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to delete domain record.',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
    }

    private function baseUrl(Domain $domain): string
    {
        $serverId = (string) ($domain->server_id ?? config('app.server_id'));
        $siteId = (string) ($domain->site_id ?? config('app.site_id'));

        return "https://forge.laravel.com/api/orgs/{$this->forgeOrg}/servers/{$serverId}/sites/{$siteId}";
    }

    private function domainsUrl(Domain $domain): string
    {
        return $this->baseUrl($domain) . '/domains';
    }

    private function domainUrl(Domain $domain, string|int $domainRecordId): string
    {
        return $this->baseUrl($domain) . "/domains/{$domainRecordId}";
    }

    private function domainCertificatesUrl(Domain $domain, string|int $domainRecordId): string
    {
        return $this->baseUrl($domain) . "/domains/{$domainRecordId}/certificates";
    }

    private function domainCertificateUrl(Domain $domain, string|int $domainRecordId, string|int $certificateId): string
    {
        return $this->domainCertificatesUrl($domain, $domainRecordId) . "/{$certificateId}";
    }

    private function assertDomainRecord(Domain $domain): void
    {
        if (!$domain->domain_record_id) {
            throw new Exception('Domain record ID is missing');
        }
    }

    private function findDomainRecordByName(Domain $domain, string $domainName): ?array
    {
        $normalized = strtolower(trim($domainName));
        $url = $this->domainsUrl($domain);
        $attempts = 0;

        while ($url && $attempts < 10) {
            $attempts++;

            try {
                $response = $this->request()->get($url);

                Log::info('Forge list domains response', [
                    'domain' => $domainName,
                    'server_id' => $domain->server_id,
                    'site_id' => $domain->site_id,
                    'status' => $response->status(),
                    'url' => $url,
                    'body' => $response->json(),
                ]);

                $payload = $response->throw()->json();
            } catch (RequestException $e) {
                Log::error('Forge list domains failed', [
                    'domain' => $domainName,
                    'server_id' => $domain->server_id,
                    'site_id' => $domain->site_id,
                    'status' => $e->response?->status(),
                    'url' => $url,
                    'body' => $e->response?->json(),
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }

            $records = $payload['data'] ?? [];

            foreach ($records as $record) {
                $name = $record['attributes']['name'] ?? null;
                if ($name && strtolower($name) === $normalized) {
                    return $record;
                }
            }

            $next = $payload['links']['next']['href'] ?? $payload['links']['next'] ?? null;
            $url = is_string($next) ? $next : null;
        }

        Log::warning('Forge domain record not found in list', [
            'domain' => $domainName,
        ]);

        return null;
    }
}
