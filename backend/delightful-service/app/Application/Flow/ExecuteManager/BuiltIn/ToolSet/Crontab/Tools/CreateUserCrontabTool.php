<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\Crontab\Tools;

use App\Application\Chat\Service\DelightfulUserContactAppService;
use App\Application\Chat\Service\DelightfulUserTaskAppService;
use App\Application\Flow\ExecuteManager\BuiltIn\BuiltInToolSet;
use App\Application\Flow\ExecuteManager\BuiltIn\ToolSet\AbstractBuiltInTool;
use App\Application\Flow\ExecuteManager\ExecutionData\ExecutionData;
use App\Domain\Contact\Entity\ValueObject\UserType;
use App\Domain\Flow\Entity\ValueObject\NodeInput;
use App\Infrastructure\Core\Collector\BuiltInToolSet\Annotation\BuiltInToolDefine;
use App\Interfaces\Authorization\Web\DelightfulUserAuthorization;
use App\Interfaces\Chat\DTO\UserTaskDTO;
use App\Interfaces\Chat\DTO\UserTaskValueDTO;
use Closure;
use DateTime;
use BeDelightful\FlowExprEngine\ComponentFactory;
use BeDelightful\FlowExprEngine\Structure\StructureType;

#[BuiltInToolDefine]
class CreateUserCrontabTool extends AbstractBuiltInTool
{
    public function getCallback(): ?Closure
    {
        return function (ExecutionData $executionData) {
            $params = $executionData->getTriggerData()->getParams();

            $dataIsolation = $executionData->getDataIsolation();

            $authorization = new DelightfulUserAuthorization();
            $delightfulUserContactAppService = di(DelightfulUserContactAppService::class);
            $user = $delightfulUserContactAppService->getByUserId($dataIsolation->getCurrentUserId());

            $authorization->setDelightfulEnvId($dataIsolation->getEnvId());
            $authorization->setId($dataIsolation->getCurrentUserId());
            $authorization->setOrganizationCode($user->getOrganizationCode());
            $authorization->setUserType(UserType::Human);

            $userTaskDTO = new UserTaskDTO($params);
            $creator = $authorization->getId();
            $userTaskDTO->setCreator($creator);
            $userTaskDTO->setDelightfulEnvId($authorization->getDelightfulEnvId());
            $userTaskDTO->setNickname($user->getNickname());

            $userTaskDTO->setConversationId($userTaskDTO->getConversationId());
            $userTaskDTO->setTopicId($userTaskDTO->getTopicId());
            $userTaskDTO->setAgentId($userTaskDTO->getAgentId());

            $userTaskValueDTO = new UserTaskValueDTO();
            $userTaskValueDTO->setInterval(0);
            $userTaskValueDTO->setUnit('');
            $userTaskValueDTO->setValues([]);
            $userTaskDTO->setValue($userTaskValueDTO->toArray());

            if ($userTaskDTO->getValue()['deadline']) {
                $userTaskValueDTO->setDeadline(new DateTime($userTaskDTO->getValue()['deadline']));
            }
            $delightfulUserTaskAppService = di(DelightfulUserTaskAppService::class);
            $crontab = $delightfulUserTaskAppService->createTask($userTaskDTO, $userTaskValueDTO);
            return [
                'crontab' => $crontab,
                'message' => 'scheduletaskcreatesuccess',
            ];
        };
    }

    public function getToolSetCode(): string
    {
        return BuiltInToolSet::Crontab->getCode();
    }

    public function getName(): string
    {
        return 'create_user_crontab';
    }

    public function getDescription(): string
    {
        return 'createonenewuserlevelotherscheduletask';
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
        "agent_id": {
            "type": "string",
            "key": "agent_id",
            "title": "assistantID",
            "description": "assistantID, ifforempty,thenusecurrentassistantD",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "topic_id": {
            "type": "string",
            "key": "topic_id",
            "title": "topicID",
            "description": "topicID, ifforempty,thenusecurrenttopicID",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "conversation_id": {
            "type": "string",
            "key": "conversation_id",
            "title": "sessionID",
            "description": "sessionID, ifforempty,thenusecurrentsessionID",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "name": {
            "type": "string",
            "key": "name",
            "title": "taskname",
            "description": "taskname",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "description": {
            "type": "string",
            "key": "description",
            "title": "taskdescription",
            "description": "taskdescription",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "day": {
            "type": "string",
            "key": "day",
            "title": "executedate",
            "description": "executedate,format:YYYY-MM-DD",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "time": {
            "type": "string",
            "key": "time",
            "title": "executetime",
            "description": "executetime,format:HH:mm",
            "required": null,
            "value": null,
            "encryption": false,
            "encryption_value": null,
            "items": null,
            "properties": null
        },
        "type": {
            "type": "string",
            "key": "type",
            "title": "duplicateperiod",
            "description": "duplicateperiod,no_repeat notduplicate,daily_repeat eachdayparameter,weekly_repeat eachweekduplicate,monthly_repeat eachmonthduplicate,annually_repeat eachyearduplicate,weekday_repeat eachworkdayduplicate,custom_repeat customizeduplicate",
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
       "title": "failretrycount",
       "description": "failretrycount",
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
