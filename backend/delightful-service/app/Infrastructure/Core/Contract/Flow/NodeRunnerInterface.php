<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Contract\Flow;

use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Infrastructure\Core\Dag\VertexResult;

interface NodeRunnerInterface
{
    public function execute(VertexResult $vertexResult, ExecutionData $executionData, array $frontResults = []): void;
}
