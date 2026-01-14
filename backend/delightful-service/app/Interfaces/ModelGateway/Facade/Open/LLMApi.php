<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Interfaces\ModelGateway\Facade\Open;

use App\Application\ModelGateway\Service\LLMAppService;
use App\Domain\ModelGateway\Entity\Dto\CompletionDTO;
use App\Domain\ModelGateway\Entity\Dto\EmbeddingsDTO;
use App\Interfaces\ModelGateway\Assembler\LLMAssembler;
use BeDelightful\ApiResponse\Annotation\ApiResponse;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Odin\Api\Response\ChatCompletionResponse;
use Hyperf\Odin\Api\Response\ChatCompletionStreamResponse;
use Hyperf\Odin\Api\Response\EmbeddingResponse;

#[ApiResponse('low_code')]
class LLMApi extends AbstractOpenApi
{
    #[Inject]
    protected LLMAppService $llmAppService;

    public function chatCompletions(RequestInterface $request)
    {
        $requestData = $request->all();
        $sendMsgGPTDTO = new CompletionDTO($requestData);
        $sendMsgGPTDTO->setAccessToken($this->getAccessToken());
        $sendMsgGPTDTO->setIps($this->getClientIps());
        $response = $this->llmAppService->chatCompletion($sendMsgGPTDTO);
        if ($response instanceof ChatCompletionStreamResponse) {
            LLMAssembler::createStreamResponseByChatCompletionResponse($sendMsgGPTDTO, $response);
            return [];
        }
        if ($response instanceof ChatCompletionResponse) {
            return LLMAssembler::createResponseByChatCompletionResponse($response);
        }
        return null;
    }

    /**
     * processtextembeddingrequest.
     * willtextconvertfortoquantitytableshow.
     */
    public function embeddings(RequestInterface $request)
    {
        $requestData = $request->all();
        $embeddingDTO = new EmbeddingsDTO($requestData);
        $embeddingDTO->setAccessToken($this->getAccessToken());
        $embeddingDTO->setIps($this->getClientIps());
        $response = $this->llmAppService->embeddings($embeddingDTO);
        if ($response instanceof EmbeddingResponse) {
            return LLMAssembler::createEmbeddingsResponse($response);
        }
        return null;
    }
}
