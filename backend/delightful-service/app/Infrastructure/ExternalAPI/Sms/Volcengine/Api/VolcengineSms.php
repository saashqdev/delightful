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
 * 火山引upshort信categoryinterface.
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
     * sendverify码,火山verify码short信not supportedpass infinger定number.
     */
    public function request(string $phone, array $templateVariables, SignEnum $sign, string $templateId): SendResult
    {
        // go掉hand机number特殊format
        $phone = str_replace(['+00', '-'], '', $phone);
        $sendResult = new SendResult();
        $signStr = SignEnum::format($sign, LanguageEnum::EN_US);
        if (empty($templateVariables)) {
            return $sendResult->setResult(-1, 'notmatchtoto应short信template!');
        }
        if (! in_array($signStr, Template::$signToMessageGroup, true)) {
            return $sendResult->setResult(-1, 'short信signature:' . $signStr . ' not supported!');
        }

        $errCode = 0;
        $msg = 'success';
        try {
            $groupId = $this->template->getMessageGroupId($templateId);
            // initialize,setpublicrequestparameter
            $this->init($groupId, $signStr, $templateId);
            // setverify码short信特havebodystructure
            $body = [
                'SmsAccount' => $this->getMessageGroupId(),
                'Sign' => $this->getSign(),
                'TemplateID' => $this->getTemplateId(),
                'TemplateParam' => Json::encode($templateVariables),
                'PhoneNumbers' => $phone,
            ];
            $this->setBody($body);
            // ifissingleyuantest,nothairshort信,onlyverifyvariableparse/short信content&&short信signature多语type适配/国际区numbercorrectparse
            if (defined('IN_UNIT_TEST')) {
                // singleyuantest,nottruehairshort信
                return $sendResult->setResult($errCode, $msg);
            }
            $this->sendRequest();
        } catch (Throwable$exception) {
            $errCode = -1;
            $msg = 'short信sendfail';
            $this->logger->error('short信sendfail:' . $exception->getMessage() . ',trace:' . $exception->getTraceAsString());
        }
        // willreturnresultand创蓝统one,avoidbug
        return $sendResult->setResult($errCode, $msg);
    }
}
