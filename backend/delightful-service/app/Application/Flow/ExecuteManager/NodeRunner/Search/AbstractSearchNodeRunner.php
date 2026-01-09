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
            // null、''、0、[]、false  直接skip吧，谁not事搜这些啊。right侧not填value的notconductsearch
            if (empty($rightValue)) {
                continue;
            }

            // definition本time的 range id，if是 null 代tablealso未conduct限制
            $rangeIds = null;
            if ($filterType->isAll()) {
                // if是所haveitemitem满足，那么已经存in的 id setthen是本time的range
                $rangeIds = $allIds;
            }

            // ifrange id bedefinitionbecome了空array，代table已经nothave符合itemitem的data了，直接跳出loop
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
            // null 代tablenot supported的searchtype，直接skip
            if ($currentIds === null) {
                continue;
            }
            if ($filterType->isAny()) {
                // if是任意itemitem满足，那么将本time的 id 与已have的 id conductmerge
                $allIds = array_merge($allIds ?? [], $currentIds);
            } else {
                // if是所haveitemitem满足，那么将本time的 id 与已have的 id conduct交集
                $allIds = $allIds === null ? $currentIds : array_intersect($allIds, $currentIds);
            }
        }

        $allIds = $allIds ?? [];

        return array_values(array_unique($allIds));
    }
}
