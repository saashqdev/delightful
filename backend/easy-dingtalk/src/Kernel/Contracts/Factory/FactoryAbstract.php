<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\EasyDingTalk\Kernel\Contracts\Factory;

use BeDelightful\EasyDingTalk\Kernel\Contracts\Endpoint\Endpoint;
use BeDelightful\EasyDingTalk\Kernel\Contracts\Endpoint\EndpointInterface;
use BeDelightful\EasyDingTalk\Kernel\Exceptions\SystemException;
use BeDelightful\SdkBase\SdkBase;

abstract class FactoryAbstract
{
    private array $fetchedDefinitions = [];

    public function __construct(string $appName, SdkBase $sdkBase)
    {
        $this->register($appName, $sdkBase);
    }

    /**
     * @throws SystemException
     */
    public function __get(string $name)
    {
        $provider = $this->fetchedDefinitions[$name] ?? null;
        if (! $provider) {
            throw new SystemException("no allowed provider [{$name}]");
        }
        return $provider;
    }

    abstract protected function getEndpoints(): array;

    protected function register(string $appName, SdkBase $sdkBase): void
    {
        foreach ($this->getEndpoints() as $key => $endpointClass) {
            if ($endpointClass instanceof Endpoint) {
                $endpoint = $endpointClass;
            } else {
                if (! is_string($endpointClass) || ! class_exists($endpointClass)) {
                    continue;
                }
                $implements = class_implements($endpointClass);
                if (! in_array(EndpointInterface::class, $implements)) {
                    continue;
                }
                $endpoint = new $endpointClass($sdkBase);
            }
            if (is_integer($key)) {
                $key = lcfirst(class_basename($endpointClass));
            }
            $endpoint->selectApp($appName);

            $this->fetchedDefinitions[$key] = $endpoint;
        }
    }
}
