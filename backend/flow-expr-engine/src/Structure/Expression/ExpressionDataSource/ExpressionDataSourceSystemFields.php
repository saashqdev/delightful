<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\FlowExprEngine\Structure\Expression\ExpressionDataSource;

enum ExpressionDataSourceSystemFields: string
{
    case GuzzleResponseHttpCode = 'guzzle.response.http_code';
    case GuzzleResponseHeader = 'guzzle.response.header';
    case GuzzleResponseBody = 'guzzle.response.body';
    case LoopValue = 'loop_value';

    public function getName(): string
    {
        return match ($this) {
            self::GuzzleResponseHttpCode => 'HTTP Status Code',
            self::GuzzleResponseHeader => 'Response Header',
            self::GuzzleResponseBody => 'Response Body',
            self::LoopValue => 'Loop Value',
        };
    }

    public static function responseList(): array
    {
        return [
            self::GuzzleResponseHttpCode->value => self::GuzzleResponseHttpCode->getName(),
            self::GuzzleResponseHeader->value => self::GuzzleResponseHeader->getName(),
            self::GuzzleResponseBody->value => self::GuzzleResponseBody->getName(),
        ];
    }

    public static function loopList(): array
    {
        return [
            self::LoopValue->value => self::LoopValue->getName(),
        ];
    }

    public static function getResponseSource(string $label, string $componentId, ?string $desc = null, ?string $relationId = null): ExpressionDataSourceFields
    {
        $expressionDataSourceFields = new ExpressionDataSourceFields($label, uniqid('fields_'), $desc, $relationId);
        foreach (self::responseList() as $key => $title) {
            $value = "{$componentId}.{$key}";
            $expressionDataSourceFields->addChildren($title, $value);
        }
        return $expressionDataSourceFields;
    }

    public static function getLoopSource(string $label, string $componentId, ?string $desc = null, ?string $relationId = null): ExpressionDataSourceFields
    {
        $expressionDataSourceFields = new ExpressionDataSourceFields($label, uniqid('fields_'), $desc, $relationId);
        foreach (self::loopList() as $key => $title) {
            $value = "{$componentId}.{$key}";
            $expressionDataSourceFields->addChildren($title, $value);
        }
        return $expressionDataSourceFields;
    }
}
