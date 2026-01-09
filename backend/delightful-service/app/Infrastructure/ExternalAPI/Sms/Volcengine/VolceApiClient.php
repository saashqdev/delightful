<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms\Volcengine;

use App\Infrastructure\ExternalAPI\Sms\AbstractSms;
use App\Infrastructure\ExternalAPI\Sms\SendResult;
use App\Infrastructure\ExternalAPI\Sms\SmsStruct;
use App\Infrastructure\ExternalAPI\Sms\TemplateInterface;
use App\Infrastructure\ExternalAPI\Sms\Volcengine\Api\VolcengineSms;

use function Hyperf\Support\make;

class VolceApiClient extends AbstractSms
{
    protected TemplateInterface $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    public function send(SmsStruct $smsStruct): SendResult
    {
        if (! $smsStruct->getTemplateId()) {
            $templateId = $this->getTemplateId($smsStruct);
            $smsStruct->setTemplateId($templateId);
        }
        $variables = $this->parseVariables($smsStruct);
        // VolcengineSms needeachtimeshortmessage weightnew new
        return make(VolcengineSms::class)->request($smsStruct->phone, $variables, $smsStruct->sign, $smsStruct->templateId);
    }

    public function getContent(SmsStruct $smsStruct): string
    {
        if (empty($smsStruct->variables)) {
            return $smsStruct->content ?: '';
        }
        $templateContent = $this->template->getContentByTemplateId($smsStruct->getTemplateId());
        // byvariableorder,alsooriginalbecomecompleteshortmessagetext
        return $this->translateContent($templateContent, $smsStruct->variables);
    }

    /**
     * parsepass invariablevariableorpersontextshortmessage,totemplateshortmessagevariableassociatearray.
     */
    private function parseVariables(SmsStruct $smsStruct): array
    {
        $variables = $smsStruct->variables;
        $smsStruct->language = $this->getContentLanguage($smsStruct);
        // Volcanoshortmessageonlysupportvariableshortmessage,according tocomplete $message adapttoshould templatevariable

        // $variables maybeforindexarray ["quotientproductA","supplyquotientA",10],Volcanoshortmessageneedalsooriginalbecomeassociatearray
        if ($smsStruct->templateId && $this->array_is_list($variables)) {
            // 1.gettemplatecontent,certainvariablekey
            $templateContent = $this->template->getContentByTemplateId($smsStruct->getTemplateId()) ?? '';
            // 2.according tovariablekey,alsooriginalassociatearray
            $variables = $this->template->getTemplateVariables($templateContent, $variables);
        }
        return $variables;
    }

    private function array_is_list(array $array): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }

        if ($array === [] || $array === array_values($array)) {
            return true;
        }
        $nextKey = -1;
        foreach ($array as $k => $v) {
            if ($k !== ++$nextKey) {
                return false;
            }
        }
        return true;
    }
}
