<?php

declare(strict_types=1);

namespace App\Factory;

use App\DTO\UserDto;
use App\DTO\UserSmallResponseDto;
use App\Entity\User;

class UserFactory
{
    public function __construct(private readonly ImageFactory $imageFactory)
    {
    }

    public function createDto(User $user): UserDto
    {
        return UserDto::create(
            $user->username,
            $user->email,
            $user->avatar ? $this->imageFactory->createDto($user->avatar) : null,
            $user->cover ? $this->imageFactory->createDto($user->cover) : null,
            $user->about,
            $user->lastActive,
            $user->fields,
            $user->apId,
            $user->apProfileId,
            $user->getId(),
            $user->followersCount,
            $user->isBot
        );
    }

    public function createSmallDto(User $user): UserSmallResponseDto
    {
        return new UserSmallResponseDto($this->createDto($user));
    }

    public function createDtoFromAp($apProfileId, $apId): UserDto
    {
        $dto = (new UserDto())->create(username: '@'.$apId, email: $apId, apId: $apId, apProfileId: $apProfileId);
        $dto->plainPassword = bin2hex(random_bytes(20));

        return $dto;
    }
}
