<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Qwen;

use App\ErrorCode\ImageGenerateErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Codec\Json;

class QwenImageAPI
{
    private const BASE_URL = 'https://dashscope.aliyuncs.com/api/v1';

    private const TASK_CREATE_ENDPOINT = '/services/aigc/text2image/image-synthesis';

    private const EDIT_TASK_CREATE_ENDPOINT = '/services/aigc/multimodal-generation/generation';

    private const TASK_QUERY_ENDPOINT = '/tasks/%s';

    private string $apiKey;

    private Client $client;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'timeout' => 120,
            'verify' => false,
        ]);
    }

    /**
     * submittext generationgraphtask
     */
    public function submitTask(array $params): array
    {
        $url = self::BASE_URL . self::TASK_CREATE_ENDPOINT;

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'X-DashScope-Async' => 'enable',
        ];

        $body = [
            'model' => $params['model'],
            'input' => [
                'prompt' => $params['prompt'],
            ],
            'parameters' => [],
        ];

        // settingoptionalparameter
        if (isset($params['size'])) {
            $body['parameters']['size'] = $params['size'];
        }

        if (isset($params['n'])) {
            $body['parameters']['n'] = $params['n'];
        }

        if (isset($params['prompt_extend'])) {
            $body['parameters']['prompt_extend'] = $params['prompt_extend'];
        }

        try {
            $response = $this->client->post($url, [
                'headers' => $headers,
                'json' => $body,
            ]);

            $responseBody = $response->getBody()->getContents();
            return Json::decode($responseBody, true);
        } catch (GuzzleException $e) {
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        } catch (Exception $e) {
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    /**
     * submitgraphlikeedittask - samecall.
     */
    public function submitEditTask(array $params): array
    {
        $url = self::BASE_URL . self::EDIT_TASK_CREATE_ENDPOINT;

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];

        // buildconform阿withincloudAPIdocumentrequestformat
        $body = [
            'model' => $params['model'] ?? 'qwen-image-edit',
            'input' => [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [],
                    ],
                ],
            ],
            'parameters' => [
                'negative_prompt' => '',
                'watermark' => false,
            ],
        ];

        // addgraphliketocontentmiddle(onlygettheonesheetimage)
        if (isset($params['image_urls']) && ! empty($params['image_urls'])) {
            $body['input']['messages'][0]['content'][] = [
                'image' => $params['image_urls'][0],
            ];
        }

        // addtextprompt
        if (isset($params['prompt']) && ! empty($params['prompt'])) {
            $body['input']['messages'][0]['content'][] = [
                'text' => $params['prompt'],
            ];
        }

        try {
            $response = $this->client->post($url, [
                'headers' => $headers,
                'json' => $body,
            ]);

            $responseBody = $response->getBody()->getContents();
            return Json::decode($responseBody, true);
        } catch (GuzzleException $e) {
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        } catch (Exception $e) {
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    /**
     * querytaskresult.
     */
    public function getTaskResult(string $taskId): array
    {
        $url = self::BASE_URL . sprintf(self::TASK_QUERY_ENDPOINT, $taskId);

        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        try {
            $response = $this->client->get($url, [
                'headers' => $headers,
            ]);

            $responseBody = $response->getBody()->getContents();
            return Json::decode($responseBody, true);
        } catch (GuzzleException $e) {
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        } catch (Exception $e) {
            ExceptionBuilder::throw(ImageGenerateErrorCode::GENERAL_ERROR, $e->getMessage());
        }
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }
}
