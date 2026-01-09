<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\PageResponseDTO;

use App\Infrastructure\Core\AbstractDTO;

/**
 * paginationresponseDTO.
 */
class PageResponseDTO extends AbstractDTO
{
    /**
     * response的paginationToken.
     */
    protected string $pageToken = '';

    /**
     * responsedataresponse，不限制array中的元素type.
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
