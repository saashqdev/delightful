<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AIImage\Tools;

use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use Closure;

#[BuiltInToolDefine]
class AIImageForHighAesGeneralV21LBuiltInTool extends AbstractAIImageBuiltInTool
{
    public function getToolSetCode(): string
    {
        return BuiltInToolSet::AIImage->getCode();
    }

    public function getName(): string
    {
        return 'ai_image_high_aes_general_v21_l';
    }

    public function getDescription(): string
    {
        return 'text generationgraphtool-Volcano-High-Aes-General-V21-Lmodel';
    }

    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            $this->executeCallback($executionData, 'high_aes_general_v21_L');
        };
    }
}
