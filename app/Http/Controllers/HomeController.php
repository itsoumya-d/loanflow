<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HomeController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'name' => 'Loanflow',
            'description' => 'Clean-room regulated lending platform',
            'docs' => '/api/v1/health',
            'repository' => 'https://github.com/itsoumya-d/loanflow',
        ]);
    }
}
