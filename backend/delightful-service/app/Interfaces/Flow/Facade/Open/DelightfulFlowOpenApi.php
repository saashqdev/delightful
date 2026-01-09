<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\Flow\Facade\Open;

use App\Application\Flow\Service\DelightfulFlowExecuteAppService;
use App\Interfaces\Flow\Assembler\DelightfulFlowExecuteLogAssembler;
use App\Interfaces\Flow\DTO\DelightfulFlowApiChatDTO;
use Delightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;

#[ApiResponse(version: 'low_code')]
class DelightfulFlowOpenApi extends AbstractOpenApi
{
    #[Inject]
    protected DelightfulFlowExecuteAppService $delightfulFlowExecuteAppServiceService;

    #[Inject]
    protected DelightfulFlowExecuteLogAssembler $delightfulFlowExecuteLogAssembler;

    public function chat()
    {
        $apiChatDTO = $this->createApiChatDTO();
        $apiChatDTO->setAsync(false);
        return $this->delightfulFlowExecuteAppServiceService->apiChat($apiChatDTO);
    }

    public function chatWithId(string $botId)
    {
        return $this->chat();
    }

    public function chatAsync()
    {
        $apiChatDTO = $this->createApiChatDTO();
        $apiChatDTO->setAsync(true);
        return $this->delightfulFlowExecuteAppServiceService->apiChat($apiChatDTO);
    }

    public function chatCompletions()
    {
        $apiChatDTO = $this->createApiChatDTO();
        $apiChatDTO->setAsync(false);
        $apiChatDTO->setVersion('v1');
        return $this->delightfulFlowExecuteAppServiceService->apiChat($apiChatDTO);
    }

    public function paramCall()
    {
        $apiChatDTO = $this->createApiChatDTO();
        $apiChatDTO->setStream(false);
        return $this->delightfulFlowExecuteAppServiceService->apiParamCall($apiChatDTO);
    }

    public function paramCallWithId(string $code)
    {
        return $this->paramCall();
    }

    public function paramCallAsync()
    {
        $apiChatDTO = $this->createApiChatDTO();
        $apiChatDTO->setAsync(true);
        $apiChatDTO->setStream(false);
        return $this->delightfulFlowExecuteAppServiceService->apiParamCall($apiChatDTO);
    }

    public function getExecuteResult(string $taskId)
    {
        $apiChatDTO = $this->createApiChatDTO();
        $apiChatDTO->setTaskId($taskId);
        $log = $this->delightfulFlowExecuteAppServiceService->getByExecuteId($apiChatDTO);
        return $this->delightfulFlowExecuteLogAssembler->createExecuteResultDTO($log);
    }

    private function createApiChatDTO(): DelightfulFlowApiChatDTO
    {
        $apiChatDTO = new DelightfulFlowApiChatDTO($this->request->all());

        $apiChatDTO->setApiKey($this->request->header('api-key', ''));
        $apiChatDTO->setAuthorization($this->request->header('authorization', ''));

        // if environment_code 存in与 header 中
        if (empty($apiChatDTO->getEnvironmentCode())
            && ($this->request->hasHeader('environment-code') || $this->request->hasHeader('environment_code') || $this->request->hasHeader('teamshare_environment_code'))) {
            $apiChatDTO->setEnvironmentCode(
                $this->request->header('environment-code', $this->request->header('environment_code', $this->request->header('teamshare_environment_code')))
            );
        }

        // compatible openai 的 messages 入参
        $params = $this->request->all();
        if (isset($params['messages'])) {
            foreach ($params['messages'] as $messageArr) {
                if (($messageArr['role'] ?? '') === 'user') {
                    $message = $messageArr['content'];
                    $apiChatDTO->setMessage($message);
                    break;
                }
            }
        }

        // process instruction parameter
        if (isset($params['instruction']) && is_array($params['instruction'])) {
            $apiChatDTO->setInstruction($params['instruction']);
        }

        return $apiChatDTO;
    }
}
