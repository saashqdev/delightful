<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Hyperf;

use Delightful\FlowExprEngine\ComponentContext;
use Delightful\FlowExprEngine\Exception\FlowExprEngineException;
use Delightful\FlowExprEngine\Kernel\RuleEngine\PHPSandbox\PHPSandboxRuleEngineClient;
use Delightful\FlowExprEngine\SdkInfo;
use Delightful\FlowExprEngine\Structure\CodeRunner;
use Delightful\SdkBase\SdkBase;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

class FlowExprEngineFactory
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function register(): void
    {
        if (! ComponentContext::hasSdkContainer()) {
            // Register SdkContainer
            ComponentContext::register($this->createSdkContainer());
            // Register expression runner
            CodeRunner::register($this->getPHPSandboxRuleEngine());
        }
    }

    private function createSdkContainer(): SdkBase
    {
        $configs = $this->container->get(ConfigInterface::class)->get('flow_expr_engine');

        $config = [
            'sdk_name' => SdkInfo::NAME,
            'exception_class' => FlowExprEngineException::class,
            'flow_expr_engine' => $configs,
        ];

        return new SdkBase($this->container, $config);
    }

    private function getPHPSandboxRuleEngine(): PHPSandboxRuleEngineClient
    {
        return new PHPSandboxRuleEngineClient();
    }
}
