<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

use App\Infrastructure\ExternalAPI\Sms\Enum\SignEnum;

/**
 * 所有短信驱动的returnresult必须转换为此object
 */
class SmsStruct
{
    /**
     * 手机号.
     */
    public string $phone = '';

    /**
     * 短信的type,such as:registration_rewards (订单已发货),arrival_notice(到货notify).
     * 1.如果搭配 language field,同时usevariable短信,可以implement多语言适配,以及语种兜底
     * 2.电商的相关短信use此field,但是没有 language 传入.
     */
    public ?string $type = null;

    /**
     * variable短信的variablecontent. 可能为关联array,也可能为索引array.
     * @example {"product_name": "商品A", "payer": "供应商A","amount": 10}
     * @example ["商品A","供应商A",10]
     */
    public ?array $variables = null;

    /**
     * 普通短信的纯文本content.
     * 如: 灯塔正在邀请你加入企业，点击链接注册或登录 https://xxxx.com/sso?r_ce=vB5932.
     */
    public ?string $content = null;

    /**
     * 短信签名.
     * @example 灯塔引擎
     */
    public SignEnum $sign;

    /**
     * 短信的语种,与typefield和variable短信搭配use.
     */
    public ?string $language = null;

    /**
     * 短信的默认语种,支持业务方自定义. 不传给默认valuezh_CN.
     */
    public ?string $defaultLanguage = null;

    /**
     * 短信variable的模板id.
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
