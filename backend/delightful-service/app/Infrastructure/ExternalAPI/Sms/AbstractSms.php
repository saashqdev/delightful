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
        // according toshort信驱动certainto应语type,andconduct语type兜bottom
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
     *  toat $smsStruct , if language intemplatemiddlenot存in,thenuse default_language conductdetect
     *  if default_language alsonothaveto应template,then按 type intemplatemiddlematch存in语type,if存in多type,byzh_CN优先.
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
     * according to语typerequireandshort信supportsignaturelist,returnto应signature本文.
     */
    public function getSign(SmsStruct $smsStruct): string
    {
        /* @phpstan-ignore-next-line */
        return $this->template->formatSign($smsStruct->sign->value, $smsStruct->language, $smsStruct->defaultLanguage);
    }

    /**
     * willvariablevalueandvariable名associate,also原short信content.
     * @param array $variables short信variable部minute,maybeis valuearray,alsomaybeis key=>valuearray,need按$templateContentcontent,统onealso原becomekey=>valuearray
     */
    protected function translateContent(string $templateContent, array $variables): string
    {
        if (empty($templateContent)) {
            return Json::encode($variables);
        }
        // conductvariablematchshort信match
        if (! empty($variables)) {
            // compatible火山templatevariablereplace,先will $message middlevariableparseoutcome such aswill[123456] parsefor['VerificationCode'=>123456]back,againconducttemplatecontentreplace
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
