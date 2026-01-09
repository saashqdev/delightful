<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\NodeRunner\Search;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Application\Flow\ExecuteManager\NodeRunner\NodeRunner;
use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Search\AbstractSearchNodeParamsConfig;

abstract class AbstractSearchNodeRunner extends NodeRunner
{
    protected function getAvailableIds(ExecutionData $executionData, callable $getCurrentIds): array
    {
        /** @var AbstractSearchNodeParamsConfig $paramsConfig */
        $paramsConfig = $this->node->getNodeParamsConfig();

        $allIds = null;
        $filterType = $paramsConfig->getFilterType();
        foreach ($paramsConfig->getFilters() as $filter) {
            $rightValue = $filter->getRightValue()->getValue()->getResult($executionData->getExpressionFieldData());
            // null、''、0、[]、false  直接skip吧，谁没事搜这些啊。右侧没填value的不进行search
            if (empty($rightValue)) {
                continue;
            }

            // 定义本次的 range id，如果是 null 代table还未进行限制
            $rangeIds = null;
            if ($filterType->isAll()) {
                // 如果是所有条件满足，那么已经存在的 id 集合就是本次的range
                $rangeIds = $allIds;
            }

            // 如果range id 被定义成了空array，代table已经没有符合条件的data了，直接跳出循环
            if (is_array($rangeIds) && empty($rangeIds)) {
                break;
            }

            $currentIds = $getCurrentIds(
                $executionData->getOperator(),
                $filter->getLeftType(),
                $filter->getOperatorType(),
                $rightValue,
                $rangeIds
            );
            // null 代table不支持的searchtype，直接skip
            if ($currentIds === null) {
                continue;
            }
            if ($filterType->isAny()) {
                // 如果是任意条件满足，那么将本次的 id 与已有的 id 进行merge
                $allIds = array_merge($allIds ?? [], $currentIds);
            } else {
                // 如果是所有条件满足，那么将本次的 id 与已有的 id 进行交集
                $allIds = $allIds === null ? $currentIds : array_intersect($allIds, $currentIds);
            }
        }

        $allIds = $allIds ?? [];

        return array_values(array_unique($allIds));
    }
}
