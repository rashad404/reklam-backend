<?php

namespace App\Services;

class SiteVerification
{
    public function records(string $host): array
    {
        return array_column(dns_get_record($host, DNS_TXT) ?: [], 'txt');
    }
}
