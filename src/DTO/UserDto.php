<?php

declare(strict_types=1);

namespace App\DTO;

use App\DTO\Contracts\UserDtoInterface;
use App\Utils\RegPatterns;
use App\Validator\Unique;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @Unique(entityClass="App\Entity\User", errorPath="username", fields={"username"}, idFields="id")
 * @Unique(entityClass="App\Entity\User", errorPath="email", fields={"email"}, idFields="id")
 */
class UserDto implements UserDtoInterface
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 30)]
    #[Assert\Regex(pattern: RegPatterns::USERNAME, match: true)]
    public ?string $username = null;
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;
    #[Assert\Length(min: 6, max: 4096)]
    public ?string $plainPassword = null; // @todo move password and agreeTerms to RegisterDto
    #[Assert\Length(min: 2, max: 512)]
    public ?string $about = null;
    public ?\DateTime $lastActive = null;
    public ?string $fields = null;
    public ?ImageDto $avatar = null;
    public ?ImageDto $cover = null;
    public bool $agreeTerms = false;
    public ?string $ip = null;
    public ?string $apId = null;
    public ?string $apProfileId = null;
    public ?int $id = null;
    public ?int $followersCount = 0;
    public ?bool $isBot = null;

    #[Assert\Callback]
    public function validate(
        ExecutionContextInterface $context,
        $payload
    ) {
        if (!Request::createFromGlobals()->request->has('user_register')) {
            return;
        }

        if (false === $this->agreeTerms) {
            $this->buildViolation($context, 'agreeTerms');
        }
    }

    private function buildViolation(ExecutionContextInterface $context, $path)
    {
        $context->buildViolation('This value should not be blank.')
            ->atPath($path)
            ->addViolation();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public static function create(
        string $username,
        string $email = null,
        ImageDto $avatar = null,
        ImageDto $cover = null,
        string $about = null,
        \DateTime $lastActive = null,
        array $fields = null,
        string $apId = null,
        string $apProfileId = null,
        int $id = null,
        ?int $followersCount = 0,
        bool $isBot = null,
    ): self {
        $dto = new UserDto();
        $dto->id = $id;
        $dto->username = $username;
        $dto->email = $email;
        $dto->avatar = $avatar;
        $dto->cover = $cover;
        $dto->about = $about;
        $dto->lastActive = $lastActive;
        $dto->fields = $fields;
        $dto->apId = $apId;
        $dto->apProfileId = $apProfileId;
        $dto->followersCount = $followersCount;
        $dto->isBot = $isBot;

        return $dto;
    }
}
