<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Response;

use App\Infrastructure\Core\AbstractDTO;
use Delightful\BeDelightful\Domain\BeAgent\Entity\TaskFileVersionEntity;

/**
 * Get file version list response DTO.
 */
class GetFileVersionsResponseDTO extends AbstractDTO
{
    protected array $list = [];

    protected int $total = 0;

    protected int $page = 1;

    public function getList(): array
    {
        return $this->list;
    }

    public function setList(array $list): void
    {
        $this->list = $list;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): void
    {
        $this->total = $total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    /**
     * Create response DTO from entity array.
     *
     * @param TaskFileVersionEntity[] $entities File version entity array
     * @param int $total Total count
     * @param int $page Current page number
     */
    public static function fromData(array $entities, int $total, int $page): self
    {
        $dto = new self();
        $dto->setTotal($total);
        $dto->setPage($page);

        $list = [];
        foreach ($entities as $entity) {
            $list[] = [
                'file_id' => (string) $entity->getFileId(),
                'version' => $entity->getVersion(),
                'edit_type' => $entity->getEditType(),
                'created_at' => $entity->getCreatedAt(),
            ];
        }

        $dto->setList($list);
        return $dto;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'list' => $this->list,
            'total' => $this->total,
            'page' => $this->page,
        ];
    }
}
