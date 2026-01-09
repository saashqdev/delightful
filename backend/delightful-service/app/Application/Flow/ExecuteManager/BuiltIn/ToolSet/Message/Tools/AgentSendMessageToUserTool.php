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
            // 助handsendmessage
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
                'message' => 'sendmessagesuccess',
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
        return 'sendmessagegiveperson';
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
            "title": "receivepersonuserid",
            "description": "receivepersonuserid",
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
     "title": "customizeduplicateparameter",
     "description": "customizeduplicateparameter",
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
       "title": "unit",
       "description": "unit ,day day,week week,month month,year year",
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
       "title": "deadlinedate",
       "description": "deadlinedate,format:YYYY-MM-DD",
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
       "title": "failedretrycount",
       "description": "failedretrycount",
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
       "title": "duplicatevalue",
       "description": "duplicatevalue",
       "required": null,
       "value": null,
       "encryption": false,
       "encryption_value": null,
       "items": null,
       "properties": null
   }
     }

     }*/
