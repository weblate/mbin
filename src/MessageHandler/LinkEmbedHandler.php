<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\LinkEmbedMessage;
use App\Repository\EmbedRepository;
use App\Utils\Embed;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LinkEmbedHandler
{
    public function __construct(private EmbedRepository $embedRepository, private Embed $embed)
    {
    }

    public function __invoke(LinkEmbedMessage $message): void
    {
        try {
            $embed = $this->embed->fetch($message->url)->html;
            if ($embed) {
                $entity = new \App\Entity\Embed($message->url, (bool)$embed);
                $this->embedRepository->add($entity);
            }
        } catch (\Exception $e) {
            $embed = false;
        }

        if (!$embed) {
            $entity = new \App\Entity\Embed($message->url, $embed = false);
            $this->embedRepository->add($entity);
        }
    }
}

