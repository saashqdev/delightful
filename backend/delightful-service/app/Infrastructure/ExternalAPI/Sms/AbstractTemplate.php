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
     * 短信typeandtemplateidmappingclose系.
     */
    protected array $typeToIdMap = [];

    /**
     * according to短信type,conductvariable短信适配,also原整item短信textcontent.
     */
    protected array $typeContents = [];

    /**
     * according totemplateid,conductvariable短信适配,also原整item短信textcontent.
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
        // matchtext ${code} middlecode
        $matched = preg_match_all('/\$\{([^}]+)}/uS', $content, $matches);
        // matchtext {$code} middlecode
        ! $matched && $matched = preg_match_all('/\{\$([^}]+)}/uS', $content, $matches);
        if (! $matched) {
            return $messages;
        }
        $variables = [];
        // template$contentmiddlenot存in "${xxx}" or者 {$xxx) typecharacter.then按index顺序match
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
     * according to短信type,returntypesupport语typelist.
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
        // signaturetypecertain
        if (empty($sign)) {
            $sign = $this->getTemplateDefaultSignType($sign);
        }
        if (empty($this->signMap[$sign])) {
            // signaturetypenot存in,直接return
            return $sign;
        }

        // certainsignature语type,needfrom userfinger定语type,userfinger定兜bottom语type,systemdefault兜bottom语type middlecertainoutcomeonevalue
        $signLanguage = null;
        // 语type兜bottom顺序
        $defaultLanguages = [$language, $defaultLanguage, LanguageEnum::EN_US, LanguageEnum::ZH_CN];
        foreach ($defaultLanguages as $value) {
            if (isset($this->signMap[$sign][$value])) {
                $signLanguage = $value;
                break;
            }
        }
        // if $sign in $defaultLanguages not存invalue,thengiveonetypesupport语type
        $firstLanguage = null;
        if (isset($this->signMap[$sign]) && is_array($this->signMap[$sign])) {
            $firstLanguage = array_key_first($this->signMap[$sign]);
        }
        $signLanguage = $signLanguage ?? $firstLanguage;
        return $this->signMap[$sign][$signLanguage] ?? $sign;
    }

    /**
     * when传入signaturetypenot存ino clock,get短信defaultsignaturetype.
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
