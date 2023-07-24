<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Image;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Annotation\Groups;

#[OA\Schema()]
class ImageDto implements \JsonSerializable
{
    #[Groups(['common'])]
    public ?string $filePath = null;
    #[Groups(['common'])]
    public ?string $sourceUrl = null;
    #[Groups(['common'])]
    public ?string $altText = null;
    #[Groups(['common'])]
    public ?int $width = null;
    #[Groups(['common'])]
    public ?int $height = null;

    public function __construct(Image $image = null)
    {
        if (null !== $image) {
            $this->filePath = $image->filePath;
            $this->sourceUrl = $image->sourceUrl;
            $this->altText = $image->altText;
            $this->width = $image->width;
            $this->height = $image->height;
        }
    }

    public function create(string $filePath, int $width = null, int $height = null, string $altText = null, string $sourceUrl = null): self
    {
        $this->filePath = $filePath;
        $this->altText = $altText;
        $this->width = $width;
        $this->height = $height;
        $this->sourceUrl = $sourceUrl;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'filePath' => $this->filePath,
            'sourceUrl' => $this->sourceUrl,
            'altText' => $this->altText,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}
