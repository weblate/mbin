<?php

declare(strict_types=1);

namespace App\Controller\Api\User;

use App\Controller\Api\BaseApi;
use App\DTO\UserDto;
use App\DTO\UserResponseDto;
use App\DTO\UserSettingsDto;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\Attribute\Required;

class UserBaseApi extends BaseApi
{
    private RequestStack $request;

    #[Required]
    public function setRequest(RequestStack $request)
    {
        $this->request = $request;
    }

    /**
     * Serialize a single user to JSON.
     *
     * @param ?UserDto $dto The UserDto to serialize
     *
     * @return array An associative array representation of the user's safe fields, to be used as JSON
     */
    protected function serializeUser(UserDto|User $dto)
    {
        $response = new UserResponseDto($dto);

        return $response->jsonSerialize();
    }

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
