<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms\Volcengine\Api;

use App\Infrastructure\ExternalAPI\Sms\Enum\LanguageEnum;
use App\Infrastructure\ExternalAPI\Sms\Enum\SignEnum;
use App\Infrastructure\ExternalAPI\Sms\SendResult;
use App\Infrastructure\ExternalAPI\Sms\Volcengine\Template;
use Hyperf\Codec\Json;
use Hyperf\Di\Annotation\Inject;
use Throwable;

/**
 * Volcanoimportupshortmessagecategoryinterface.
 * @see https://www.volcengine.com/docs/6361/171579
 */
class VolcengineSms extends VolcengineApi
{
    protected string $method = 'POST';

    protected string $path = '/';

    /**
     * interfacename.
     */
    protected string $action = 'SendSms';

    /**
     * interfaceversion.
     */
    protected string $version = '2020-01-01';

    #[Inject]
    protected Template $template;

    /**
     * sendverifycode,Volcanoverifycodeshortmessagenot supportedpass infingersetnumber.
     */
    public function request(string $phone, array $templateVariables, SignEnum $sign, string $templateId): SendResult
    {
        // godrophandmachinenumberspecialformat
        $phone = str_replace(['+00', '-'], '', $phone);
        $sendResult = new SendResult();
        $signStr = SignEnum::format($sign, LanguageEnum::EN_US);
        if (empty($templateVariables)) {
            return $sendResult->setResult(-1, 'notmatchtotoshouldshortmessagetemplate!');
        }
        if (! in_array($signStr, Template::$signToMessageGroup, true)) {
            return $sendResult->setResult(-1, 'shortmessagesignature:' . $signStr . ' not supported!');
        }

        $errCode = 0;
        $msg = 'success';
        try {
            $groupId = $this->template->getMessageGroupId($templateId);
            // initialize,setpublicrequestparameter
            $this->init($groupId, $signStr, $templateId);
            // setverifycodeshortmessage specialhavebodystructure
            $body = [
                'SmsAccount' => $this->getMessageGroupId(),
                'Sign' => $this->getSign(),
                'TemplateID' => $this->getTemplateId(),
                'TemplateParam' => Json::encode($templateVariables),
                'PhoneNumbers' => $phone,
            ];
            $this->setBody($body);
            // ifissingleyuantest,nothairshortmessage,onlyverifyvariableparse/shortmessagecontent&&shortmessagesignaturemulti-languagetypeadapt/internationalregionnumbercorrectparse
            if (defined('IN_UNIT_TEST')) {
                // singleyuantest,nottruehairshortmessage
                return $sendResult->setResult($errCode, $msg);
            }
            $this->sendRequest();
        } catch (Throwable$exception) {
            $errCode = -1;
            $msg = 'shortmessagesendfail';
            $this->logger->error('shortmessagesendfail:' . $exception->getMessage() . ',trace:' . $exception->getTraceAsString());
        }
        // willreturnresultandChuanglan systemone,avoidbug
        return $sendResult->setResult($errCode, $msg);
    }
}
