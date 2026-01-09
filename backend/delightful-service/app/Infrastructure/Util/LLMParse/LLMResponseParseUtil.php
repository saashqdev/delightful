<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\LLMParse;

use Hyperf\Codec\Json;

class LLMResponseParseUtil
{
    public static function parseJson(string $content): ?array
    {
        $parseResult = self::parseLLMResponse($content, 'json');
        $jsonArray = $parseResult ? Json::decode($parseResult) : null;
        return is_array($jsonArray) ? $jsonArray : null;
    }

    public static function parseMarkdown(string $content): string
    {
        return self::parseLLMResponse($content, 'markdown');
    }

    private static function parseLLMResponse(string $content, string $type): ?string
    {
        $content = trim($content);
        $typePattern = sprintf('/```%s\s*([\s\S]*?)\s*```/i', $type);
        // match ```json or ``` between JSON data
        if (preg_match($typePattern, $content, $matches)) {
            $matchString = $matches[1];
        } elseif (preg_match('/```\s*([\s\S]*?)\s*```/i', $content, $matches)) { // match ``` betweencontent
            $matchString = $matches[1];
        } else {
            $matchString = ''; // nothave找to JSON data
        }
        $matchString = ! empty($matchString) ? trim($matchString) : trim($content);
        if ($type === 'json') {
            if (json_validate($matchString)) {
                return $matchString;
            }
            return null; // JSON formatnotcorrect
        }
        return $matchString;
    }
}
