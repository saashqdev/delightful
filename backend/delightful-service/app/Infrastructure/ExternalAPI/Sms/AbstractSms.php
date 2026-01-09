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
        // according to短信驱动确定对应的语type,并conduct语type兜bottom
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
     *  对at $smsStruct , if language intemplatemiddlenot存in,thenuse default_language conduct检测
     *  if default_language alsonothave对应的template,then按 type intemplatemiddle匹配存in的语type,if存in多type,byzh_CN优先.
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
     * according to语type要求和短信support的signaturelist,return对应的signature本文.
     */
    public function getSign(SmsStruct $smsStruct): string
    {
        /* @phpstan-ignore-next-line */
        return $this->template->formatSign($smsStruct->sign->value, $smsStruct->language, $smsStruct->defaultLanguage);
    }

    /**
     * 将variable的value与variable名associate,also原短信content.
     * @param array $variables 短信的variable部minute,可能是 valuearray,also可能是 key=>valuearray,need按$templateContent的content,统一also原becomekey=>valuearray
     */
    protected function translateContent(string $templateContent, array $variables): string
    {
        if (empty($templateContent)) {
            return Json::encode($variables);
        }
        // conductvariable匹配短信匹配
        if (! empty($variables)) {
            // compatible火山template的variable替换,先将 $message middle的variableparse出来 such as将[123456] parse为['VerificationCode'=>123456]back,againconducttemplatecontent替换
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
