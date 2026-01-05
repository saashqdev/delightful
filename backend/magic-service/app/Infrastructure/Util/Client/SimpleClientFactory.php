<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Client;

use GuzzleHttp\Client;
use Hyperf\Context\ApplicationContext;
use Hyperf\Guzzle\ClientFactory;

class SimpleClientFactory
{
    public function __invoke(): Client
    {
        return ApplicationContext::getContainer()->get(ClientFactory::class)->create();
    }
}
