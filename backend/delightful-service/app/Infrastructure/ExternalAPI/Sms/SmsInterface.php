<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

interface SmsInterface
{
    /**
     * getshort信templateid. andnotonefixed depositintemplateid.
     */
    public function getTemplateId(SmsStruct $smsStruct): ?string;

    /**
     * sendshort信,forcerequire所haveshortmessage drivenreturnstructuresame.
     */
    public function send(SmsStruct $smsStruct): SendResult;

    /**
     * parse变quantityshort信,returncompleteshort信text.
     */
    public function getContent(SmsStruct $smsStruct): string;

    /**
     * getshortmessage copy languagetype,andsignaturenoclose. maybeshort信contentisIndonesian,signatureisEnglish.
     */
    public function getContentLanguage(SmsStruct $smsStruct): string;

    /**
     * getshort信signature. needmulti-languagetypeadapt,语type兜bottom!
     */
    public function getSign(SmsStruct $smsStruct): string;
}
