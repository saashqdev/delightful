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
        ], $data, 'defaultall局configuration结构not符', false, true);
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
        $this->assertArrayEquals($payload, $putData, 'PUT returndatanotone致');

        // againtime GET verifycacheand持久化
        $getResponse = $this->get($this->url, [], $this->getCommonHeaders());
        $this->assertSame(1000, $getResponse['code']);
        $getData = $getResponse['data'];
        $this->assertArrayEquals($payload, $getData, 'GET returndataandexpectednot符');
    }

    public function testGetGlobalConfigWithPlatformSettings(): void
    {
        // firstsetplatformset
        $platformPayload = [
            'logo_zh_url' => 'https://example.com/logo_zh.png',
            'logo_en_url' => 'https://example.com/logo_en.png',
            'favicon_url' => 'https://example.com/favicon.ico',
            'default_language' => 'zh_CN',
            'name_i18n' => [
                'zh_CN' => 'testplatform',
                'en_US' => 'Test Platform',
            ],
        ];

        // passplatformsetinterfaceset
        $this->put('/api/v1/platform/setting', $platformPayload, $this->getCommonHeaders());

        // getall局configuration,shouldcontainplatformset
        $response = $this->get($this->url, [], $this->getCommonHeaders());
        $this->assertSame(1000, $response['code']);
        $data = $response['data'];

        // verifycontain维护模typeconfiguration
        $this->assertArrayHasKey('is_maintenance', $data);
        $this->assertArrayHasKey('maintenance_description', $data);

        // verifycontainplatformset
        $this->assertArrayHasKey('logo', $data);
        $this->assertArrayHasKey('favicon', $data);
        $this->assertArrayHasKey('default_language', $data);

        // verifyplatformsetvalue
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

        // verify基本结构
        $this->assertIsArray($data);
        $this->assertArrayHasKey('is_maintenance', $data);
        $this->assertArrayHasKey('maintenance_description', $data);

        // verifytype
        $this->assertIsBool($data['is_maintenance']);
        $this->assertIsString($data['maintenance_description']);

        // ifhaveplatformset,verifyits结构
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
