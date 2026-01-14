<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\TaskScheduler\Repository\Persistence;

use BeDelightful\TaskScheduler\Entity\TaskSchedulerLog;
use BeDelightful\TaskScheduler\Repository\Persistence\Model\TaskSchedulerLogModel;

class TaskSchedulerLogRepository
{
    public function create(TaskSchedulerLog $log): TaskSchedulerLog
    {
        $model = new TaskSchedulerLogModel();
        $model->fill($log->toModelArray());
        $model->save();
        $log->setId($model->id);
        return $log;
    }
}
