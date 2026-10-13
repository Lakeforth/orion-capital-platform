<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

class DomainService
{
    public function verifyDnsRecord(string $domain, string $recordType = 'A'): array
    {
        // Validate the domain name
        $validator = Validator::make(
            ['domain' => $domain],
            ['domain' => 'required|regex:/^(?!:\/\/)([a-zA-Z0-9-_]+\.)*[a-zA-Z0-9][a-zA-Z0-9-_]+\.[a-zA-Z]{2,11}?$/']
        );

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => 'Invalid domain format.',
                'errors' => $validator->errors(),
            ];
        }

        $dnsRecords = dns_get_record($domain, constant("DNS_" . strtoupper($recordType)));

        if (!$dnsRecords) {
            return [
                'success' => false,
                'message' => "No $recordType record found for $domain.",
            ];
        }

        return [
            'success' => true,
            'message' => "$recordType record exists for $domain.",
            'data' => $dnsRecords,
        ];
    }

}
