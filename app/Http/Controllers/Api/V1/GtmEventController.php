<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Integrations\Gtm\GtmEventValidator;
use App\Models\LoanApplication;
use App\Services\Lending\LoanEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GtmEventController
{
    public function __invoke(
        Request $request,
        GtmEventValidator $validator,
        LoanEventService $events,
    ): JsonResponse {
        $validated = $validator->validate($request->json()->all());

        $application = LoanApplication::query()
            ->where('reference', $validated['application_reference'])
            ->first();

        if ($application === null) {
            return response()->json(['message' => 'Unknown application_reference.'], 404);
        }

        $event = $events->record($application, 'gtm.'.$validated['event'], [
            'event_id' => $validated['event_id'],
            'occurred_at' => $validated['occurred_at'],
            'parameters' => $validated['parameters'],
        ]);

        return response()->json([
            'status' => 'accepted',
            'event_id' => $event->id,
        ], 202);
    }
}
