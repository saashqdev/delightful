<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;

/**
 * Collaboration project creator list response DTO.
 */
class CollaborationCreatorListResponseDTO extends AbstractDTO
{
    /**
     * @var CollaborationCreatorItemDTO[] Creator list
     */
    protected array $creators = [];

    /**
     * Create response DTO from user entity array.
     *
     * @param array $userEntities User entity array
     */
    public static function fromUserEntities(array $userEntities): self
    {
        $dto = new self();

        $creatorItems = [];
        foreach ($userEntities as $userEntity) {
            $creatorItems[] = CollaborationCreatorItemDTO::fromUserEntity($userEntity);
        }

        $dto->setCreators($creatorItems);
        return $dto;
    }

    /**
     * Create empty response DTO.
     */
    public static function fromEmpty(): self
    {
        return new self();
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return array_map(fn (CollaborationCreatorItemDTO $creator) => $creator->toArray(), $this->creators);
    }

    /**
     * Get creator list.
     *
     * @return CollaborationCreatorItemDTO[]
     */
    public function getCreators(): array
    {
        return $this->creators;
    }

    /**
     * Set creator list.
     *
     * @param CollaborationCreatorItemDTO[] $creators
     */
    public function setCreators(array $creators): self
    {
        $this->creators = $creators;
        return $this;
    }
}
