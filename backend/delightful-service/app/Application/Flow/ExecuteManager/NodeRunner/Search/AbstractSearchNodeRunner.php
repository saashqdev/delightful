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
            // null,'',0,[],false  directlyskip,谁not事搜thisthese.right侧not填valuenotconductsearch
            if (empty($rightValue)) {
                continue;
            }

            // definition本time range id,ifis null 代tablealsonotconductlimit
            $rangeIds = null;
            if ($filterType->isAll()) {
                // ifis所haveitemitemfull足,that么alreadyalready existsin id setthenis本timerange
                $rangeIds = $allIds;
            }

            // ifrange id bedefinitionbecomeemptyarray,代tablealready经nothaveconformitemitemdata,directly跳outloop
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
            // null 代tablenot supportedsearchtype,directlyskip
            if ($currentIds === null) {
                continue;
            }
            if ($filterType->isAny()) {
                // ifis任意itemitemfull足,that么will本time id andalreadyhave id conductmerge
                $allIds = array_merge($allIds ?? [], $currentIds);
            } else {
                // ifis所haveitemitemfull足,that么will本time id andalreadyhave id conduct交collection
                $allIds = $allIds === null ? $currentIds : array_intersect($allIds, $currentIds);
            }
        }

        $allIds = $allIds ?? [];

        return array_values(array_unique($allIds));
    }
}
