<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\ExternalAPI\Sms\Volcengine\Api;

use App\Infrastructure\ExternalAPI\Sms\Volcengine\Base\Sign;
use App\Infrastructure\ExternalAPI\Sms\Volcengine\Base\SignParam;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\RequestOptions;
use Hyperf\Codec\Json;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Guzzle\ClientFactory;
use RuntimeException;

use function Hyperf\Config\config;

/**
 * 火山short信所have api 基础category.
 * @see https://www.volcengine.com/docs/6361/171579
 */
abstract class VolcengineApi
{
    /**
     * 国insideshort信requestground址
     */
    private const CHINA_HOST = 'https://sms.volcengineapi.com';

    /**
     * 国outsideshort信requestground址
     */
    private const SINGAPORE_HOST = 'https://sms.byteplusapi.com';

    private const CHINA_REGION = 'cn-north-1';

    private const SINGAPORE_REGION = 'ap-singapore-1';

    protected string $method;

    protected string $path;

    protected string $region;

    /**
     * interfacename.
     */
    protected string $action;

    /**
     * interfaceversion.
     */
    protected string $version;

    /**
     * requestground址
     */
    protected string $host;

    protected string $accessKey = '';

    protected string $secretKey = '';

    /**
     * short信signature. such as[灯塔engine].
     */
    protected string $sign = '';

    /**
     * short信messagegroupid.
     */
    protected string $messageGroupId = '';

    /**
     * short信templateId.
     */
    protected string $templateId = '';

    protected ClientFactory $clientFactory;

    protected Client $client;

    protected StdoutLoggerInterface $logger;

    private string $service = 'volcSMS';

    private array $query = [];

    private array $body = [];

    private array $headers = [];

    public function __construct(ClientFactory $clientFactory, StdoutLoggerInterface $logger, string $region = self::CHINA_REGION)
    {
        // 部minutepublicfixedparameterinconstructparametermiddlecertain
        $this->setRegion($region);
        $this->setSecretKey(config('sms.volcengine.secretKey'));
        $this->setAccessKey(config('sms.volcengine.accessKey'));
        $this->setQuery();
        $this->setHeaders();
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
        $this->client = $this->clientFactory->create([
            RequestOptions::TIMEOUT => 10,
            'base_uri' => $this->getHost(),
        ]);
    }

    /**
     * @throws GuzzleException
     * @todo 接入messagesendstatuscallback
     */
    protected function sendRequest()
    {
        // setrequestsignatureandX-Daterequesthead
        $this->setAuth();
        try {
            // requestheadappendsignature
            $options = [
                RequestOptions::QUERY => $this->getQuery(),
                RequestOptions::HEADERS => $this->getHeaders(),
                RequestOptions::JSON => $this->getBody(),
            ];
            $response = $this->client->request($this->method, $this->getPath(), $options);
            $responseBody = Json::decode($response->getBody()->getContents());
            // conducterror码judge
            if (isset($responseBody['ResponseMetadata']['Error'])) {
                $this->logger->error('sendSmsError ' . Json::encode($responseBody));
                throw new RuntimeException('short信sendfail');
            }
            $this->logger->info(sprintf('volce sendRequest %s response %s', Json::encode($options), Json::encode($responseBody)));
            return $responseBody;
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
            if ($response) {
                $body = $response->getBody()->getContents();
                $this->logger->error('sendSmsError ' . $body);
            } else {
                $this->logger->error('sendSmsError ' . $exception->getMessage());
            }
            throw $exception;
        }
    }

    /**
     * acceptdifferentshort信typesend
     */
    protected function init(string $messageGroupId, string $sign, string $templateId): void
    {
        $this->messageGroupId = $messageGroupId;
        $this->sign = $sign;
        $this->templateId = $templateId;
    }

    protected function getBody(): array
    {
        return $this->body;
    }

    protected function addHeader(string $key, $value): void
    {
        // 字section方requestheadvalueisarray,才can参andback续signature
        $value = is_array($value) ? $value : [$value];
        $this->headers[$key] = $value;
    }

