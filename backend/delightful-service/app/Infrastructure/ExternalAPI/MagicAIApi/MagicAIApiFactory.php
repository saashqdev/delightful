<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\DelightfulAIApi;

use App\Infrastructure\ExternalAPI\DelightfulAIApi\Kernel\DelightfulAIApiException;
use Delightful\SdkBase\SdkBase;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

class DelightfulAIApiFactory
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(array $configs = []): DelightfulAIApi
    {
        if (empty($configs)) {
            $configs = $this->container->get(ConfigInterface::class)->get('delightful_ai');
        }
        $configs['sdk_name'] = DelightfulAIApi::NAME;
        $configs['exception_class'] = DelightfulAIApiException::class;
        $sdkBase = new SdkBase($this->container, $configs);
        return new DelightfulAIApi($sdkBase);
    }
}
