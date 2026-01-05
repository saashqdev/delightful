<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure;

use Delightful\FlowExprEngine\ComponentContext;
use Delightful\FlowExprEngine\Exception\FlowExprEngineException;
use Delightful\FlowExprEngine\Kernel\RuleEngine\RuleEngineClientInterface;
use Delightful\FlowExprEngine\Kernel\Utils\Functions;
use Delightful\FlowExprEngine\Structure\Condition\Condition;
use Delightful\FlowExprEngine\Structure\Expression\Expression;
use Throwable;

class CodeRunner
{
    protected static ?RuleEngineClientInterface $ruleEngineClient = null;

    public static function register(RuleEngineClientInterface $ruleEngineClient): void
    {
        self::$ruleEngineClient = $ruleEngineClient;
    }

    public static function getCodeByExpression(Expression $expression): string
    {
        return self::getRuleEngineClient()->getCodeByExpression($expression);
    }

    public static function getCodeByCondition(Condition $condition): string
    {
        return self::getRuleEngineClient()->getCodeByCondition($condition);
    }

    public static function execute(string $code, array $data = []): mixed
    {
        $logContext = [
            'tag' => 'code_execute_success',
            'elapsed_time' => 0,
            'code' => $code,
            'data' => $data,
        ];
        $startTime = microtime(true);

        try {
            $result = self::getRuleEngineClient()->execute($code, $data);
            $logContext['result'] = $result;
            return $result;
        } catch (Throwable $throwable) {
            $logContext['tag'] = 'code_execute_fail';
            $logContext['result'] = $throwable->getMessage();
            $logContext['error'] = $throwable->getPrevious()?->getMessage();
            throw new FlowExprEngineException('code_execute_fail | ' . $code . ' | error: ' . $logContext['error'], $throwable->getCode(), $throwable);
        } finally {
            $logContext['elapsed_time'] = round((microtime(true) - $startTime) * 1000, 2);
            Functions::logEnabled() && ComponentContext::getSdkContainer()->getLogger()->info('CodeRunner::execute', $logContext);
        }
    }

    public static function check(string $code, bool $cache = true): void
    {
        $effective = self::getRuleEngineClient()->isEffective($code);

        if (! $effective) {
            throw new FlowExprEngineException("code [{$code}] is not effective");
        }
    }

    public static function getRuleEngineClient(): RuleEngineClientInterface
    {
        if (self::$ruleEngineClient === null) {
            throw new FlowExprEngineException('RuleEngineClient is not set');
        }
        return self::$ruleEngineClient;
    }
}
