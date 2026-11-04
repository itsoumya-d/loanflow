<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Integrations\Gtm\GtmEventForwarder;
use App\Models\LoanApplication;
use App\Models\LoanEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GtmEventForwarderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_forwards_a_signed_server_side_event(): void
    {
        Http::fake([
            'https://gtm.example.com/collect' => Http::response([], 204),
        ]);

        $application = LoanApplication::factory()->create();
        $event = $this->eventFor($application);

        $forwarder = new GtmEventForwarder(app(Factory::class), [
            'server_url' => 'https://gtm.example.com/collect',
            'forward_secret' => 'forward-secret',
            'signature_header' => 'X-Loanflow-Signature',
            'timeout_seconds' => 5,
        ]);

        $forwarder->forward($event);

        $this->assertNotNull($event->refresh()->gtm_forwarded_at);

        Http::assertSent(function (ClientRequest $request) use ($application): bool {
            $payload = json_decode($request->body(), true);
            $expected = hash_hmac('sha256', $request->body(), 'forward-secret');

            return $request->method() === 'POST'
                && $request->hasHeader('X-Loanflow-Signature', $expected)
                && $payload['event'] === 'loanflow_loan_application_received'
                && $payload['application_reference'] === $application->reference
                && $payload['parameters']['loan_status'] === $application->status;
        });
    }

    private function eventFor(LoanApplication $application): LoanEvent
    {
        return $application->events()->create([
            'sequence' => 1,
            'type' => 'loan_application.received',
            'payload' => ['source' => 'test'],
            'previous_hash' => null,
            'hash' => str_repeat('b', 64),
            'occurred_at' => now(),
        ]);
    }
}
