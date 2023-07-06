<?php

declare(strict_types=1);

namespace App\Message\ActivityPub\Inbox;

class CreateMessage
{
    public function __construct(public array $payload)
    {
    }
}
