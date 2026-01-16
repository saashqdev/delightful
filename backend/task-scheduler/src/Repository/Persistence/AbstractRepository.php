<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\TaskScheduler\Repository\Persistence;

use Delightful\TaskScheduler\Util\Functions;
use Hyperf\Database\Model\Builder;

abstract class AbstractRepository
{
    protected function createBuilder(Builder $builder): Builder
    {
        $appEnv = Functions::getEnv();
        if (! empty($appEnv)) {
            $builder->where('environment', $appEnv);
        }
        return $builder;
    }
}
