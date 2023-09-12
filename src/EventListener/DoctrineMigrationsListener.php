<?php

declare(strict_types=1);

namespace App\EventListener;

use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When('dev')]
#[AutoconfigureTag('doctrine.event_listener', ['event' => ToolEvents::postGenerateSchema])]
class DoctrineMigrationsListener
{
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();

        if (!$schema->hasNamespace('public')) {
            $schema->createNamespace('public');
        }
    }
}
