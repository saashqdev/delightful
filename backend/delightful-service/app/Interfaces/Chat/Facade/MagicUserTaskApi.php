<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Chat\Facade;

use App\Application\Chat\Service\DelightfulUserTaskAppService;
use App\ErrorCode\UserTaskErrorCode;
use App\Infrastructure\Core\Exception\BusinessException;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use App\Interfaces\Chat\DTO\UserTaskDTO;
use App\Interfaces\Chat\DTO\UserTaskValueDTO;
use DateTime;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Delightful\TaskScheduler\Entity\ValueObject\TaskType;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Throwable;

#[ApiResponse('low_code')]
class DelightfulUserTaskApi extends AbstractApi
{
    public function __construct(
        private DelightfulUserTaskAppService $delightfulUserTaskAppService,
        private ValidatorFactoryInterface $validatorFactory,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function createTask(RequestInterface $request)
    {
        $request = $request->all();
        $rules = [
            'agent_id' => 'required|string',
            'topic_id' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
            'day' => 'string',
            'time' => 'string',
            'value' => 'required|array',
            'conversation_id' => 'required|string',
            'user_id' => 'string',
        ];

        $authorization = $this->getAuthorization();

        try {
            $params = $this->checkParams($request, $rules);

            $userTaskDTO = new UserTaskDTO($params);
            $creator = $authorization->getId();
            $userTaskDTO->setCreator($creator);
            $userTaskDTO->setDelightfulEnvId($authorization->getDelightfulEnvId());
            $userTaskDTO->setNickname($authorization->getNickname());
            $userTaskDTO->setConversationId($params['conversation_id']);
            $userTaskDTO->setTopicId($params['topic_id']);
            $userTaskValueDTO = new UserTaskValueDTO();
            $month = empty($userTaskDTO->getValue()['month']) ? '' : (string) $userTaskDTO->getValue()['month'];
            $values = empty($userTaskDTO->getValue()['values']) ? [] : $userTaskDTO->getValue()['values'];
            $interval = empty($userTaskDTO->getValue()['interval']) ? 0 : $userTaskDTO->getValue()['interval'];
            $unit = empty($userTaskDTO->getValue()['unit']) ? '' : $userTaskDTO->getValue()['unit'];
            $userTaskValueDTO->setInterval($interval);
            $userTaskValueDTO->setUnit($unit);
            $userTaskValueDTO->setValues($values);
            $userTaskValueDTO->setMonth($month);

            // 如果type 等于自定义重复，那么需要判断time 是否存在值
            if ($userTaskDTO->getType() === TaskType::CustomRepeat->value) {
                if (empty($userTaskDTO->getTime())) {
                    ExceptionBuilder::throw(UserTaskErrorCode::PARAMETER_INVALID, 'time is  required for custom repeat');
                }
            }

            // 将deadline转换为DateTime对象
            if ($userTaskDTO->getValue()['deadline']) {
                $userTaskValueDTO->setDeadline(new DateTime($userTaskDTO->getValue()['deadline']));
            }

            $this->delightfulUserTaskAppService->createTask($userTaskDTO, $userTaskValueDTO);
        } catch (Throwable $exception) {
            ExceptionBuilder::throw(UserTaskErrorCode::TASK_CREATE_FAILED, $exception->getMessage());
        }

        return true;
    }

    public function getTask(int $id)
    {
        return $this->delightfulUserTaskAppService->getTask($id);
    }

    public function updateTask(RequestInterface $request, int $id)
    {
        $request = $request->all();
        $rules = [
            'agent_id' => 'required|string',
            'topic_id' => 'required|string',
            'name' => 'required|string',
            'type' => 'required|string',
            'day' => 'string',
            'time' => 'string',
            'value' => 'required|array',
            'conversation_id' => 'string',
            'user_id' => 'string',
        ];

        $authorization = $this->getAuthorization();
        try {
            $params = $this->checkParams($request, $rules);
            $userTaskDTO = new UserTaskDTO($params);
            $authorization = $this->getAuthorization();
            $creator = $authorization->getId();
            $userTaskDTO->setCreator($creator);
            $userTaskDTO->setDelightfulEnvId($authorization->getDelightfulEnvId());
            $userTaskDTO->setConversationId($params['conversation_id']);
            $userTaskDTO->setTopicId($params['topic_id']);

            // 如果type 等于自定义重复，那么需要判断time 是否存在值, If condition is always false.
            if ($userTaskDTO->getType() === TaskType::CustomRepeat->value) {
                if (empty($userTaskDTO->getTime())) {
                    ExceptionBuilder::throw(UserTaskErrorCode::PARAMETER_INVALID, 'time is  required for custom repeat');
                }
            }

            $userTaskValueDTO = new UserTaskValueDTO();
            $interval = empty($userTaskDTO->getValue()['interval']) ? 0 : $userTaskDTO->getValue()['interval'];
            $unit = empty($userTaskDTO->getValue()['unit']) ? '' : $userTaskDTO->getValue()['unit'];
            $values = empty($userTaskDTO->getValue()['values']) ? [] : $userTaskDTO->getValue()['values'];
            $month = empty($userTaskDTO->getValue()['month']) ? '' : (string) $userTaskDTO->getValue()['month'];
            $userTaskValueDTO->setInterval($interval);
            $userTaskValueDTO->setUnit($unit);
            $userTaskValueDTO->setValues($values);
            $userTaskValueDTO->setMonth($month);

            // 将deadline转换为DateTime对象
            if ($userTaskDTO->getValue()['deadline']) {
                $userTaskValueDTO->setDeadline(new DateTime($userTaskDTO->getValue()['deadline']));
            }

            $this->delightfulUserTaskAppService->updateTask($id, $userTaskDTO, $userTaskValueDTO);
        } catch (Throwable $exception) {
            ExceptionBuilder::throw(UserTaskErrorCode::TASK_UPDATE_FAILED, $exception->getMessage());
        }

        return true;
    }

    public function deleteTask(int $id)
    {
        $this->delightfulUserTaskAppService->deleteTask($id);
    }

    public function listTask(RequestInterface $request)
    {
        $params = $request->all();
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 100;

        // 校验agentId
        $agentId = $params['agent_id'] ?? '';
        $topicId = $params['topic_id'] ?? '';
        if (! $agentId) {
            ExceptionBuilder::throw(UserTaskErrorCode::AGENT_ID_REQUIRED);
        }

        // if (! $topicId) {
        //     ExceptionBuilder::throw(UserTaskErrorCode::TOPIC_ID_REQUIRED);
        // }

        try {
            $authorization = $this->getAuthorization();
            $creator = $authorization->getId();
            $queryId = $this->delightfulUserTaskAppService->getQueryId($agentId, $topicId);
            return $this->delightfulUserTaskAppService->listTaskByCreator($page, $pageSize, $creator, $queryId);
        } catch (Throwable $exception) {
            ExceptionBuilder::throw(UserTaskErrorCode::TASK_LIST_FAILED, throwable: $exception);
        }
    }

    private function checkParams(array $params, array $rules): array
    {
        $validator = $this->validatorFactory->make($params, $rules);
        if ($validator->fails()) {
            throw new BusinessException(json_encode($validator->errors()));
        }
        $validator->validated();
        return $params;
    }
}
