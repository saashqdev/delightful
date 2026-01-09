<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

use App\Infrastructure\ExternalAPI\Sms\Enum\SignEnum;

/**
 *  haveshortmessage drivenreturnresultmustconvertforthisobject
 */
class SmsStruct
{
    /**
     * handmachinenumber.
     */
    public string $phone = '';

    /**
     * shortmessagetype,such as:registration_rewards (orderalreadyhair货),arrival_notice(to货notify).
     * 1.ifmatch language field,meanwhileusevariableshortmessage,canimplementmultiplelanguageadapt,byandlanguagetypefallbackbottom
     * 2.电quotient相closeshortmessageusethisfield,butisnothave language pass in.
     */
    public ?string $type = null;

    /**
     * variableshortmessagevariablecontent. maybeforassociatearray,alsomaybeforindexarray.
     * @example {"product_name": "quotient品A", "payer": "supplyquotientA","amount": 10}
     * @example ["quotient品A","supplyquotientA",10]
     */
    public ?array $variables = null;

    /**
     * normalshortpure messagetextcontent.
     * like: lighthousejustininvitationyouadd入enterprise,point击linkregisterorlogin https://xxxx.com/sso?r_ce=vB5932.
     */
    public ?string $content = null;

    /**
     * shortmessagesignature.
     * @example lighthouseengine
     */
    public SignEnum $sign;

    /**
     * shortmessage languagetype,andtypefieldandvariableshortmessage matchinguse.
     */
    public ?string $language = null;

    /**
     * shortmessagedefaultlanguagetype,supportbusinesssidecustomize. not传givedefaultvaluezh_CN.
     */
    public ?string $defaultLanguage = null;

    /**
     * shortmessagevariabletemplateid.
     */
    public ?string $templateId = null;

    public function __construct(string $phone, array $variables, SignEnum $sign, string $templateId)
    {
        $this->setPhone($phone);
        $this->setVariables($variables);
        $this->setSign($sign);
        $this->setTemplateId($templateId);
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getVariables(): ?array
    {
        return $this->variables;
    }

    public function setVariables(?array $variables): void
    {
        $this->variables = $variables;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getSign(): SignEnum
    {
        return $this->sign;
    }

    public function setSign(SignEnum $sign): void
    {
        $this->sign = $sign;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): void
    {
        $this->language = $language;
    }

    public function getDefaultLanguage(): ?string
    {
        return $this->defaultLanguage;
    }

    public function setDefaultLanguage(?string $defaultLanguage): void
    {
        $this->defaultLanguage = $defaultLanguage;
    }

    public function getTemplateId(): ?string
    {
        return $this->templateId;
    }

    public function setTemplateId(?string $templateId): void
    {
        $this->templateId = $templateId;
    }

    public function toArray(): array
    {
        return [
            'phone' => $this->getPhone(),
            'type' => $this->getType(),
            'variables' => $this->getVariables(),
            'language' => $this->getLanguage(),
        ];
    }
}
