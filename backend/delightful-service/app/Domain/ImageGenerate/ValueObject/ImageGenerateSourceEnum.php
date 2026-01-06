<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\ValueObject;

enum ImageGenerateSourceEnum: string
{
    // 超级麦吉
    case SUPER_DELIGHTFUL = 'super_magic';

    // agent
    case AGENT = 'agent';

    // 工具
    case TOOL = 'tool';

    // 流程节点
    case FLOW_NODE = 'flow_node';

    // API
    case API = 'api';
    case NONE = 'none';

    // API 平台
    case API_PLATFORM = 'api_platform';
}
