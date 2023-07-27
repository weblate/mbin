<?php

declare(strict_types=1);

namespace App\Controller\Api\User;

use App\Controller\Api\BaseApi;
use App\DTO\UserSettingsDto;
use Symfony\Component\HttpFoundation\Request;

class UserBaseApi extends BaseApi
{
    /**
     * Deserialize a user's settings from JSON.
     *
     * @param Request         $request The request containing JSON to deserialize
     * @param UserSettingsDto $dto     The UserSettingsDto to modify with new values
     *
     * @return UserSettingsDto An user with only certain fields allowed to be modified by the user
     */
    protected function deserializeUserSettings(Request $request, UserSettingsDto $dto): UserSettingsDto
    {
        $deserialized = $this->serializer->deserialize($request->getContent(), UserSettingsDto::class, 'json');
        assert($deserialized instanceof UserSettingsDto);

        $dto = $deserialized->mergeIntoDto($dto);

        return $dto;
    }
}
