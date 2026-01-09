<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

interface SmsInterface
{
    /**
     * get短信的模板id. 并不一定存在模板id.
     */
    public function getTemplateId(SmsStruct $smsStruct): ?string;

    /**
     * 发送短信,强制要求所有短信驱动的return结构一样.
     */
    public function send(SmsStruct $smsStruct): SendResult;

    /**
     * 解析变量短信,return完整的短信文本.
     */
    public function getContent(SmsStruct $smsStruct): string;

    /**
     * get短信文案的语种,与签名无关. 可能短信content是印尼语,签名是英文.
     */
    public function getContentLanguage(SmsStruct $smsStruct): string;

    /**
     * get短信签名. 需要多语种适配,语种兜底!
     */
    public function getSign(SmsStruct $smsStruct): string;
}
