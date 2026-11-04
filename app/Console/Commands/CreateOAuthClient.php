<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\ClientRepository;

class CreateOAuthClient extends Command
{
    protected $signature = 'loanflow:client {name=WordPress intake : Human-readable client name}';

    protected $description = 'Create an OAuth 2.0 client credentials client and print its one-time secret.';

    public function handle(ClientRepository $clients): int
    {
        $client = $clients->createClientCredentialsGrantClient((string) $this->argument('name'));

        $this->info('OAuth client created. Store the secret now; it is hashed at rest.');
        $this->line('Client ID:     '.$client->getKey());
        $this->line('Client secret: '.$client->plainSecret);
        $this->line('Grant type:    client_credentials');
        $this->line('Scopes:        applications:write applications:read');

        return self::SUCCESS;
    }
}
