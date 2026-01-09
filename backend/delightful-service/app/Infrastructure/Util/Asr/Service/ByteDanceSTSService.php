<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Infrastructure\Util\Asr\Service;

use App\ErrorCode\AsrErrorCode;
use App\Infrastructure\Core\Exception\ExceptionBuilder;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Codec\Json;
use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;
use Psr\Log\LoggerInterface;

/**
 * 字节跳动voiceserviceSTStokenservice
 * useatgetvoiceservice的JWT token.
 */
class ByteDanceSTSService
{
    /** service端requestJWT token的API端点 */
    private const string STS_TOKEN_URL = 'https://openspeech.bytedance.com/api/v1/sts/token';

    /** JWT Tokencache前缀 */
    private const string JWT_CACHE_PREFIX = 'asr:jwt_token:';

    /** logrecord器 */
    protected LoggerInterface $logger;

    /** HTTP客户端 */
    private Client $client;

    /** Redis客户端 */
    private Redis $redis;

    public function __construct()
    {
        $this->client = new Client();
        $container = ApplicationContext::getContainer();
        $this->logger = $container->get(LoggerFactory::class)->get(static::class);
        $this->redis = $container->get(RedisFactory::class)->get('default');
    }

    /**
     * according touserDelightful IDgetJWT Token（带cache）.
     *
     * @param string $delightfulId userDelightful ID
     * @param int $duration valid期（秒），default7200秒
     * @param bool $refresh whetherforcerefreshtoken，defaultfalse
     * @return array containJWT Token和相关info的array
     * @throws Exception
     */
    public function getJwtTokenForUser(string $delightfulId, int $duration = 7200, bool $refresh = false): array
    {
        if (empty($delightfulId)) {
            ExceptionBuilder::throw(AsrErrorCode::InvalidDelightfulId, 'asr.config_error.invalid_delightful_id');
        }

        // checkcache（ifnot是forcerefresh）
        $cacheKey = $this->getCacheKey($delightfulId);
        if (! $refresh) {
            $cachedData = $this->getCachedJwtToken($cacheKey);

            if ($cachedData !== null) {
                // 计算remainingvalidtime
                $remainingDuration = $cachedData['expires_at'] - time();
                $cachedData['duration'] = max(0, $remainingDuration);

                $this->logger->info('returncache的JWT Token', [
                    'delightful_id' => $delightfulId,
                    'cache_expires_at' => $cachedData['expires_at'],
                    'remaining_duration' => $remainingDuration,
                ]);
                return $cachedData;
            }
        }

        // cache中nothaveor已expire，or者forcerefresh，getnewJWT Token
        $appId = config('asr.volcengine.app_id');
        $accessToken = config('asr.volcengine.token');

        if (empty($appId) || empty($accessToken)) {
            ExceptionBuilder::throw(AsrErrorCode::InvalidConfig, 'asr.config_error.invalid_config');
        }

        $jwtToken = $this->getJwtToken($appId, $accessToken, $duration);

        // buildreturndata
        $tokenData = [
            'jwt_token' => $jwtToken,
            'app_id' => $appId,
            'duration' => $duration,
            'expires_at' => time() + $duration,
            'resource_id' => 'volc.bigasr.sauc.duration',
            'delightful_id' => $delightfulId,
        ];

        // cacheJWT Token，提前30秒expireby避免边界issue
        $cacheExpiry = max(1, $duration - 30);
        $this->cacheJwtToken($cacheKey, $tokenData, $cacheExpiry);

        $this->logger->info('generate并cachenewJWT Token', [
            'delightful_id' => $delightfulId,
            'duration' => $duration,
            'cache_expiry' => $cacheExpiry,
            'refresh' => $refresh,
        ]);

        return $tokenData;
    }

