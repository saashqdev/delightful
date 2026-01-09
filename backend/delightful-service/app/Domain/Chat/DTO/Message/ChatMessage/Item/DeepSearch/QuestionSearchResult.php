<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\DTO\Message\ChatMessage\Item\DeepSearch;

use App\Infrastructure\Core\AbstractObject;

/**
 * some个issue的searchresult.
 */
class QuestionSearchResult extends AbstractObject
{
    /**
     * issue id.
     */
    protected string $questionId;

    /**
     * @var SearchDetailItem[]
     */
    protected array $search;

    /**
     * 总词数.
     */
    protected int $totalWords;

    /**
     * 匹配词数.
     */
    protected int $matchCount;

    /**
     * 页数.
     */
    protected int $pageCount;

    public function getQuestionId(): string
    {
        return $this->questionId;
    }

    public function setQuestionId(string $questionId): void
    {
        $this->questionId = $questionId;
    }

    public function getSearch(): array
    {
        return $this->search;
    }

    public function setSearch(array $search): void
    {
        foreach ($search as $key => $item) {
            if (! $item instanceof SearchDetailItem) {
                $item = new SearchDetailItem($item);
            }
            // 移exceptdetail
            $item->setDetail(null);
            $search[$key] = $item;
        }
        $this->search = $search;
    }

    public function getTotalWords(): int
    {
        return $this->totalWords;
    }

    public function setTotalWords(int $totalWords): void
    {
        $this->totalWords = $totalWords;
    }

    public function getMatchCount(): int
    {
        return $this->matchCount;
    }

    public function setMatchCount(int $matchCount): void
    {
        $this->matchCount = $matchCount;
    }

    public function getPageCount(): int
    {
        return $this->pageCount;
    }

    public function setPageCount(int $pageCount): void
    {
        $this->pageCount = $pageCount;
    }
}
