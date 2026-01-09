<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

use App\Infrastructure\ExternalAPI\Sms\Enum\SignEnum;

/**
 * 所have短信驱动returnresultmustconvertforthisobject
 */
class SmsStruct
{
    /**
     * hand机number.
     */
    public string $phone = '';

    /**
     * 短信type,such as:registration_rewards (orderalreadyhair货),arrival_notice(to货notify).
     * 1.if搭配 language field,meanwhileusevariable短信,canimplement多language适配,byand语type兜bottom
     * 2.电quotient相close短信usethisfield,butisnothave language 传入.
     */
    public ?string $type = null;

    /**
     * variable短信variablecontent. maybeforassociatearray,alsomaybeforindexarray.
     * @example {"product_name": "quotient品A", "payer": "供应quotientA","amount": 10}
     * @example ["quotient品A","供应quotientA",10]
     */
    public ?array $variables = null;

    /**
     * 普通短信纯textcontent.
     * 如: 灯塔justin邀请你add入企业，point击linkregisterorlogin https://xxxx.com/sso?r_ce=vB5932.
     */
    public ?string $content = null;

    /**
     * 短信signature.
     * @example 灯塔engine
     */
    public SignEnum $sign;

    /**
     * 短信语type,andtypefieldandvariable短信搭配use.
     */
    public ?string $language = null;

    /**
     * 短信default语type,support业务方customize. not传givedefaultvaluezh_CN.
     */
    public ?string $defaultLanguage = null;

    /**
     * 短信variabletemplateid.
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
