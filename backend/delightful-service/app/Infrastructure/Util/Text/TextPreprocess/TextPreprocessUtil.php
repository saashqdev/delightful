<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Text\TextPreprocess;

use App\Infrastructure\Util\Text\TextPreprocess\Strategy\FormatExcelTextPreprocessStrategy;
use App\Infrastructure\Util\Text\TextPreprocess\Strategy\RemoveUrlEmailTextPreprocessStrategy;
use App\Infrastructure\Util\Text\TextPreprocess\Strategy\ReplaceWhitespaceTextPreprocessStrategy;
use App\Infrastructure\Util\Text\TextPreprocess\Strategy\TextPreprocessStrategyInterface;
use App\Infrastructure\Util\Text\TextPreprocess\ValueObject\TextPreprocessRule;

/**
 * 文本预处理tool.
 */
class TextPreprocessUtil
{
    /**
     * according to文本预处理规则进行预处理.
     * @param array<TextPreprocessRule> $rules
     */
    public static function preprocess(array $rules, string $text): string
    {
        // 保护tagcontent
        $protectedContent = [];
        $text = preg_replace_callback(
            '/<DelightfulCompressibleContent[^>]*>.*?<\/DelightfulCompressibleContent>/s',
            function ($matches) use (&$protectedContent) {
                $key = '{{PROTECTED_' . count($protectedContent) . '}}';
                $protectedContent[$key] = $matches[0];
                return $key;
            },
            $text
        );

        // 将FORMAT_EXCEL规则放到array前面
        $excelSheetLineRemoveRule = array_filter($rules, fn (TextPreprocessRule $rule) => $rule === TextPreprocessRule::FORMAT_EXCEL);
        $otherRules = array_filter(
            $rules,
            fn (TextPreprocessRule $rule) => $rule !== TextPreprocessRule::FORMAT_EXCEL
        );

        // ensureFORMAT_EXCEL的固定顺序
        $orderedRules = [];
        if (! empty($excelSheetLineRemoveRule)) {
            $orderedRules[] = TextPreprocessRule::FORMAT_EXCEL;
        }
        $rules = array_merge($orderedRules, $otherRules);

        foreach ($rules as $rule) {
            /** @var ?TextPreprocessStrategyInterface $strategy */
            $strategy = match ($rule) {
                TextPreprocessRule::FORMAT_EXCEL => di(FormatExcelTextPreprocessStrategy::class),
                TextPreprocessRule::REPLACE_WHITESPACE => di(ReplaceWhitespaceTextPreprocessStrategy::class),
                TextPreprocessRule::REMOVE_URL_EMAIL => di(RemoveUrlEmailTextPreprocessStrategy::class),
                default => null,
            };
            if (! $strategy instanceof TextPreprocessStrategyInterface) {
                continue;
            }
            $text = $strategy->preprocess($text);
        }

        // restoretagcontent
        foreach ($protectedContent as $key => $content) {
            $text = str_replace($key, $content, $text);
        }

        return $text;
    }
}
