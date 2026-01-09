<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Official;

use App\Infrastructure\ExternalAPI\ImageGenerateAPI\AbstractImageGenerate;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\ImageGenerateRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Request\OfficialProxyRequest;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\ImageGenerateResponse;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Response\OpenAIFormatResponse;
use App\Infrastructure\Util\DelightfulUriTool;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Hyperf\Codec\Json;
use Throwable;

/**
 * officialproxymodel.
 */
class OfficialProxyModel extends AbstractImageGenerate
{
    protected string $url;

    protected string $apiKey;

    protected Client $httpClient;

    public function __construct(array $config)
    {
        $this->url = rtrim($config['url'] ?? '', '/');
        $this->apiKey = $config['api_key'] ?? '';

        $this->httpClient = new Client([
            'timeout' => $config['timeout'] ?? 300,
        ]);
    }

    public function generateImageOpenAIFormat(ImageGenerateRequest $imageGenerateRequest): OpenAIFormatResponse
    {
        $uri = DelightfulUriTool::getImagesGenerationsUri();

        $fullUrl = $this->url . $uri;
        try {
            /** @var OfficialProxyRequest $imageGenerateRequest */
            $officialProxyRequest = $imageGenerateRequest;
            $data = $officialProxyRequest->toArray();

            $this->logger->info('officialproxy:sendimagegeneraterequest', [
                'url' => $fullUrl,
                'data' => $data,
            ]);

            $response = $this->httpClient->post($fullUrl, [
                RequestOptions::HEADERS => [
                    'api-key' => $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                RequestOptions::JSON => $data,
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();

            $this->logger->info('officialproxy:收toresponse', [
                'status_code' => $statusCode,
                'response_length' => strlen($responseBody),
            ]);

            $responseData = Json::decode($responseBody);

            $this->logger->info('officialproxy:requestsuccess', [
                'url' => $this->url,
                'data_count' => count($responseData['data'] ?? []),
            ]);

            // build OpenAI formatresponse
            return new OpenAIFormatResponse($responseData);
        } catch (GuzzleException $e) {
            $errorBody = '';
            // trygetresponsebody
            try {
                if ($e instanceof RequestException && $e->hasResponse()) {
                    $errorBody = $e->getResponse()->getBody()->getContents();
                    $errorBody = json_decode($errorBody, true);
                }
            } catch (Throwable $bodyException) {
                $errorBody = 'Failed to read response body: ' . $bodyException->getMessage();
            }

            $this->logger->error('officialproxy:requestfailed', [
                'url' => $fullUrl,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'response_body' => $errorBody,
            ]);

            // returnerrorresponse
            return OpenAIFormatResponse::buildError(
                code: is_array($errorBody) ? $errorBody['error']['code'] : 4001,
                message: is_array($errorBody) ? $errorBody['error']['message'] : $errorBody,
            );
        } catch (Throwable $e) {
            $this->logger->error('officialproxy:unknownerror', [
                'url' => $fullUrl,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return OpenAIFormatResponse::buildError(
                code: $e->getCode() ?: 4001,
                message: 'request error'
            );
        }
    }

    public function generateImageRawWithWatermark(ImageGenerateRequest $imageGenerateRequest): array
    {
        $response = $this->generateImageOpenAIFormat($imageGenerateRequest);
        return $response->toArray();
    }

    public function getProviderName(): string
    {
        return 'official';
    }

    public function generateImageRaw(ImageGenerateRequest $imageGenerateRequest): array
    {
        $response = $this->generateImageOpenAIFormat($imageGenerateRequest);
        return $response->toArray();
    }

    public function setAK(string $ak)
    {
        // TODO: Implement setAK() method.
    }

    public function setSK(string $sk)
    {
        // TODO: Implement setSK() method.
    }

    public function setApiKey(string $apiKey)
    {
        // TODO: Implement setApiKey() method.
    }

    protected function generateImageInternal(ImageGenerateRequest $imageGenerateRequest): ImageGenerateResponse
    {
        // officialproxynotusethismethod,directlyuse generateImageOpenAIFormat
        throw new Exception('OfficialProxyModel does not support generateImageInternal method');
    }
}
