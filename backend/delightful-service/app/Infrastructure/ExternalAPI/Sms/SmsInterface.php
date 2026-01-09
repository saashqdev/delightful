<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

interface SmsInterface
{
    /**
     * getshortmessagetemplateid. andnotonefixed depositintemplateid.
     */
    public function getTemplateId(SmsStruct $smsStruct): ?string;

    /**
     * sendshortmessage,forcerequire haveshortmessage drivenreturnstructuresame.
     */
    public function send(SmsStruct $smsStruct): SendResult;

    /**
     * parsechangequantityshortmessage,returncompleteshortmessagetext.
     */
    public function getContent(SmsStruct $smsStruct): string;

    /**
     * getshortmessage copy languagetype,andsignaturenoclose. maybeshortmessagecontentisIndonesian,signatureisEnglish.
     */
    public function getContentLanguage(SmsStruct $smsStruct): string;

    /**
     * getshortmessagesignature. needmulti-languagetypeadapt,languagetypefallbackbottom!
     */
    public function getSign(SmsStruct $smsStruct): string;
}
