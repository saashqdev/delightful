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
            // notlimititem数,所bynothavedownone页
            $hasMore = false;
        } elseif ($maxRecords !== null) {
            // ifknow总record数,thendirectlycompare
            $hasMore = ($currentOffset + $currentLimit) < $maxRecords;
        } else {
            // ifnotknow总record数,whenfrontresultcollectionnotfornullthenhavedownone页
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
