<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\SuperMagic\Interfaces\SuperAgent\DTO\Response;

use Delightful\SuperMagic\Domain\SuperAgent\Entity\ProjectForkEntity;

/**
 * Fork project response DTO.
 */
class ForkProjectResponseDTO
{
    public function __construct(
        public readonly string $status,
        public readonly string $recordId,
        public readonly string $projectId,
    ) {
    }

    public static function fromEntity(ProjectForkEntity $projectFork): self
    {
        return new self(
            status: $projectFork->getStatus()->value,
            recordId: (string) $projectFork->getId(),
            projectId: (string) $projectFork->getForkProjectId(),
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'project_id' => $this->projectId,
            'record_id' => $this->recordId,
        ];
    }
}
