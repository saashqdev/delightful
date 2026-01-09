<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\OrganizationEnvironment\DTO;

use App\Infrastructure\Core\AbstractDTO;
use Hyperf\HttpServer\Contract\RequestInterface;

class OrganizationListRequestDTO extends AbstractDTO
{
    public int $page = 1;

    public int $pageSize = 20;

    public ?string $name = null;

    public ?string $delightfulOrganizationCode = null;

    public ?int $status = null;

    public ?int $type = null;

    public ?int $syncStatus = null;

    public ?string $createdAtStart = null;

    public ?string $createdAtEnd = null;

    public string $orderBy = 'id';

    public string $orderDirection = 'asc';

    public static function fromRequest(RequestInterface $request): self
    {
        $dto = new self();
        $dto->page = (int) $request->input('page', 1);
        $dto->pageSize = (int) $request->input('page_size', 20);
        $dto->name = $request->input('name');
        $dto->delightfulOrganizationCode = $request->input('delightful_organization_code');
        $dto->status = ($request->input('status') !== null) ? (int) $request->input('status') : null;
        $dto->type = ($request->input('type') !== null) ? (int) $request->input('type') : null;
        $dto->syncStatus = ($request->input('sync_status') !== null) ? (int) $request->input('sync_status') : null;
        $dto->createdAtStart = $request->input('created_at_start');
        $dto->createdAtEnd = $request->input('created_at_end');
        // 固定sort
        $dto->orderBy = 'id';
        $dto->orderDirection = 'asc';
        return $dto;
    }

    public function toFilters(): array
    {
        return array_filter([
            'name' => $this->name,
            'delightful_organization_code' => $this->delightfulOrganizationCode,
            'status' => $this->status,
            'type' => $this->type,
            'sync_status' => $this->syncStatus,
            'created_at_start' => $this->createdAtStart,
            'created_at_end' => $this->createdAtEnd,
            'order_by' => $this->orderBy,
            'order_direction' => $this->orderDirection,
        ], static fn ($v) => $v !== null && $v !== '');
    }
}
