<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

class LoanflowClientSeeder extends Seeder
{
    public function run(): void
    {
        $client = Client::query()->firstOrCreate(
            ['name' => 'Loanflow WordPress Intake'],
            [
                'secret' => Str::random(48),
                'redirect_uris' => [],
                'grant_types' => ['client_credentials'],
                'revoked' => false,
            ],
        );

        if ($client->wasRecentlyCreated) {
            $this->command?->info("Loanflow OAuth client created: {$client->id}");
            $this->command?->warn("Client secret (store now, shown once): {$client->plainSecret}");
        }
    }
}
