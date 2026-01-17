<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Infrastructure\Utils;

use Hyperf\Context\ApplicationContext;
use Hyperf\Redis\Redis;

/**
 * Temporary access token utility.
 * Manages token lifecycle and permissions via Redis.
 */
class AccessTokenUtil
{
    /**
     * Redis key prefix.
     */
    protected static string $prefix = 'be_delightful_access_token:';

    /**
     * Default expiration time (seconds).
     */
    protected static int $defaultTtl = 3600;

    /**
     * Generate a temporary access token.
     *
     * @param string $shareId Share ID
     * @param string $organizationCode Organization code
     * @param string $scope Permission scope (e.g., read, write, full)
     * @param null|int $ttl Expiration time (seconds); uses default when not provided
     * @param null|array $metadata Additional metadata
     * @return string Generated token
     */
    public static function generate(string $shareId, string $organizationCode = '', string $scope = 'read', ?int $ttl = null, ?array $metadata = null): string
    {
        $redis = self::getRedis();
        $actualTtl = $ttl ?? self::$defaultTtl;

        // Generate a deterministic token based on share ID and scope plus PHP timestamp
        $token = md5($shareId . ':' . $scope . ':' . $organizationCode . ':' . time());

        // Build token data
        $tokenData = [
            'share_id' => $shareId,
            'scope' => $scope,
            'created_at' => time(),
            'expires_at' => time() + $actualTtl,
            'metadata' => $metadata ?? [],
            'organization_code' => $organizationCode,
        ];

        // Store token data
        $key = self::$prefix . $token;
        $redis->set($key, json_encode($tokenData));
        $redis->expire($key, $actualTtl);

        return $token;
    }

    /**
     * Validate a token.
     *
     * @param string $token Access token
     * @param null|string $requiredScope Required permission scope
     * @return bool Whether the token is valid
     */
    public static function validate(string $token, ?string $requiredScope = null): bool
    {
        $data = self::getTokenData($token);

        // Token not found
        if (! $data) {
            return false;
        }

        // Token expired
        if (time() > ($data['expires_at'] ?? 0)) {
            self::revoke($token);
            return false;
        }

        // Validate scope
        if ($requiredScope && ($data['scope'] ?? '') !== $requiredScope) {
            return false;
        }

        return true;
    }

    /**
     * Revoke a token.
     *
     * @param string $token Access token
     * @return bool Whether the operation succeeded
     */
    public static function revoke(string $token): bool
    {
        $redis = self::getRedis();
        $tokenKey = self::$prefix . $token;
        return (bool) $redis->del($tokenKey);
    }

    /**
     * Refresh token expiration.
     *
     * @param string $token Access token
     * @param null|int $ttl New expiration time (seconds); uses default when not provided
     * @return bool Whether the operation succeeded
     */
    public static function refresh(string $token, ?int $ttl = null): bool
    {
        $data = self::getTokenData($token);
        if (! $data) {
            return false;
        }

        $data['expires_at'] = time() + ($ttl ?? self::$defaultTtl);

        $key = self::$prefix . $token;
        $redis = self::getRedis();
        $redis->set($key, json_encode($data));
        $redis->expire($key, $ttl ?? self::$defaultTtl);

        return true;
    }

    /**
     * Get the share ID associated with the token.
     *
     * @param string $token Access token
     * @return null|string Share ID; null if invalid token
     */
    public static function getShareId(string $token): ?string
    {
        $data = self::getTokenData($token);
        return $data['share_id'] ?? null;
    }

    public static function getOrganizationCode(string $token): ?string
    {
        $data = self::getTokenData($token);
        return $data['organization_code'] ?? null;
    }

    /**
     * Get token metadata.
     *
     * @param string $token Access token
     * @return null|array Token metadata; null if invalid token
     */
    public static function getMetadata(string $token): ?array
    {
        $data = self::getTokenData($token);
        return $data['metadata'] ?? null;
    }

    /**
     * Extract token from request.
     *
     * @param array $headers Request headers
     * @param array $query Query parameters
     * @param array $body Request body
     * @return null|string Extracted token; null if not found
     */
    public static function extractTokenFromRequest(array $headers = [], array $query = [], array $body = []): ?string
    {
        // Extract from Authorization header
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        if (is_string($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Extract from query parameters
        if (isset($query['access_token']) && is_string($query['access_token'])) {
            return $query['access_token'];
        }

        // Extract from request body
        if (isset($body['access_token']) && is_string($body['access_token'])) {
            return $body['access_token'];
        }

        return null;
    }

    /**
     * Get Redis instance.
     */
    protected static function getRedis(): Redis
    {
        return ApplicationContext::getContainer()->get(Redis::class);
    }

    /**
     * Get token data.
     *
     * @param string $token Access token
     * @return null|array Token data; null if not found
     */
    protected static function getTokenData(string $token): ?array
    {
        $redis = self::getRedis();
        $json = $redis->get(self::$prefix . $token);
        if (! $json) {
            return null;
        }

        return json_decode($json, true);
    }

    /**
     * Generate a UUID string.
     *
     * @return string UUID string
     */
    protected static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0x0FFF) | 0x4000,
            mt_rand(0, 0x3FFF) | 0x8000,
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF),
            mt_rand(0, 0xFFFF)
        );
    }
}
