<?php

namespace App\Traits\Api\V1;


trait PaginationTrait
{
    private function sanitizePerPage(mixed $perPage): int
    {
        $perPage = is_numeric($perPage) ? (int)$perPage : 10;
        return max(1, min(100, $perPage));
    }
}
