<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Image;
use App\Entity\User;
use OpenApi\Attributes as OA;

#[OA\Schema()]
class UserResponseDto implements \JsonSerializable
{
    public ?ImageDto $avatar = null;
    public ?ImageDto $cover = null;
    public string $username;
    public int $followersCount = 0;
    public ?string $about = null;
    public ?\DateTime $lastActive = null;
    public ?string $apProfileId = null;
    public ?string $apId = null;
    public ?bool $isBot = null;
    public ?int $userId = null;

    public function __construct(UserDto|User $dto)
    {
        $this->userId = $dto->getId();
        $this->username = $dto->username;
        $this->about = $dto->about;
        if($dto->avatar instanceof Image) {
            $this->avatar = new ImageDto($dto->avatar);
        } else {
            $this->avatar = $dto->avatar;
        }
        if($dto->cover instanceof Image) {
            $this->cover = new ImageDto($dto->cover);
        } else {
            $this->cover = $dto->cover;
        }
        $this->lastActive = $dto->lastActive;
        $this->apId = $dto->apId;
        $this->apProfileId = $dto->apProfileId;
        $this->followersCount = $dto->followersCount;
        $this->isBot = true === $dto->isBot;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'userId' => $this->userId,
            'username' => $this->username,
            'about' => $this->about,
            'avatar' => $this->avatar ? $this->avatar->jsonSerialize() : null,
            'cover' => $this->cover ? $this->cover->jsonSerialize() : null,
            'lastActive' => $this->lastActive?->format(\DateTimeInterface::ATOM),
            'followersCount' => $this->followersCount,
            'apId' => $this->apId,
            'apProfileId' => $this->apProfileId,
            'isBot' => $this->isBot,
        ];
    }
}
