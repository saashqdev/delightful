<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

interface SmsInterface
{
    /**
     * getshort信templateid. andnotone定存intemplateid.
     */
    public function getTemplateId(SmsStruct $smsStruct): ?string;

    /**
     * sendshort信,forcerequire所haveshort信驱动return结构same.
     */
    public function send(SmsStruct $smsStruct): SendResult;

    /**
     * parse变quantityshort信,returncompleteshort信text.
     */
    public function getContent(SmsStruct $smsStruct): string;

    /**
     * getshort信文案语type,andsignaturenoclose. maybeshort信contentis印尼语,signatureisEnglish.
     */
    public function getContentLanguage(SmsStruct $smsStruct): string;

    /**
     * getshort信signature. need多语type适配,语type兜bottom!
     */
    public function getSign(SmsStruct $smsStruct): string;
}
