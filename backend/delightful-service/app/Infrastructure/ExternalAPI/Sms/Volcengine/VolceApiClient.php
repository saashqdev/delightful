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
        // VolcengineSms needeachtimeshort信重new new
        return make(VolcengineSms::class)->request($smsStruct->phone, $variables, $smsStruct->sign, $smsStruct->templateId);
    }

    public function getContent(SmsStruct $smsStruct): string
    {
        if (empty($smsStruct->variables)) {
            return $smsStruct->content ?: '';
        }
        $templateContent = $this->template->getContentByTemplateId($smsStruct->getTemplateId());
        // 按variableorder,also原becomecompleteshort信text
        return $this->translateContent($templateContent, $smsStruct->variables);
    }

    /**
     * parsepass invariablevariableor者textshort信,totemplateshort信variableassociatearray.
     */
    private function parseVariables(SmsStruct $smsStruct): array
    {
        $variables = $smsStruct->variables;
        $smsStruct->language = $this->getContentLanguage($smsStruct);
        // Volcanoshort信onlysupportvariableshort信,according tocomplete $message adaptto应 templatevariable

        // $variables maybeforindexarray ["quotient品A","supplyquotientA",10],Volcanoshort信needalso原becomeassociatearray
        if ($smsStruct->templateId && $this->array_is_list($variables)) {
            // 1.gettemplatecontent,certainvariablekey
            $templateContent = $this->template->getContentByTemplateId($smsStruct->getTemplateId()) ?? '';
            // 2.according tovariablekey,also原associatearray
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
