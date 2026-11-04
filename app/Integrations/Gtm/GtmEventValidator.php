<?php

declare(strict_types=1);

namespace App\Integrations\Gtm;

use App\Exceptions\InvalidGtmEventException;
use Illuminate\Support\Str;

class GtmEventValidator
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{event: string, event_id: string, application_reference: string, occurred_at: string, parameters: array<string, scalar>}
     */
    public function validate(array $payload): array
    {
        $allowed = (array) config('loanflow.gtm.allowed_events', []);
        $event = $payload['event'] ?? null;

        if (! is_string($event) || ! in_array($event, $allowed, true)) {
            throw new InvalidGtmEventException('Unknown GTM event name.');
        }

        $reference = $payload['application_reference'] ?? null;

        if (! is_string($reference) || $reference === '' || mb_strlen($reference) > 32) {
            throw new InvalidGtmEventException('A valid application_reference is required.');
        }

        $eventId = $payload['event_id'] ?? null;

        if ($eventId !== null && (! is_string($eventId) || ! Str::isUuid($eventId))) {
            throw new InvalidGtmEventException('event_id must be a UUID when provided.');
        }

        $parameters = $payload['parameters'] ?? [];

        if (! is_array($parameters)) {
            throw new InvalidGtmEventException('parameters must be an object.');
        }

        if (count($parameters) > 40) {
            throw new InvalidGtmEventException('parameters may contain at most 40 keys.');
        }

        $clean = [];

        foreach ($parameters as $key => $value) {
            if (! is_string($key) || preg_match('/^[a-z0-9_]{1,40}$/', $key) !== 1) {
                throw new InvalidGtmEventException('parameter keys must match [a-z0-9_].');
            }

            if (is_string($value)) {
                $value = mb_substr($value, 0, 500);
            } elseif (! is_scalar($value) && $value !== null) {
                throw new InvalidGtmEventException('parameter values must be scalar.');
            }

            $clean[$key] = $value;
        }

        return [
            'event' => $event,
            'event_id' => is_string($eventId) ? $eventId : (string) Str::uuid(),
            'application_reference' => $reference,
            'occurred_at' => is_string($payload['occurred_at'] ?? null)
                ? (string) $payload['occurred_at']
                : now()->toIso8601String(),
            'parameters' => $clean,
        ];
    }
}
