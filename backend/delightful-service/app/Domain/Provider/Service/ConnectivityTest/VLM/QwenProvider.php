<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ConnectivityTest\VLM;

use App\Domain\Provider\DTO\Item\ProviderConfigItem;
use App\Domain\Provider\Service\ConnectivityTest\ConnectResponse;
use App\Domain\Provider\Service\ConnectivityTest\IProvider;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Qwen\QwenImageAPI;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Hyperf\Codec\Json;
use Psr\Log\LoggerInterface;

use function Hyperf\Translation\__;

/**
 * 通义千问服务商.
 */
class QwenProvider implements IProvider
{
    public function __construct()
    {
    }

    public function connectivityTestByModel(ProviderConfigItem $serviceProviderConfig, string $modelVersion): ConnectResponse
    {
        $connectResponse = new ConnectResponse();

        $apiKey = $serviceProviderConfig->getApiKey();

        if (empty($apiKey)) {
            $connectResponse->setMessage(__('service_provider.api_key_empty'));
            $connectResponse->setStatus(false);
            return $connectResponse;
        }

        $logger = di(LoggerInterface::class);
        $qwenAPI = new QwenImageAPI($apiKey);

        $body = [];
        // 文生图configuration
        $body['prompt'] = '生成一只狗';
        $body['size'] = '1328*1328'; // useqwen-image支持的默认1:1尺寸
        $body['n'] = 1;
        $body['model'] = $modelVersion;
        $body['watermark'] = false;
        $body['prompt_extend'] = false;

        try {
            $response = $qwenAPI->submitTask($body);

            // 检查响应格式
            if (! isset($response['output']['task_id'])) {
                $connectResponse->setStatus(false);
                $connectResponse->setMessage($response['message'] ?? '响应格式error');
                return $connectResponse;
            }

            // 连通性测试success，不need等待任务完成
            $connectResponse->setStatus(true);
            $connectResponse->setMessage('连接测试success');
        } catch (Exception $e) {
            $connectResponse->setStatus(false);
            if ($e instanceof ClientException) {
                $connectResponse->setMessage(Json::decode($e->getResponse()->getBody()->getContents()));
            } else {
                $connectResponse->setMessage($e->getMessage());
            }
        }

        return $connectResponse;
    }
}
