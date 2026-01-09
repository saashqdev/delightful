<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Kernel;

use HyperfTest\Cases\Api\AbstractHttpTest;

/**
 * @internal
 * @coversNothing
 */
class GlobalConfigApiTest extends AbstractHttpTest
{
    private string $url = '/api/v1/settings/global';

    public function testGetGlobalConfigDefault(): void
    {
        $response = $this->get($this->url, [], $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $data = $response['data'];
        $this->assertArrayValueTypesEquals([
            'is_maintenance' => false,
            'maintenance_description' => '',
        ], $data, '默认全局configuration结构不符', false, true);
    }

    public function testUpdateGlobalConfig(): void
    {
        $payload = [
            'is_maintenance' => true,
            'maintenance_description' => 'unit test maintenance',
        ];

        $putResponse = $this->put($this->url, $payload, $this->getCommonHeaders());
        $this->assertSame(1000, $putResponse['code']);
        $putData = $putResponse['data'];
        $this->assertArrayEquals($payload, $putData, 'PUT return数据不一致');

        // 再次 GET 验证cache及持久化
        $getResponse = $this->get($this->url, [], $this->getCommonHeaders());
        $this->assertSame(1000, $getResponse['code']);
        $getData = $getResponse['data'];
        $this->assertArrayEquals($payload, $getData, 'GET return数据与预期不符');
    }

    public function testGetGlobalConfigWithPlatformSettings(): void
    {
        // 首先set平台set
        $platformPayload = [
            'logo_zh_url' => 'https://example.com/logo_zh.png',
            'logo_en_url' => 'https://example.com/logo_en.png',
            'favicon_url' => 'https://example.com/favicon.ico',
            'default_language' => 'zh_CN',
            'name_i18n' => [
                'zh_CN' => 'test平台',
                'en_US' => 'Test Platform',
            ],
        ];

        // 通过平台set接口set
        $this->put('/api/v1/platform/setting', $platformPayload, $this->getCommonHeaders());

        // get全局configuration，应该包含平台set
        $response = $this->get($this->url, [], $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $data = $response['data'];

        // 验证包含维护模式configuration
        $this->assertArrayHasKey('is_maintenance', $data);
        $this->assertArrayHasKey('maintenance_description', $data);

        // 验证包含平台set
        $this->assertArrayHasKey('logo', $data);
        $this->assertArrayHasKey('favicon', $data);
        $this->assertArrayHasKey('default_language', $data);

        // 验证平台set的value
        if (isset($data['logo']['zh_CN']['url'])) {
            $this->assertSame('https://example.com/logo_zh.png', $data['logo']['zh_CN']['url']);
        }
        if (isset($data['logo']['en_US']['url'])) {
            $this->assertSame('https://example.com/logo_en.png', $data['logo']['en_US']['url']);
        }
        if (isset($data['favicon']['url'])) {
            $this->assertSame('https://example.com/favicon.ico', $data['favicon']['url']);
        }
        $this->assertSame('zh_CN', $data['default_language']);
    }

    public function testGetGlobalConfigResponseStructure(): void
    {
        $response = $this->get($this->url, [], $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $data = $response['data'];

        // 验证基本结构
        $this->assertIsArray($data);
        $this->assertArrayHasKey('is_maintenance', $data);
        $this->assertArrayHasKey('maintenance_description', $data);

        // 验证type
        $this->assertIsBool($data['is_maintenance']);
        $this->assertIsString($data['maintenance_description']);

        // 如果有平台set，验证其结构
        if (isset($data['logo'])) {
            $this->assertIsArray($data['logo']);
        }
        if (isset($data['favicon'])) {
            $this->assertIsArray($data['favicon']);
        }
        if (isset($data['default_language'])) {
            $this->assertIsString($data['default_language']);
        }
    }
}
