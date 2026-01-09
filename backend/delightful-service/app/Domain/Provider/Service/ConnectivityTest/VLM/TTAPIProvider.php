<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Provider\Service\ConnectivityTest\VLM;

use App\Domain\Provider\DTO\Item\ProviderConfigItem;
use App\Domain\Provider\Service\ConnectivityTest\ConnectResponse;
use App\Domain\Provider\Service\ConnectivityTest\IProvider;
use App\Infrastructure\ExternalAPI\ImageGenerateAPI\Model\Flux\FluxAPI;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Hyperf\Codec\Json;

use function Hyperf\Translation\__;

class TTAPIProvider implements IProvider
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

        try {
            // TTAPI 任意test即can,andnotneed生graph,因for VLM modelis超leveladministratoradd,inusefrontwe肯定testsuccess
            // 所byonlyneedtestonecostlowinterface即can
            $fluxAPI = new FluxAPI($apiKey);
            $fluxAPI->getAccountInfo();
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