    protected function setBody(array $body): void
    {
        $this->body = $body;
    }

    /**
     * setparametersignatureandpublicrequestheadparameterX-Date.
     */
    protected function setAuth(): void
    {
        $this->client->getConfig();
        $sign = new Sign();
        $credentials = [];
        $credentials['region'] = $this->getRegion();
        $credentials['service'] = $this->getService();
        $credentials['ak'] = $this->getAccessKey();
        $credentials['sk'] = $this->getSecretKey();
        $req = new SignParam();
        $req->setDate(new DateTime());
        $req->setHeaderList($this->getHeaders());
        $req->setHost($this->getHost());
        $req->setPath($this->getPath());
        $req->setIsSignUrl(false);
        $req->setMethod($this->getMethod());
        $req->setQueryList($this->getQuery());
        // !!! notice,thiswithinnotcanaddup JSON_UNESCAPED_UNICODE,addwill导致bodyhavemiddle文o clocksignaturenotcorrect!
        $bodyStream = Utils::streamFor(Json::encode($this->getBody(), JSON_THROW_ON_ERROR));
        $req->setPayloadHash(Utils::hash($bodyStream, 'sha256'));
        $result = $sign->signOnly($req, $credentials);
        // requestheadaddupX-Date
        $this->addHeader('X-Date', $result->getXDate());
        $auth = $result->getAuthorization();
        // addupsignature
        $this->addHeader('Authorization', $auth);
    }

    protected function setHeaders(): void
    {
        // researchhair现,documentrequireinrequestheadmiddle传AccessKey/SecretKey/ServiceName/Region,its实cannot传. Authorizationheadmiddlehave传AccessKey
        $this->headers = [
            'Content-Type' => ['application/json;charset=utf-8'],
            'User-Agent' => ['volc-sdk-php/v1.0.87'],
            //            'AccessKey' => $this->getaccessKey(),
            //            'SecretKey' => $this->getSecretKey(),
            //            'ServiceName' => $this->getService(),
            //            'Region' => $this->getRegion(),
        ];
    }

    protected function getSign(): string
    {
        return $this->sign;
    }

    protected function getMessageGroupId(): string
    {
        return $this->messageGroupId;
    }

    protected function getTemplateId(): string
    {
        return $this->templateId;
    }

    protected function setAccessKey(string $ak): void
    {
        $this->accessKey = $ak;
    }

    protected function setSecretKey($sk): void
    {
        $this->secretKey = $sk;
    }

    protected function getQuery(): array
    {
        return $this->query;
    }

    protected function getHeaders(): array
    {
        return $this->headers;
    }

    protected function getMethod(): string
    {
        return $this->method;
    }

    protected function getUrl(): string
    {
        return $this->getHost() . $this->getPath();
    }

    protected function getPath(): string
    {
        return $this->path;
    }

    protected function getRegion(): string
    {
        return $this->region;
    }

    protected function getService(): string
    {
        return $this->service;
    }

    protected function getHost(): string
    {
        return $this->host;
    }

    protected function getAction(): string
    {
        return $this->action;
    }

    protected function getVersion(): string
    {
        return $this->version;
    }

    private function getAccessKey(): string
    {
        return $this->accessKey;
    }

    private function getSecretKey(): string
    {
        return $this->secretKey;
    }

    private function setQuery(): void
    {
        $this->query = ['Action' => $this->getAction(), 'Version' => $this->getVersion()];
    }

    private function setRegion(string $region): void
    {
        // regiononlysupportmiddle国andnewadd坡,defaultmiddle国
        if ($region === self::SINGAPORE_REGION) {
            $this->setHost(self::SINGAPORE_HOST);
        } else {
            $region = self::CHINA_REGION;
            $this->setHost(self::CHINA_HOST);
        }
        $this->region = $region;
    }

    private function setHost(string $host): void
    {
        $this->host = $host;
    }
}
