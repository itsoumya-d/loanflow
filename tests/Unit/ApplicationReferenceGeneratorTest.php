<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Lending\ApplicationReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationReferenceGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_nz_style_references(): void
    {
        $generator = new ApplicationReferenceGenerator;

        $reference = $generator->generate();

        $this->assertMatchesRegularExpression('/^LF-\d{4}-[A-Z0-9]{8}$/', $reference);
        $this->assertNotSame($reference, $generator->generate());
    }
}
