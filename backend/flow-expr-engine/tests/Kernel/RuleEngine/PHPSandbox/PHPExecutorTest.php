<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Test\Kernel\RuleEngine\PHPSandbox;

use Delightful\FlowExprEngine\SdkInfo;
use Delightful\FlowExprEngine\Test\BaseTestCase;
use Delightful\RuleEngineCore\PhpScript\Admin\RuleExecutionSetProperties;
use Delightful\RuleEngineCore\PhpScript\RuleType;
use Delightful\RuleEngineCore\Standards\Admin\InputType;
use Delightful\RuleEngineCore\Standards\RuleServiceProviderManager;
use Delightful\RuleEngineCore\Standards\RuleSessionType;
use Delightful\RuleEngineCore\Standards\StatelessRuleSessionInterface;

/**
 * @internal
 * @coversNothing
 */
class PHPExecutorTest extends BaseTestCase
{
    public function testExecuteCode()
    {
        $code = <<<'PHP'
echo "hello ".PHP_EOL;
PHP;
        $input = [$code];

        $uri = SdkInfo::RULE_SERVICE_PROVIDER;
        $ruleProvider = RuleServiceProviderManager::getRuleServiceProvider($uri);
        $admin = $ruleProvider->getRuleAdministrator();
        $ruleExecutionSetProvider = $admin->getRuleExecutionSetProvider(InputType::from(InputType::String));

        $ruleExecutionSetProperties = new RuleExecutionSetProperties();
        $ruleExecutionSetProperties->setName('test');
        $ruleExecutionSetProperties->setRuleType(RuleType::Script);

        $properties = $ruleExecutionSetProperties;
        $bindUri = $properties->getName();
        $set = $ruleExecutionSetProvider->createRuleExecutionSet($input, $properties);
        $admin->registerRuleExecutionSet($bindUri, $set, $properties);
        $runtime = $ruleProvider->getRuleRuntime();
        /** @var StatelessRuleSessionInterface $ruleSession */
        $ruleSession = $runtime->createRuleSession($bindUri, $properties, RuleSessionType::from(RuleSessionType::Stateless));

        ob_start();
        $result = $ruleSession->executeRules([])[0] ?? null;
        $debug = ob_get_clean();
        $this->assertEquals('hello ' . PHP_EOL, $debug);
        $this->assertNull($result);
    }
}
