<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

use App\Infrastructure\ExternalAPI\Sms\Enum\LanguageEnum;

abstract class AbstractTemplate implements TemplateInterface
{
    /**
     * 短信type与templateid的mapping关系.
     */
    protected array $typeToIdMap = [];

    /**
     * according to短信type,进行variable短信的适配,还原整条短信文本content.
     */
    protected array $typeContents = [];

    /**
     * according totemplateid,进行variable短信的适配,还原整条短信文本content.
     */
    protected array $idContents = [];

    protected array $signMap = [];

    public function getTemplateIdByTypeAndLanguage(string $type, ?string $language): ?string
    {
        return $this->typeToIdMap[$language][$type] ?? null;
    }

    public function getContentBySMSTypeAndLanguage(string $type, ?string $language): string
    {
        $templateId = $this->getTemplateIdByTypeAndLanguage($type, $language);
        if ($templateId) {
            $content = $this->getContentByTemplateId($templateId);
        }
        if (empty($content)) {
            $content = $this->typeContents[$language][$type] ?? '';
        }
        return $content;
    }

    public function getContentByTemplateId(string $templateId): string
    {
        return $this->idContents[$templateId] ?? '';
    }

    public function getTemplateVariables(string $content, array $messages): array
    {
        $matches = [];
        // 匹配文本 ${code} 中的code
        $matched = preg_match_all('/\$\{([^}]+)}/uS', $content, $matches);
        // 匹配文本 {$code} 中的code
        ! $matched && $matched = preg_match_all('/\{\$([^}]+)}/uS', $content, $matches);
        if (! $matched) {
            return $messages;
        }
        $variables = [];
        // template$content中不存在 "${xxx}" 或者 {$xxx) type的字符.则按index顺序匹配
        foreach ($matches[1] as $index => $variableKey) {
            if (isset($messages[$variableKey])) {
                $variables[$variableKey] = $messages[$variableKey];
            } elseif (isset($messages[$index])) {
                $variables[$variableKey] = $messages[$index];
            }
        }
        return $variables;
    }

    /**
     * according to短信type,returntype支持的语种list.
     * @return string[]
     */
    public function getTemplateLanguagesByType(string $type): array
    {
        $languages = [];
        $languages[] = $this->getLanguages($type, $this->typeToIdMap);
        $languages[] = $this->getLanguages($type, $this->typeContents);
        return array_values(array_unique(array_merge(...$languages)));
    }

    public function formatSign(string $sign, ?LanguageEnum $language, ?LanguageEnum $defaultLanguage = LanguageEnum::ZH_CN): string
    {
        // signaturetype确定
        if (empty($sign)) {
            $sign = $this->getTemplateDefaultSignType($sign);
        }
        if (empty($this->signMap[$sign])) {
            // signaturetype不存在,直接return
            return $sign;
        }

        // 确定signature的语种,need从 user指定语种,user指定兜底语种,系统default的兜底语种 中确定出来一个value
        $signLanguage = null;
        // 语种兜底的顺序
        $defaultLanguages = [$language, $defaultLanguage, LanguageEnum::EN_US, LanguageEnum::ZH_CN];
        foreach ($defaultLanguages as $value) {
            if (isset($this->signMap[$sign][$value])) {
                $signLanguage = $value;
                break;
            }
        }
        // 如果 $sign 在 $defaultLanguages 不存在value,则给一个type支持的语种
        $firstLanguage = null;
        if (isset($this->signMap[$sign]) && is_array($this->signMap[$sign])) {
            $firstLanguage = array_key_first($this->signMap[$sign]);
        }
        $signLanguage = $signLanguage ?? $firstLanguage;
        return $this->signMap[$sign][$signLanguage] ?? $sign;
    }

    /**
     * 当传入的signaturetype不存在时,get短信的defaultsignaturetype.
     */
    abstract protected function getTemplateDefaultSignType(string $sign): string;

    /**
     * @return string[]
     */
    private function getLanguages(string $type, array $data): array
    {
        $languages = [];
        foreach ($data as $language => $smsTypeMap) {
            if (is_array($smsTypeMap) && array_key_exists($type, $smsTypeMap)) {
                is_string($language) && $languages[] = $language;
            }
        }
        return $languages;
    }
}
