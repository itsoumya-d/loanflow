<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreLoanApplicationRequest;
use App\Http\Requests\UpdateLoanApplicationStatusRequest;
use App\Http\Resources\LoanApplicationResource;
use App\Http\Resources\LoanEventResource;
use App\Models\LoanApplication;
use App\Models\User;
use App\Services\Lending\LoanApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LoanApplicationController
{
    public function __construct(private readonly LoanApplicationService $applications) {}

    public function store(StoreLoanApplicationRequest $request): JsonResponse
    {
        $key = (string) $request->attributes->get('idempotency_key');
        $source = (string) ($request->header('X-Loanflow-Source') ?: 'api');

        $result = $this->applications->submit($request->validated(), $key, $source);

        return (new LoanApplicationResource($result->application))
            ->response()
            ->setStatusCode($result->created ? 201 : 200)
            ->header('Location', route('api.loan-applications.show', $result->application));
    }

    public function show(LoanApplication $loanApplication): LoanApplicationResource
    {
        return new LoanApplicationResource($loanApplication);
    }

    public function timeline(LoanApplication $loanApplication): AnonymousResourceCollection
    {
        return LoanEventResource::collection(
            $loanApplication->events()->orderBy('sequence')->get()
        );
    }

    public function updateStatus(
        UpdateLoanApplicationStatusRequest $request,
        LoanApplication $loanApplication,
    ): LoanApplicationResource {
        $actor = $request->user();

        $updated = $this->applications->transition(
            $loanApplication,
            (string) $request->validated('status'),
            $request->validated('reason'),
            $actor instanceof User ? $actor : null,
        );

        return new LoanApplicationResource($updated);
    }
}
