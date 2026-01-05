<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\RuleEngineCore\PhpScript\Admin;

use Delightful\RuleEngineCore\PhpScript\PlaceholderProviderInterface;
use Delightful\RuleEngineCore\PhpScript\Repository\ExecutableCodeRepositoryInterface;
use Delightful\RuleEngineCore\PhpScript\RuleServiceProvider;
use Delightful\RuleEngineCore\Standards\Admin\Properties;
use Delightful\RuleEngineCore\Standards\Admin\RuleExecutionSetInterface;
use Delightful\RuleEngineCore\Standards\Admin\RuleExecutionSetProviderInterface;

class StringRuleExecutionSetProvider implements RuleExecutionSetProviderInterface
{
    public function __construct(
        private ParserConfig $parserConfig,
        private ExecutableCodeRepositoryInterface $executableCodeRepository,
    ) {
    }

    /**
     * @param null|RuleExecutionSetProperties $properties
     */
    public function createRuleExecutionSet(mixed $input, Properties $properties): RuleExecutionSetInterface
    {
        $set = new RuleExecutionSet();
        $set->setPlaceholderProvider(RuleServiceProvider::createInstance(PlaceholderProviderInterface::class));
        $properties->setExecutableFunctions($this->executableCodeRepository->getExecutableCodesByType(ExecutableType::from(ExecutableType::FUNCTION_TYPE), $properties->getRuleGroup(), $properties));
        $properties->setExecutableClasses($this->executableCodeRepository->getExecutableCodesByType(ExecutableType::from(ExecutableType::CLASS_TYPE), $properties->getRuleGroup(), $properties));
        $properties->setExecutableConstants($this->executableCodeRepository->getExecutableCodesByType(ExecutableType::from(ExecutableType::CONSTANT_TYPE), $properties->getRuleGroup(), $properties));
        empty($properties->getParserConfig()) && $properties->setParserConfig($this->parserConfig);
        $set->create($input, $properties);

        return $set;
    }
}
