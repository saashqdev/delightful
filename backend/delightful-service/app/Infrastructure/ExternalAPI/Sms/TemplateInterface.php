<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms;

use App\Infrastructure\ExternalAPI\Sms\Enum\LanguageEnum;

interface TemplateInterface
{
    /**
     * 根据传入的短信type和语种,尝试确定可能存在的模板id.
     */
    public function getTemplateIdByTypeAndLanguage(string $type, ?string $language): ?string;

    /**
     * 根据传入的短信type和语种,确定短信content. 可能会动态调整type对应的模板content.
     */
    public function getContentBySMSTypeAndLanguage(string $type, ?string $language): string;

    /**
     * 根据前传入的短信模板id,确定短信content.
     */
    public function getContentByTemplateId(string $templateId): string;

    /**
     * 解析模板variable,得到 variablekey与variablevalue 的array.
     */
    public function getTemplateVariables(string $content, array $messages): array;

    /**
     * 根据短信type,returntype支持的语种list.
     * @return string[]
     */
    public function getTemplateLanguagesByType(string $type): array;

    /**
     * 根据语种要求和短信支持的签名list,return对应的签名文本.
     */
    public function formatSign(string $sign, ?LanguageEnum $language, ?LanguageEnum $defaultLanguage = LanguageEnum::ZH_CN): string;
}
