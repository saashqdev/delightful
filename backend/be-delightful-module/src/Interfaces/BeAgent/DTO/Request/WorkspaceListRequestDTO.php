<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Interfaces\BeAgent\DTO\Request;

use App\Infrastructure\Core\AbstractDTO;
use Delightful\BeDelightful\Domain\BeAgent\Entity\ValueObject\WorkspaceArchiveStatus;
use Hyperf\HttpServer\Contract\RequestInterface;

class WorkspaceListRequestDTO extends AbstractDTO
{
    /**
     * Is archived: 0=no, 1=yes.
     */
    public ?int $isArchived = null;

    /**
     * Page number
     */
    public int $page = 1;

    /**
     * Items per page.
     */
    public int $pageSize = 10;

    /**
     * Create DTO from request.
     */
    public static function fromRequest(RequestInterface $request): self
    {
        $dto = new self();
        $dto->isArchived = $request->has('is_archived')
            ? (int) $request->input('is_archived')
            : WorkspaceArchiveStatus::NotArchived->value;
        $dto->page = (int) ($request->input('page', 1) ?: 1);
        $dto->pageSize = (int) ($request->input('page_size', 10) ?: 10);

        return $dto;
    }

    /**
     * Build query conditions.
     */
    public function buildConditions(): array
    {
        $conditions = [];

        if ($this->isArchived !== null) {
            $conditions['is_archived'] = $this->isArchived;
        } else {
            // Default: not archived
            $conditions['is_archived'] = WorkspaceArchiveStatus::NotArchived->value;
        }

        return $conditions;
    }
}
