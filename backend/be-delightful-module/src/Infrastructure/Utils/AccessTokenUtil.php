<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Infrastructure\Utils;

use Hyperf\Context\ApplicationContext;
use Hyperf\Redis\Redis;
/** * temporary access token tool class. * Based on Redis to manage token lifecycle and permissions. */

class AccessTokenUtil 
{
 /** * Redis key prefix. */ 
    protected 
    static string $prefix = 'super_magic_access_token:'; /** * Default expiration time (seconds). */ 
    protected 
    static int $defaultTtl = 3600; /** * Generate temporary access token. * * @param string $shareId ShareID * @param string $organizationCode OrganizationCode * @param string $scope permission scope (e.g.: read, write, full) * @param null|int $ttl Expiration time (seconds)If not specified, use default value * @param null|array $metadata Additional metadata * @return string Generate d token */ 
    public 
    static function generate(string $shareId, string $organizationCode = '', string $scope = 'read', ?int $ttl = null, ?array $metadata = null): string 
{
 $redis = self::getRedis();
$actualTtl = $ttl ?? self::$defaultTtl; // Generate deterministic token based on share ID and scope + Get PHP timestamp $token = md5($shareId . ':' . $scope . ':' . $organizationCode . ':' . time()); // BuildTokenData $tokenData = [ 'share_id' => $shareId, 'scope' => $scope, 'created_at' => time(), 'expires_at' => time() + $actualTtl, 'metadata' => $metadata ?? [], 'organization_code' => $organizationCode, ]; // Store token data $key = self::$prefix . $token; $redis->set($key, json_encode($tokenData)); $redis->expire($key, $actualTtl); return $token; 
}
 /** * Validate token validity. * * @param string $token Access token * @param null|string $requiredScope Required permission scope * @return bool Tokenwhether valid */ 
    public 
    static function validate(string $token, ?string $requiredScope = null): bool 
{
 $data = self::getTokenData($token); // Tokendoes not exist if (! $data) 
{
 return false; 
}
 // TokenExpired if (time() > ($data['expires_at'] ?? 0)) 
{
 self::revoke($token); return false; 
}
 // Validate permission Range if ($requiredScope && ($data['scope'] ?? '') !== $requiredScope) 
{
 return false; 
}
 return true; 
}
 /** * UndoToken. * * @param string $token Access token * @return bool whether operation succeeded */ 
    public 
    static function revoke(string $token): bool 
{
 $redis = self::getRedis(); $tokenKey = self::$prefix . $token; return (bool) $redis->del($tokenKey); 
}
 /** * Refresh token validity period. * * @param string $token Access token * @param null|int $ttl NewExpiration time (seconds)If not specified, use default value * @return bool whether operation succeeded */ 
    public 
    static function refresh(string $token, ?int $ttl = null): bool 
{
 $data = self::getTokenData($token);
if (! $data) 
{
 return false; 
}
 $data['expires_at'] = time() + ($ttl ?? self::$defaultTtl); $key = self::$prefix . $token; $redis = self::getRedis(); $redis->set($key, json_encode($data)); $redis->expire($key, $ttl ?? self::$defaultTtl); return true; 
}
 /** * Get share ID associated with token. * * @param string $token Access token * @return null|string ShareIDReturn null for invalid token */ 
    public 
    static function getShareId(string $token): ?string 
{
 $data = self::getTokenData($token); return $data['share_id'] ?? null; 
}
 
    public 
    static function getOrganizationCode(string $token): ?string 
{
 $data = self::getTokenData($token); return $data['organization_code'] ?? null; 
}
 /** * Get token metadata. * * @param string $token Access token * @return null|array Token metadataReturn null for invalid token */ 
    public 
    static function getMetadata(string $token): ?array 
{
 $data = self::getTokenData($token); return $data['metadata'] ?? null; 
}
 /** * Extract token from request. * * @param array $headers Request headers array * @param array $query query ParameterArray * @param array $body Request body array * @return null|string Extracted token, return null if not found */ 
    public 
    static function extractTokenFromRequest(array $headers = [], array $query = [], array $body = []): ?string 
{
 // Extract from Authorization header $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ''; if (is_string($authHeader) && str_starts_with($authHeader, 'Bearer ')) 
{
 return substr($authHeader, 7); 
}
 // Extract from query parameter if (isset($query['access_token']) && is_string($query['access_token'])) 
{
 return $query['access_token']; 
}
 // Extract from request body if (isset($body['access_token']) && is_string($body['access_token'])) 
{
 return $body['access_token']; 
}
 return null; 
}
 /** * GetRedisInstance. */ 
    protected 
    static function getRedis(): Redis 
{
 return ApplicationContext::getincluding er()->get(Redis::class); 
}
 /** * GetTokenData. * * @param string $token Access token * @return null|array TokenDatadoes not existReturn null */ 
    protected 
    static function getTokenData(string $token): ?array 
{
 $redis = self::getRedis(); $json = $redis->get(self::$prefix . $token); if (! $json) 
{
 return null; 
}
 return json_decode($json, true); 
}
 /** * Generate UUIDString. * * @return string UUIDString */ 
    protected 
    static function generateUuid(): string 
{
 return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0x0FFF) | 0x4000, mt_rand(0, 0x3FFF) | 0x8000, mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF), mt_rand(0, 0xFFFF) ); 
}
 
}
 
