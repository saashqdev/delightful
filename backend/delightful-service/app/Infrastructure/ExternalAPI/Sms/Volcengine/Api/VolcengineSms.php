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
 * 火山引起短信categoryinterface.
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
     * sendverify码,火山的verify码短信not supported传入finger定的number.
     */
    public function request(string $phone, array $templateVariables, SignEnum $sign, string $templateId): SendResult
    {
        // 去掉hand机number的特殊format
        $phone = str_replace(['+00', '-'], '', $phone);
        $sendResult = new SendResult();
        $signStr = SignEnum::format($sign, LanguageEnum::EN_US);
        if (empty($templateVariables)) {
            return $sendResult->setResult(-1, '未匹配to对应的短信template!');
        }
        if (! in_array($signStr, Template::$signToMessageGroup, true)) {
            return $sendResult->setResult(-1, '短信signature:' . $signStr . ' not supported!');
        }

        $errCode = 0;
        $msg = 'success';
        try {
            $groupId = $this->template->getMessageGroupId($templateId);
            // initialize,set公共的requestparameter
            $this->init($groupId, $signStr, $templateId);
            // setverify码短信的特havebody结构
            $body = [
                'SmsAccount' => $this->getMessageGroupId(),
                'Sign' => $this->getSign(),
                'TemplateID' => $this->getTemplateId(),
                'TemplateParam' => Json::encode($templateVariables),
                'PhoneNumbers' => $phone,
            ];
            $this->setBody($body);
            // if是单yuantest,nothair短信,只verifyvariableparse/短信content&&短信signature多语type适配/国际区numbercorrectparse
            if (defined('IN_UNIT_TEST')) {
                // 单yuantest,not真的hair短信
                return $sendResult->setResult($errCode, $msg);
            }
            $this->sendRequest();
        } catch (Throwable$exception) {
            $errCode = -1;
            $msg = '短信sendfail';
            $this->logger->error('短信sendfail：' . $exception->getMessage() . ',trace:' . $exception->getTraceAsString());
        }
        // 将returnresult与创蓝统一,避免bug
        return $sendResult->setResult($errCode, $msg);
    }
}
