<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\CloudFile\Hyperf;

use BeDelightful\CloudFile\CloudFile;
use BeDelightful\CloudFile\Kernel\Exceptions\CloudFileException;
use BeDelightful\SdkBase\SdkBase;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

class CloudFileFactory
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(): CloudFile
    {
        $configs = $this->container->get(ConfigInterface::class)->get('cloudfile', []);
        $container = new SdkBase($this->container, [
            'sdk_name' => 'cloudfile',
            'exception_class' => CloudFileException::class,
            'cloudfile' => $configs,
        ]);
        return new CloudFile($container);
    }
}
