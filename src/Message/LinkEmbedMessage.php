<?php

declare(strict_types=1);

namespace App\Message;

class LinkEmbedMessage
{
    public function __construct(public string $url)
    {
    }
}
