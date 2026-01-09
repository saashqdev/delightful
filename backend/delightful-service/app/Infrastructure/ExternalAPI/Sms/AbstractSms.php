<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

use App\Infrastructure\ExternalAPI\Sms\Enum\LanguageEnum;
use Hyperf\Codec\Json;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Stringable\Str;

abstract class AbstractSms implements SmsInterface
{
    #[Inject]
    protected StdoutLoggerInterface $logger;

    protected TemplateInterface $template;

    public function getContent(SmsStruct $smsStruct): string
    {
        // according to短信驱动确定对应的语种,并进行语种兜底
        $language = $this->getContentLanguage($smsStruct);
        if (empty($smsStruct->variables)) {
            return $smsStruct->content ?: '';
        }
        $templateContent = '';
        if ($smsStruct->templateId) {
            $templateContent = $this->template->getContentByTemplateId($smsStruct->templateId);
        }
        if (! $templateContent && $smsStruct->type) {
            $templateContent = $this->template->getContentBySMSTypeAndLanguage($smsStruct->type, $language);
        }
        return $this->translateContent($templateContent, $smsStruct->variables);
    }

    public function getTemplateId(SmsStruct $smsStruct): ?string
    {
        $templateId = null;
        $smsStruct->language = $this->getContentLanguage($smsStruct);
        if ($smsStruct->type && $smsStruct->language) {
            $templateId = $this->template->getTemplateIdByTypeAndLanguage($smsStruct->type, $smsStruct->language);
        }
        return $templateId;
    }

    /**
     *  对于 $smsStruct , 如果 language 在template中不存在,则用 default_language 进行检测
     *  如果 default_language 也没有对应的template,则按 type 在template中匹配存在的语种,如果存在多种,以zh_CN优先.
     */
    public function getContentLanguage(SmsStruct $smsStruct): string
    {
        $language = $smsStruct->defaultLanguage ?: LanguageEnum::ZH_CN->value;
        $language = $smsStruct->language ?: $language;
        if ($smsStruct->templateId || empty($smsStruct->type)) {
            return $language;
        }

        $languages = $this->template->getTemplateLanguagesByType($smsStruct->type);

        if (empty($languages)) {
            return $language;
        }

        return match (true) {
            in_array($smsStruct->language, $languages, true) => $smsStruct->language,
            in_array($smsStruct->defaultLanguage, $languages, true) => $smsStruct->defaultLanguage,
            in_array(LanguageEnum::ZH_CN, $languages, true) => LanguageEnum::ZH_CN->value,
            default => $languages[0],
        };
    }

    /**
     * according to语种要求和短信支持的signaturelist,return对应的signature本文.
     */
    public function getSign(SmsStruct $smsStruct): string
    {
        /* @phpstan-ignore-next-line */
        return $this->template->formatSign($smsStruct->sign->value, $smsStruct->language, $smsStruct->defaultLanguage);
    }

    /**
     * 将variable的value与variable名关联,还原短信content.
     * @param array $variables 短信的variable部分,可能是 valuearray,也可能是 key=>valuearray,need按$templateContent的content,统一还原成key=>valuearray
     */
    protected function translateContent(string $templateContent, array $variables): string
    {
        if (empty($templateContent)) {
            return Json::encode($variables);
        }
        // 进行variable匹配短信匹配
        if (! empty($variables)) {
            // 兼容火山template的variable替换,先将 $message 中的variable解析出来 such as将[123456] 解析为['VerificationCode'=>123456]后,再进行templatecontent替换
            $variables = $this->template->getTemplateVariables($templateContent, $variables);
            $i = 1;
            foreach ($variables as $k => $v) {
                $v = (string) $v;
                if (Str::contains($templateContent, '${' . $k . '}')) {
                    $templateContent = str_replace('${' . $k . '}', $v, $templateContent);
                } elseif (Str::contains($templateContent, "{\${$k}}")) {
                    $templateContent = str_replace("{\${$k}}", $v, $templateContent);
                } else {
                    $templateContent = str_replace('{' . $i . '}', $v, $templateContent);
                    ++$i;
                }
            }
        }
        return $templateContent;
    }
}
