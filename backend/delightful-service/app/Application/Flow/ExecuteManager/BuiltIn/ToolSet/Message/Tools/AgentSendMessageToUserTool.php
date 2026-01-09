<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\Message\Tools;

use App\Application\Chat\Service\DelightfulChatMessageAppService;
use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInTool;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Chat\DTO\Message\ChatMessage\TextMessage;
use App\Domain\Chat\Entity\DelightfulSeqEntity;
use App\Domain\Chat\Entity\ValueObject\ConversationType;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use App\Infrastructure\Util\IdGenerator\IdGenerator;
use Closure;
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

#[BuiltInToolDefine]
class AgentSendMessageToUserTool extends AbstractBuiltInTool
{
    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            $params = $executionData->getTriggerData()->getParams();
            // $delightfulAgentAppService = di(DelightfulAgentAppService::class);
            $senderUserId = $executionData->getAgentUserId();
            // 助手发送message
            $assistantMessage = new TextMessage(['content' => $params['content']]);
            $appMessageId = IdGenerator::getUniqueId32();
            $receiveSeqDTO = new DelightfulSeqEntity();
            $receiveSeqDTO->setContent($assistantMessage);
            $receiveSeqDTO->setSeqType($assistantMessage->getMessageTypeEnum());
            $receiverIds = $params['receiver_user_ids'];

            $receiverType = ConversationType::User;

            foreach ($receiverIds as $receiverId) {
                di(DelightfulChatMessageAppService::class)->agentSendMessage(
                    aiSeqDTO: $receiveSeqDTO,
                    senderUserId: $senderUserId,
                    receiverId: $receiverId,
                    appMessageId: $appMessageId,
                    receiverType: $receiverType
                );
            }
            return [
                'message' => '发送messagesuccess',
            ];
        };
    }

    public function getToolSetCode(): string
    {
        return BuiltInToolSet::Message->getCode();
    }

    public function getName(): string
    {
        return 'agent_send_message_to_user';
    }

    public function getDescription(): string
    {
        return '发送message给个人';
    }

    public function getInput(): ?NodeInput
    {
        $input = new NodeInput();
        $input->setForm(ComponentFactory::generateTemplate(StructureType::Form, json_decode(
            <<<'JSON'
{
    "type": "object",
    "key": "root",
    "sort": 0,
    "title": "root",
    "description": "",
    "items": null,
    "value": null,
    "required": [
        "name",
        "day",
        "time",
        "type",
        "value"
    ],
    "properties": {
        "receiver_user_ids": {
            "type": "array",
            "key": "receiver_user_ids",
            "title": "接收人的userid",
            "description": "接收人的userid",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items":  {
                "type": "string"
            },
            "properties": null
        },
        "content": {
            "type": "string",
            "key": "content",
            "title": "messagecontent",
            "description": "messagecontent",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        }
        }
  
}
JSON,
            true
        )));
        return $input;
    }
}

/* "value": {
     "type": "object",
     "key": "value",
     "title": "自定义重复parameter",
     "description": "自定义重复parameter",
     "required": [
   "unit",
   "deadline",
   "interval",
   "values"
     ],
     "value": null,
     "encryption": false,
     "encryption_value": null,
     "properties": {
   "unit": {
       "type": "string",
       "key": "unit",
       "title": "单位",
       "description": "单位 ，day 天，week 周，month 月，year 年",
       "required": null,
       "value": null,
       "encryption": false,
       "encryption_value": null,
       "items": null,
       "properties": null
   },
   "deadline": {
       "type": "string",
       "key": "deadline",
       "title": "截止日期",
       "description": "截止日期，格式：YYYY-MM-DD",
       "required": null,
       "value": null,
       "encryption": false,
       "encryption_value": null,
       "items": null,
       "properties": null
   },
   "interval": {
       "type": "number",
       "key": "interval",
       "title": "failed重试次数",
       "description": "failed重试次数",
       "required": null,
       "value": null,
       "encryption": false,
       "encryption_valugit e": null,
       "items": null,
       "properties": null
   },
   "values": {
       "type": "array",
       "key": "values",
       "title": "重复值",
       "description": "重复值",
       "required": null,
       "value": null,
       "encryption": false,
       "encryption_value": null,
       "items": null,
       "properties": null
   }
     }

     }*/
