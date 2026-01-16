<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript;

use Delightful\RuleEngineCore\PhpScript\Admin\RuleAdministrator;
use Delightful\RuleEngineCore\PhpScript\Repository\ExecutableCodeRepositoryInterface;
use Delightful\RuleEngineCore\PhpScript\Repository\RuleExecutionSetRepositoryInterface;
use Delightful\RuleEngineCore\Standards\AbstractRuleServiceProvider;
use Delightful\RuleEngineCore\Standards\Admin\RuleAdministratorInterface;
use Delightful\RuleEngineCore\Standards\RuleRuntimeInterface;
use Hyperf\Di\Container;
use Psr\Container\ContainerInterface;

class RuleServiceProvider extends AbstractRuleServiceProvider
{
    public const RULE_SERVICE_PROVIDER = 'RuleEngineCore/php-script';

    /** @var Container */
    protected static ?ContainerInterface $container = null;

    private RuleRuntimeInterface $ruleRuntime;

    private RuleAdministratorInterface $ruleAdministrator;

    private RuleExecutionSetRepositoryInterface $executionSetRepository;

    private ExecutableCodeRepositoryInterface $executableCodeRepository;

    public function __construct()
    {
    }

    public function setExecutionSetRepository(RuleExecutionSetRepositoryInterface $executionSetRepository): RuleServiceProvider
    {
        $this->executionSetRepository = $executionSetRepository;
        return $this;
    }

    public function getExecutionSetRepository(): RuleExecutionSetRepositoryInterface
    {
        if (empty($this->executionSetRepository)) {
            $this->executionSetRepository = static::$container->make(RuleExecutionSetRepositoryInterface::class);
        }

        return $this->executionSetRepository;
    }

    public function getExecutableCodeRepository(): ExecutableCodeRepositoryInterface
    {
        if (empty($this->executableCodeRepository)) {
            $this->executableCodeRepository = static::$container->make(ExecutableCodeRepositoryInterface::class);
        }

        return $this->executableCodeRepository;
    }

    public function setExecutableCodeRepository(ExecutableCodeRepositoryInterface $executableCodeRepository): RuleServiceProvider
    {
        $this->executableCodeRepository = $executableCodeRepository;
        return $this;
    }

    public function getRuleRuntime(): RuleRuntimeInterface
    {
        if (empty($this->ruleRuntime)) {
            $this->ruleRuntime = new RuleRuntime($this->getExecutionSetRepository());
        }

        return $this->ruleRuntime;
    }

    public function getRuleAdministrator(): RuleAdministratorInterface
    {
        if (empty($this->ruleAdministrator)) {
            $this->ruleAdministrator = new RuleAdministrator($this->getExecutionSetRepository(), $this->getExecutableCodeRepository());
        }

        return $this->ruleAdministrator;
    }
}
