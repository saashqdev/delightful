<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

interface SmsInterface
{
    /**
     * get短信的templateid. 并不一定存在templateid.
     */
    public function getTemplateId(SmsStruct $smsStruct): ?string;

    /**
     * send短信,force要求所有短信驱动的return结构一样.
     */
    public function send(SmsStruct $smsStruct): SendResult;

    /**
     * parse变量短信,return完整的短信文本.
     */
    public function getContent(SmsStruct $smsStruct): string;

    /**
     * get短信文案的语种,与signature无关. 可能短信content是印尼语,signature是英文.
     */
    public function getContentLanguage(SmsStruct $smsStruct): string;

    /**
     * get短信signature. need多语种适配,语种兜底!
     */
    public function getSign(SmsStruct $smsStruct): string;
}
