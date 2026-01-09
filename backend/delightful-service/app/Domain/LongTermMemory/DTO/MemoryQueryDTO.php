<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\LongTermMemory\DTO;

use App\Domain\LongTermMemory\Entity\ValueObject\MemoryType;
use App\Infrastructure\Core\AbstractDTO;

/**
 * memoryquery DTO.
 */
class MemoryQueryDTO extends AbstractDTO
{
    public string $orgId = '';

    public string $appId = '';

    public string $userId = '';

    public ?array $status = null;

    public ?bool $enabled = null;

    public ?MemoryType $type = null;

    public array $tags = [];

    public ?string $keyword = null;

    public ?string $projectId = null;

    public int $limit = 50;

    public string $orderBy = 'created_at';

    public string $orderDirection = 'desc';

    // paginationrelatedclose
    public ?string $pageToken = null;

    public int $offset = 0; // offsetquantity

    public function __construct(?array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * settingmemorytype.
     */
    public function setMemoryType(MemoryType|string $type): void
    {
        if (is_string($type)) {
            $this->type = MemoryType::from($type);
        } else {
            $this->type = $type;
        }
    }

    /**
     * parse pageToken.
     */
    public function parsePageToken(): void
    {
        if ($this->pageToken === null || $this->pageToken === '') {
            $this->offset = 0;
            return;
        }

        $this->offset = (int) $this->pageToken;
    }

    /**
     * generate pageToken.
     */
    public static function generatePageToken(int $offset): string
    {
        return (string) $offset;
    }
}
