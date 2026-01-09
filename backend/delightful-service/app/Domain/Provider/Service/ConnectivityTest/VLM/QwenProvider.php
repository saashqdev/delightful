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
 * general meaningthousandquestionservicequotient.
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
        // text generationgraphconfiguration
        $body['prompt'] = 'generateoneonlydog';
        $body['size'] = '1328*1328'; // useqwen-imagesupportdefault1:1size
        $body['n'] = 1;
        $body['model'] = $modelVersion;
        $body['watermark'] = false;
        $body['prompt_extend'] = false;

        try {
            $response = $qwenAPI->submitTask($body);

            // checkresponseformat
            if (! isset($response['output']['task_id'])) {
                $connectResponse->setStatus(false);
                $connectResponse->setMessage($response['message'] ?? 'responseformaterror');
                return $connectResponse;
            }

            // connectedpropertytestsuccess,notneedetcpendingtaskcomplete
            $connectResponse->setStatus(true);
            $connectResponse->setMessage('connecttestsuccess');
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
