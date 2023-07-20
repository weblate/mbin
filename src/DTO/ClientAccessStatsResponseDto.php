<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema()]
class ClientAccessStatsResponseDto
{
    public ?string $client = null;
    public ?string $datetime = null;
    public ?int $count = null;
}