    /**
     * getJWT token.
     *
     * @param string $appId applicationID
     * @param string $accessToken accesstoken
     * @param int $duration valid期（秒），default7200秒
     * @return string JWT token
     * @throws Exception
     */
    public function getJwtToken(string $appId, string $accessToken, int $duration = 7200): string
    {
        if (empty($appId) || empty($accessToken)) {
            ExceptionBuilder::throw(AsrErrorCode::InvalidConfig, 'asr.config_error.invalid_config');
        }

        $body = [
            'appid' => $appId,
            'duration' => $duration,
        ];

        $headers = [
            'Authorization' => 'Bearer; ' . $accessToken,
            'Content-Type' => 'application/json',
        ];

        try {
            $this->logger->info('requestJWT token', [
                'appid' => $appId,
                'duration' => $duration,
            ]);

            $response = $this->client->post(self::STS_TOKEN_URL, [
                'headers' => $headers,
                'json' => $body,
            ]);

            $responseBody = $response->getBody()->getContents();
            $responseData = json_decode($responseBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('parseresponseJSONfail', [
                    'response' => $responseBody,
                    'error' => json_last_error_msg(),
                ]);
                ExceptionBuilder::throw(AsrErrorCode::Error, 'asr.sts_token.parse_response_failed');
            }

            if (! isset($responseData['jwt_token'])) {
                $this->logger->error('response中缺少jwt_tokenfield', [
                    'response' => $responseData,
                ]);
                ExceptionBuilder::throw(AsrErrorCode::Error, 'asr.sts_token.missing_jwt_token');
            }

            $jwtToken = $responseData['jwt_token'];

            $this->logger->info('successgetJWT token', [
                'appid' => $appId,
                'token_length' => strlen($jwtToken),
            ]);

            return $jwtToken;
        } catch (GuzzleException $e) {
            $this->logger->error('getJWT tokenfail', [
                'appid' => $appId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            ExceptionBuilder::throw(AsrErrorCode::Error, 'asr.sts_token.request_failed');
        }
    }

    /**
     * use环境variableconfigurationgetJWT token.
     *
     * @param int $duration valid期（秒），default7200秒
     * @return string JWT token
     * @throws Exception
     */
    public function getJwtTokenFromConfig(int $duration = 7200): string
    {
        $appId = config('asr.volcengine.app_id');
        $accessToken = config('asr.volcengine.token');

        if (empty($appId) || empty($accessToken)) {
            $this->logger->error('ASRconfigurationnot完整', [
                'app_id_exists' => ! empty($appId),
                'access_token_exists' => ! empty($accessToken),
            ]);
            ExceptionBuilder::throw(AsrErrorCode::InvalidConfig, 'asr.config_error.invalid_config');
        }

        return $this->getJwtToken($appId, $accessToken, $duration);
    }

    /**
     * 清exceptuser的JWT Tokencache.
     *
     * @param string $delightfulId userDelightful ID
     * @return bool whethersuccess清except
     */
    public function clearUserJwtTokenCache(string $delightfulId): bool
    {
        try {
            $cacheKey = $this->getCacheKey($delightfulId);
            $result = $this->redis->del($cacheKey);

            $this->logger->info('清exceptuserJWT Tokencache', [
                'delightful_id' => $delightfulId,
                'result' => $result,
            ]);

            return is_int($result) && $result > 0;
        } catch (Exception $e) {
            $this->logger->error('清exceptJWT Tokencachefail', [
                'delightful_id' => $delightfulId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * generatecache键.
     *
     * @param string $delightfulId userDelightful ID
     * @return string cache键
     */
    private function getCacheKey(string $delightfulId): string
    {
        return self::JWT_CACHE_PREFIX . md5($delightfulId);
    }

    /**
     * fromcachegetJWT Token.
     *
     * @param string $cacheKey cache键
     * @return null|array cache的dataornull
     */
    private function getCachedJwtToken(string $cacheKey): ?array
    {
        try {
            $cachedData = $this->redis->get($cacheKey);

            if ($cachedData === null || $cachedData === false) {
                return null;
            }

            $data = Json::decode($cachedData);

            // checkwhether已expire（额外的securitycheck）
            if (isset($data['expires_at']) && $data['expires_at'] <= time()) {
                $this->redis->del($cacheKey);
                return null;
            }

            return $data;
        } catch (Exception $e) {
            $this->logger->warning('getcacheJWT Tokenfail', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * cacheJWT Token.
     *
     * @param string $cacheKey cache键
     * @param array $tokenData Tokendata
     * @param int $expiry expiretime（秒）
     */
    private function cacheJwtToken(string $cacheKey, array $tokenData, int $expiry): void
    {
        try {
            $this->redis->setex($cacheKey, $expiry, Json::encode($tokenData));
        } catch (Exception $e) {
            $this->logger->warning('cacheJWT Tokenfail', [
                'cache_key' => $cacheKey,
                'expiry' => $expiry,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
