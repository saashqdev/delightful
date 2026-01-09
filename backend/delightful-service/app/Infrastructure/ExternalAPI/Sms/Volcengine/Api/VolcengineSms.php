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
 * 火山引起短信类接口.
 * @see https://www.volcengine.com/docs/6361/171579
 */
class VolcengineSms extends VolcengineApi
{
    protected string $method = 'POST';

    protected string $path = '/';

    /**
     * 接口name.
     */
    protected string $action = 'SendSms';

    /**
     * 接口版本.
     */
    protected string $version = '2020-01-01';

    #[Inject]
    protected Template $template;

    /**
     * 发送验证码,火山的验证码短信不支持传入指定的数字.
     */
    public function request(string $phone, array $templateVariables, SignEnum $sign, string $templateId): SendResult
    {
        // 去掉手机号的特殊格式
        $phone = str_replace(['+00', '-'], '', $phone);
        $sendResult = new SendResult();
        $signStr = SignEnum::format($sign, LanguageEnum::EN_US);
        if (empty($templateVariables)) {
            return $sendResult->setResult(-1, '未匹配到对应的短信template!');
        }
        if (! in_array($signStr, Template::$signToMessageGroup, true)) {
            return $sendResult->setResult(-1, '短信signature:' . $signStr . ' 不支持!');
        }

        $errCode = 0;
        $msg = 'success';
        try {
            $groupId = $this->template->getMessageGroupId($templateId);
            // initialize,set公共的请求parameter
            $this->init($groupId, $signStr, $templateId);
            // set验证码短信的特有body结构
            $body = [
                'SmsAccount' => $this->getMessageGroupId(),
                'Sign' => $this->getSign(),
                'TemplateID' => $this->getTemplateId(),
                'TemplateParam' => Json::encode($templateVariables),
                'PhoneNumbers' => $phone,
            ];
            $this->setBody($body);
            // 如果是单元test,不发短信,只验证variable解析/短信content&&短信signature多语种适配/国际区号正确解析
            if (defined('IN_UNIT_TEST')) {
                // 单元test,不真的发短信
                return $sendResult->setResult($errCode, $msg);
            }
            $this->sendRequest();
        } catch (Throwable$exception) {
            $errCode = -1;
            $msg = '短信发送fail';
            $this->logger->error('短信发送fail：' . $exception->getMessage() . ',trace:' . $exception->getTraceAsString());
        }
        // 将returnresult与创蓝统一,避免bug
        return $sendResult->setResult($errCode, $msg);
    }
}
