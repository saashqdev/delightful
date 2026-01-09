<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Core\Hyperf\Odin\Model;

use App\Infrastructure\Core\Hyperf\Odin\Api\Providers\Misc\Misc;
use App\Infrastructure\Core\Hyperf\Odin\Api\Request\MiscEmbeddingRequest;
use Hyperf\Odin\Api\Providers\OpenAI\Client;
use Hyperf\Odin\Api\Providers\OpenAI\OpenAIConfig;
use Hyperf\Odin\Api\RequestOptions\ApiOptions;
use Hyperf\Odin\Api\Response\EmbeddingResponse;
use Hyperf\Odin\Contract\Api\ClientInterface;
use Hyperf\Odin\Exception\LLMException\Configuration\LLMInvalidApiKeyException;
use Hyperf\Odin\Exception\LLMException\Configuration\LLMInvalidEndpointException;
use Hyperf\Odin\Exception\LLMException\Model\LLMEmbeddingNotSupportedException;
use Hyperf\Odin\Model\OpenAIModel;
use Psr\Log\LoggerInterface;

class MiscEmbeddingModel extends OpenAIModel
{
    /**
     * @throws LLMEmbeddingNotSupportedException
     * @throws LLMInvalidApiKeyException
     * @throws LLMInvalidEndpointException
     */
    public function embeddings(array|string $input, ?string $encoding_format = 'float', ?string $user = null, array $businessParams = []): EmbeddingResponse
    {
        // checkmodelwhethersupport嵌入feature
        $this->checkEmbeddingSupport();

        $client = $this->getClient();
        $embeddingRequest = new MiscEmbeddingRequest(
            input: $input,
            model: $this->model
        );

        return $client->embeddings($embeddingRequest);
    }

    /**
     * @throws LLMInvalidApiKeyException
     * @throws LLMInvalidEndpointException
     */
    protected function getClient(): ClientInterface
    {
        // processAPI基础URL,ensurecontaincorrectversionpath
        $config = $this->config;
        $this->processApiBaseUrl($config);

        // useClientFactorycreateOpenAIcustomer端
        return $this->createClient(
            $config,
            $this->getApiRequestOptions(),
            $this->logger
        );
    }

    /**
     * getAPIversionpath.
     * OpenAIAPIversionpathfor v1.
     */
    protected function getApiVersionPath(): string
    {
        return 'misc/v1';
    }

    /**
     * @throws LLMInvalidApiKeyException
     * @throws LLMInvalidEndpointException
     */
    private function createClient(array $config, ApiOptions $apiOptions, LoggerInterface $logger): Client
    {
        // verify必wantconfigurationparameter
        $apiKey = $config['api_key'] ?? '';
        $baseUrl = $config['base_url'] ?? '';

        // createconfigurationobject
        $clientConfig = new OpenAIConfig(
            apiKey: $apiKey,
            organization: '',
            baseUrl: $baseUrl
        );

        // createAPIinstance
        return (new Misc())->getClient($clientConfig, $apiOptions, $logger);
    }
}
