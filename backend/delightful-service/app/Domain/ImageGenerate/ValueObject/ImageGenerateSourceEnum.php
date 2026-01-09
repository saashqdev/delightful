<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\ImageGenerate\ValueObject;

enum ImageGenerateSourceEnum: string
{
    // exceedslevelMage
    case SUPER_DELIGHTFUL = 'be_delightful';

    // agent
    case AGENT = 'agent';

    // tool
    case TOOL = 'tool';

    // processsectionpoint
    case FLOW_NODE = 'flow_node';

    // API
    case API = 'api';
    case NONE = 'none';

    // API platform
    case API_PLATFORM = 'api_platform';
}
