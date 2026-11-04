<?php

declare(strict_types=1);

namespace App\Integrations\BigQuery;

interface AccessTokenProvider
{
    public function token(): string;
}
