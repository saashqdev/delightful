<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\Sub;

use App\Domain\Flow\Entity\ValueObject\NodeParamsConfig\NodeParamsConfig;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;

class SubNodeParamsConfig extends NodeParamsConfig
{
    public function validate(): array
    {
        // get子process入参andout参,byuserinputparameterfor准,canfornull,accurateproperty放toexecuteo clockvalidation
        $subFlowId = $this->node->getParams()['sub_flow_id'] ?? '';
        if (! $subFlowId) {
            ExceptionBuilder::throw(FlowErrorCode::FlowNodeValidateFailed, 'flow.node.sub.flow_id_empty');
        }

        return [
            'sub_flow_id' => $subFlowId,
        ];
    }

    public function generateTemplate(): void
    {
        $this->node->setParams([
            'sub_flow_id' => '',
        ]);
    }
}
