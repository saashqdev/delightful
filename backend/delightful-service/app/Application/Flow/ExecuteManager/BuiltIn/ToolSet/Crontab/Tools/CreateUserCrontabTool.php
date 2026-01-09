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
use Delightful\FlowExprEngine\ComponentFactory;
use Delightful\FlowExprEngine\Structure\StructureType;

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
                'message' => '定时taskcreatesuccess',
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
        return 'create一个newuser级别定时task';
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
            "title": "助理ID",
            "description": "助理ID, 如果为空，则usecurrent助理D",
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
            "title": "话题ID",
            "description": "话题ID, 如果为空，则usecurrent话题ID",
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
            "description": "sessionID, 如果为空，则usecurrentsessionID",
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
            "description": "task的name",
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
            "description": "task的description",
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
            "title": "执行日期",
            "description": "执行日期，格式：YYYY-MM-DD",
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
            "title": "执行time",
            "description": "执行time，格式：HH:mm",
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
            "title": "重复周期",
            "description": "重复周期，no_repeat 不重复，daily_repeat 每天parameter，weekly_repeat 每周重复，monthly_repeat 每月重复，annually_repeat 每年重复，weekday_repeat 每个工作日重复，custom_repeat customize重复",
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
     "title": "customize重复parameter",
     "description": "customize重复parameter",
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
       "title": "fail重试次数",
       "description": "fail重试次数",
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
       "title": "重复value",
       "description": "重复value",
       "required": null,
       "value": null,
       "encryption": false,
       "encryption_value": null,
       "items": null,
       "properties": null
   }
     }

     }*/
