<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms\Volcengine;

use App\Infrastructure\ExternalAPI\Sms\AbstractTemplate;
use App\Infrastructure\ExternalAPI\Sms\Enum\LanguageEnum;
use App\Infrastructure\ExternalAPI\Sms\Enum\SignEnum;
use App\Infrastructure\ExternalAPI\Sms\Enum\SmsTypeEnum;
use App\Infrastructure\ExternalAPI\Sms\Volcengine\Base\VolcengineTemplateIdEnum;
use RuntimeException;

class Template extends AbstractTemplate
{
    /**
     * defaultmessage组ID.
     */
    public const string DEFAULT_MESSAGE_GROUP_ID = '77a48cb1';

    /**
     * message组支持的signaturelist.
     */
    public static array $signToMessageGroup = ['灯塔引擎'];

    protected array $typeToIdMap = [
        LanguageEnum::ZH_CN->value => [
            SmsTypeEnum::VERIFICATION_WITH_EXPIRATION->value => VolcengineTemplateIdEnum::ST_79E262F3->value,
        ],
    ];

    protected array $idContents = [
        VolcengineTemplateIdEnum::ST_79E262F3->value => '您的verify码是：${verification_code}，valid期 ${timeout} 分钟。请在page中inputverify码completeverify。如非本人操作，请ignore。',
    ];

    /**
     * 短messagetemplateId与message组的mapping.
     */
    protected array $templateToGroupIdMap = [
        self::DEFAULT_MESSAGE_GROUP_ID => [
            VolcengineTemplateIdEnum::ST_79E25915->value,
        ],
    ];

    /**
     * 火山云短信的signature暂未支持国际化.
     */
    protected array $signMap = [
        '灯塔引擎' => [
            LanguageEnum::ZH_CN->value => '灯塔引擎',
            //            Language::EN_US => 'Light Engine',
        ],
        SignEnum::DENG_TA->value => [
            LanguageEnum::ZH_CN->value => '灯塔引擎',
            //            Language::EN_US => 'Light Engine',
        ],
    ];

    public function getMessageGroupId(string $templateId): string
    {
        foreach ($this->templateToGroupIdMap as $groupId => $templateIds) {
            if (in_array($templateId, $templateIds, true)) {
                return $groupId;
            }
        }
        return self::DEFAULT_MESSAGE_GROUP_ID;
    }

    /**
     * according to传来的短信文本,parsevariable. 只有variable的value,未匹配variable的key!
     * needvariableparse的原因:火山短信只支持variable短信的send,而业务方will出于创蓝短信的原因,will传来整个短信文本content,没有variable.
     */
    public function smsVariableAnalyse(string $message, string $templateId, ?string $language): array
    {
        // 找到指定的templatevariable正则parse规则. 如果没传模版id,循环正则匹配will降低匹配速度和准确度
        if ($templateId) {
            // 判断template是否存在
            if (! isset($this->idContents[$templateId])) {
                throw new RuntimeException('未匹配到templateid:' . $templateId);
            }
            $pregMatch = $this->variablePregAnalyse[$language][$templateId] ?? '';
            // 如果according to短信content匹配到了templateid,就变更传入的templateid的value
            $pregMatch && [$templateId, $matchedVariables] = $this->variablePregMatch([$templateId => $pregMatch], $message);
        } elseif (isset($this->variablePregAnalyse[$language])) {
            // 火山普通短信,且无法according totype + language 确定templateid,尝试according to短信文本content + language 确定templateid和variable
            [$templateId, $matchedVariables] = $this->variablePregMatch($this->variablePregAnalyse[$language], $message);
        }
        if (empty($templateId)) {
            throw new RuntimeException('未匹配到templateid');
        }
        if (empty($matchedVariables)) {
            throw new RuntimeException('短信的templatevariableparsefail');
        }
        return [$templateId, $matchedVariables];
    }

    protected function getTemplateDefaultSignType(string $sign): string
    {
        return array_key_first(self::$signToMessageGroup) ?? '';
    }

    /**
     * @param array $pregVariableAnalyse ['templateid_xxx'=>'正则table达式']
     */
    private function variablePregMatch(array $pregVariableAnalyse, string $message): array
    {
        $matchedVariables = [];
        $matches = [];
        $templateId = null;
        foreach ($pregVariableAnalyse as $templateId => $pregTemplate) {
            if (preg_match($pregTemplate, $message, $matches)) {
                $matchedVariables = array_slice($matches, 1);
                break;
            }
        }
        return [$templateId, $matchedVariables];
    }
}
