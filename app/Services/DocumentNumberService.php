<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class DocumentNumberService
{
    public function generate(string $documentType, ?Carbon $date = null): string
    {
        return app(DocumentSequenceService::class)->generate($documentType, date: $date);
    }
}
