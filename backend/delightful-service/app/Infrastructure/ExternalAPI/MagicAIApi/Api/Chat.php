<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\DelightfulAIApi\Api;

use App\Infrastructure\ExternalAPI\DelightfulAIApi\Api\Request\Chat\CompletionsRequest;
use App\Infrastructure\ExternalAPI\DelightfulAIApi\Api\Response\Chat\CompletionsResponse;
use App\Infrastructure\ExternalAPI\DelightfulAIApi\Kernel\AbstractApi;
use GuzzleHttp\RequestOptions;

class Chat extends AbstractApi
{
    public function completions(CompletionsRequest $request): CompletionsResponse
    {
        $options = [
            RequestOptions::JSON => $request->toBody(),
        ];
        $response = $this->post('/api/v2/magic/llm/chatCompletions', $options);
        $data = $this->getResponseData($response, true);

        return new CompletionsResponse($data);
    }
}
