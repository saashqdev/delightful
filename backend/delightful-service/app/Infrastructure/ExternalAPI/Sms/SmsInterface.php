<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

interface SmsInterface
{
    /**
     * get短信templateid. andnot一定存intemplateid.
     */
    public function getTemplateId(SmsStruct $smsStruct): ?string;

    /**
     * send短信,force要求所have短信驱动return结构same.
     */
    public function send(SmsStruct $smsStruct): SendResult;

    /**
     * parse变quantity短信,return完整短信text.
     */
    public function getContent(SmsStruct $smsStruct): string;

    /**
     * get短信文案语type,andsignature无close. maybe短信contentis印尼语,signatureisEnglish.
     */
    public function getContentLanguage(SmsStruct $smsStruct): string;

    /**
     * get短信signature. need多语type适配,语type兜bottom!
     */
    public function getSign(SmsStruct $smsStruct): string;
}
