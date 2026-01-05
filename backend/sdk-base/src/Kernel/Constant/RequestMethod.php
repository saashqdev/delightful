<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Dtyq\SdkBase\Kernel\Constant;

enum RequestMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Delete = 'DELETE';
    case Head = 'HEAD';
    case Patch = 'PATCH';
}
