<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\PageResponseDTO;

use App\Infrastructure\Core\AbstractDTO;

/**
 * 分页响应DTO.
 */
class PageResponseDTO extends AbstractDTO
{
    /**
     * 响应的分页Token.
     */
    protected string $pageToken = '';

    /**
     * 响应data响应，不限制数组中的元素type.
     */
    protected array $items = [];

    /**
     * 是否还有更多data.
     */
    protected bool $hasMore = false;

    public function getPageToken(): string
    {
        return $this->pageToken;
    }

    public function setPageToken(string $pageToken): void
    {
        $this->pageToken = $pageToken;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function isHasMore(): bool
    {
        return $this->hasMore;
    }

    public function getHasMore(): bool
    {
        return $this->hasMore;
    }

    public function setHasMore(bool $hasMore): void
    {
        $this->hasMore = $hasMore;
    }
}
