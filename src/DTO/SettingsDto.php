<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema()]
class SettingsDto implements \JsonSerializable
{
    public ?string $KBIN_DOMAIN = null;
    public ?string $KBIN_TITLE = null;
    public ?string $KBIN_META_TITLE = null;
    public ?string $KBIN_META_KEYWORDS = null;
    public ?string $KBIN_META_DESCRIPTION = null;
    public ?string $KBIN_DEFAULT_LANG = null;
    public ?string $KBIN_CONTACT_EMAIL = null;
    public ?string $KBIN_SENDER_EMAIL = null;
    public ?bool $KBIN_JS_ENABLED = null;
    public ?bool $KBIN_FEDERATION_ENABLED = null;
    public ?bool $KBIN_REGISTRATIONS_ENABLED = null;
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    public ?array $KBIN_BANNED_INSTANCES = null;
    public ?bool $KBIN_HEADER_LOGO = null;
    public ?bool $KBIN_CAPTCHA_ENABLED = null;
    public ?bool $KBIN_MERCURE_ENABLED = null;
    public ?bool $KBIN_ADMIN_ONLY_OAUTH_CLIENTS = null;

    public function __construct(
        string $KBIN_DOMAIN,
        string $KBIN_TITLE,
        string $KBIN_META_TITLE,
        string $KBIN_META_KEYWORDS,
        string $KBIN_META_DESCRIPTION,
        string $KBIN_DEFAULT_LANG,
        string $KBIN_CONTACT_EMAIL,
        string $KBIN_SENDER_EMAIL,
        bool $KBIN_JS_ENABLED,
        bool $KBIN_FEDERATION_ENABLED,
        bool $KBIN_REGISTRATIONS_ENABLED,
        array $KBIN_BANNED_INSTANCES,
        bool $KBIN_HEADER_LOGO,
        bool $KBIN_CAPTCHA_ENABLED,
        bool $KBIN_MERCURE_ENABLED,
        bool $KBIN_ADMIN_ONLY_OAUTH_CLIENTS
    ) {
        $this->KBIN_DOMAIN = $KBIN_DOMAIN;
        $this->KBIN_TITLE = $KBIN_TITLE;
        $this->KBIN_META_TITLE = $KBIN_META_TITLE;
        $this->KBIN_META_KEYWORDS = $KBIN_META_KEYWORDS;
        $this->KBIN_META_DESCRIPTION = $KBIN_META_DESCRIPTION;
        $this->KBIN_DEFAULT_LANG = $KBIN_DEFAULT_LANG;
        $this->KBIN_CONTACT_EMAIL = $KBIN_CONTACT_EMAIL;
        $this->KBIN_SENDER_EMAIL = $KBIN_SENDER_EMAIL;
        $this->KBIN_JS_ENABLED = $KBIN_JS_ENABLED;
        $this->KBIN_FEDERATION_ENABLED = $KBIN_FEDERATION_ENABLED;
        $this->KBIN_REGISTRATIONS_ENABLED = $KBIN_REGISTRATIONS_ENABLED;
        $this->KBIN_BANNED_INSTANCES = $KBIN_BANNED_INSTANCES;
        $this->KBIN_HEADER_LOGO = $KBIN_HEADER_LOGO;
        $this->KBIN_CAPTCHA_ENABLED = $KBIN_CAPTCHA_ENABLED;
        $this->KBIN_MERCURE_ENABLED = $KBIN_MERCURE_ENABLED;
        $this->KBIN_ADMIN_ONLY_OAUTH_CLIENTS = $KBIN_ADMIN_ONLY_OAUTH_CLIENTS;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'KBIN_DOMAIN' => $this->KBIN_DOMAIN,
            'KBIN_TITLE' => $this->KBIN_TITLE,
            'KBIN_META_TITLE' => $this->KBIN_META_TITLE,
            'KBIN_META_KEYWORDS' => $this->KBIN_META_KEYWORDS,
            'KBIN_META_DESCRIPTION' => $this->KBIN_META_DESCRIPTION,
            'KBIN_DEFAULT_LANG' => $this->KBIN_DEFAULT_LANG,
            'KBIN_CONTACT_EMAIL' => $this->KBIN_CONTACT_EMAIL,
            'KBIN_SENDER_EMAIL' => $this->KBIN_SENDER_EMAIL,
            'KBIN_JS_ENABLED' => $this->KBIN_JS_ENABLED,
            'KBIN_FEDERATION_ENABLED' => $this->KBIN_FEDERATION_ENABLED,
            'KBIN_REGISTRATIONS_ENABLED' => $this->KBIN_REGISTRATIONS_ENABLED,
            'KBIN_BANNED_INSTANCES' => $this->KBIN_BANNED_INSTANCES,
            'KBIN_HEADER_LOGO' => $this->KBIN_HEADER_LOGO,
            'KBIN_CAPTCHA_ENABLED' => $this->KBIN_CAPTCHA_ENABLED,
            'KBIN_MERCURE_ENABLED' => $this->KBIN_MERCURE_ENABLED,
            'KBIN_ADMIN_ONLY_OAUTH_CLIENTS' => $this->KBIN_ADMIN_ONLY_OAUTH_CLIENTS,
        ];
    }
}
