<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Assembler;

// paginationorganization器
class PageListAssembler
{
    public static function pageByMysql(array $data, int $currentOffset = 0, int $currentLimit = 0, ?int $maxRecords = null): array
    {
        if ($currentLimit === 0) {
            // not限制item数，所bynothavedown一页
            $hasMore = false;
        } elseif ($maxRecords !== null) {
            // if知道总record数，then直接compare
            $hasMore = ($currentOffset + $currentLimit) < $maxRecords;
        } else {
            // ifnot知道总record数，whenfrontresult集notfornullthenhavedown一页
            $hasMore = empty($data) ? false : true;
        }
        $nextPageToken = $hasMore ? (string) ($currentOffset + $currentLimit) : '';

        return [
            'items' => $data,
            'has_more' => (bool) $nextPageToken,
            'page_token' => $nextPageToken,
        ];
    }

    public static function pageByElasticSearch(array $data, string $requestPageToken, bool $hasMore = false): array
    {
        return [
            'items' => $data,
            'has_more' => $hasMore,
            'page_token' => $requestPageToken,
        ];
    }
}
