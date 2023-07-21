<?php

namespace App\DTO;

use App\Entity\OAuth2UserConsent;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use OpenApi\Attributes as OA;

#[OA\Schema()]
class ClientConsentsResponseDto implements \JsonSerializable
{
    public ?int $consentId = null;
    public ?string $client = null;
    public ?string $description = null;
    public ?ImageDto $clientLogo = null;
    #[OA\Property(description: 'The scopes the app currently has permission to access', type: 'array', items: new OA\Items(type: 'string', enum: OAuth2ClientDto::AVAILABLE_SCOPES))]
    public ?array $scopesGranted = null;
    #[OA\Property(description: 'The scopes the app may request', type: 'array', items: new OA\Items(type: 'string', enum: OAuth2ClientDto::AVAILABLE_SCOPES))]
    public ?array $scopesAvailable = null;

    public function __construct(?OAuth2UserConsent $consent)
    {
        if ($consent) {
            $this->consentId = $consent->getId();
            $this->client = $consent->getClient()->getName();
            $this->description = $consent->getClient()->getDescription();
            $this->clientLogo = $consent->getClient()->getImage() ? new ImageDto($consent->getClient()->getImage()) : null;
            $this->scopesGranted = $consent->getScopes();
            $this->scopesAvailable = array_map(fn (Scope $scope) => (string) $scope, $consent->getClient()->getScopes());
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            'consentId' => $this->consentId,
            'client' => $this->client,
            'description' => $this->description,
            'clientLogo' => $this->clientLogo?->jsonSerialize(),
            'scopesGranted' => $this->scopesGranted,
            'scopesAvailable' => $this->scopesAvailable,
        ];
    }
}
