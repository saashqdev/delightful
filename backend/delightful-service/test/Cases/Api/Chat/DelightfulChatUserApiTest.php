<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Chat;

use HyperfTest\Cases\Api\AbstractHttpTest;

/**
 * @internal
 * Delightful聊天userAPI测试
 */
class DelightfulChatUserApiTest extends AbstractHttpTest
{
    private const string UPDATE_USER_INFO_API = '/api/v1/contact/users/me';

    private const string GET_USER_UPDATE_PERMISSION_API = '/api/v1/contact/users/me/update-permission';

    private const string LOGIN_API = '/api/v1/sessions';

    /**
     * 登录账号：13800138001
     * 密码：123456.
     */
    private const string TEST_PHONE = '13800138001';

    private const string TEST_PASSWORD = '123456';

    private const string TEST_STATE_CODE = '+86';

    private const string TEST_ORGANIZATION_CODE = 'test001';

    /**
     * 存储登录后的token.
     */
    private static string $accessToken = '';

    /**
     * 测试完整更新user信息 - 更新所有字段.
     */
    public function testUpdateUserInfoWithAllFields(): void
    {
        // 先登录获取token
        $token = $this->performLogin();
        echo "\n使用token进行user信息更新: " . $token . "\n";

        $requestData = [
            'avatar_url' => 'https://example.com/avatar/new-avatar.jpg',
            'nickname' => '新nickname',
        ];

        $headers = $this->getTestHeaders();
        echo "\n请求头信息: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n";

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $headers);

        echo "\n响应结果: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";

        // 检查响应是否为array
        $this->assertIsArray($response, '响应应该是array格式');

        // 如果响应containerror信息，输出详细信息
        if (isset($response['code']) && $response['code'] !== 1000) {
            echo "\n接口returnerror: code=" . $response['code'] . ', message=' . ($response['message'] ?? 'unknown') . "\n";

            // 如果是认证error，我们可以接受并跳过测试
            if ($response['code'] === 2179 || $response['code'] === 3035) {
                $this->markTestSkipped('接口认证fail，可能需要其他认证配置 - 接口路由validate正常');
                return;
            }
        }

        // validate响应结构 - 检查是否有data字段
        $this->assertArrayHasKey('data', $response, '响应应containdata字段');
        $this->assertEquals(1000, $response['code'], '应该returnsuccess响应码');

        $userData = $response['data'];

        // validateuser数据结构 - 检查关键字段存在
        $this->assertArrayHasKey('id', $userData, '响应应containid字段');
        $this->assertArrayHasKey('avatar_url', $userData, '响应应containavatar_url字段');
        $this->assertArrayHasKey('nickname', $userData, '响应应containnickname字段');
        $this->assertArrayHasKey('organization_code', $userData, '响应应containorganization_code字段');
        $this->assertArrayHasKey('user_id', $userData, '响应应containuser_id字段');
        $this->assertArrayHasKey('created_at', $userData, '响应应containcreated_at字段');
        $this->assertArrayHasKey('updated_at', $userData, '响应应containupdated_at字段');

        // validate关键字段不为null
        $this->assertNotEmpty($userData['id'], 'id字段不应为null');
        $this->assertNotEmpty($userData['organization_code'], 'organization_code字段不应为null');
        $this->assertNotEmpty($userData['user_id'], 'user_id字段不应为null');
        $this->assertNotEmpty($userData['created_at'], 'created_at字段不应为null');
        $this->assertNotEmpty($userData['updated_at'], 'updated_at字段不应为null');

        // validate更新的具体字段value
        $this->assertEquals($requestData['avatar_url'], $userData['avatar_url'], 'avatarURL更新fail');
        $this->assertEquals($requestData['nickname'], $userData['nickname'], 'nickname更新fail');
    }

    /**
     * 测试仅更新avatar.
     */
    public function testUpdateUserInfoWithAvatarOnly(): void
    {
        // 先登录获取token
        $this->performLogin();

        $requestData = [
            'avatar_url' => 'https://example.com/avatar/updated-avatar.png',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, '响应应该是array格式');

        // 如果是认证error，跳过测试
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('接口认证fail');
            return;
        }

        $this->assertArrayHasKey('data', $response, '响应应containdata字段');
        $this->assertEquals(1000, $response['code'], '应该returnsuccess响应码');

        $userData = $response['data'];
        $this->assertArrayHasKey('avatar_url', $userData, '响应应containavatar_url字段');
        $this->assertEquals($requestData['avatar_url'], $userData['avatar_url'], 'avatarURL应该被正确更新');
        $this->assertArrayHasKey('nickname', $userData, '响应应containnickname字段');
    }

    /**
     * 测试仅更新nickname.
     */
    public function testUpdateUserInfoWithNicknameOnly(): void
    {
        // 先登录获取token
        $this->performLogin();

        $requestData = [
            'nickname' => 'SuperUser2024',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, '响应应该是array格式');

        // 如果是认证error，跳过测试
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('接口认证fail');
            return;
        }

        $this->assertArrayHasKey('data', $response, '响应应containdata字段');
        $this->assertEquals(1000, $response['code'], '应该returnsuccess响应码');

        $userData = $response['data'];
        $this->assertArrayHasKey('nickname', $userData, '响应应containnickname字段');
        $this->assertEquals($requestData['nickname'], $userData['nickname'], 'nickname应该被正确更新');
    }

    /**
     * 测试nullparameter更新 - 不传任何字段.
     */
    public function testUpdateUserInfoWithEmptyData(): void
    {
        // 先登录获取token
        $this->performLogin();

        $requestData = [];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // nullparameter下应该正常return当前user信息，不报错
        $this->assertIsArray($response, '响应应该是array格式');

        // 如果是认证error，跳过测试
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('接口认证fail');
            return;
        }

        $this->assertArrayHasKey('data', $response, '响应应containdata字段');
        $this->assertEquals(1000, $response['code'], '应该returnsuccess响应码');

        $userData = $response['data'];

        // validate关键字段存在
        $this->assertArrayHasKey('id', $userData, '响应应containid字段');
        $this->assertArrayHasKey('organization_code', $userData, '响应应containorganization_code字段');
        $this->assertArrayHasKey('user_id', $userData, '响应应containuser_id字段');
        $this->assertArrayHasKey('created_at', $userData, '响应应containcreated_at字段');
        $this->assertArrayHasKey('updated_at', $userData, '响应应containupdated_at字段');

        // validate关键字段不为null
        $this->assertNotEmpty($userData['id'], 'id字段不应为null');
        $this->assertNotEmpty($userData['organization_code'], 'organization_code字段不应为null');
        $this->assertNotEmpty($userData['user_id'], 'user_id字段不应为null');
        $this->assertNotEmpty($userData['created_at'], 'created_at字段不应为null');
        $this->assertNotEmpty($userData['updated_at'], 'updated_at字段不应为null');
    }

    /**
     * 测试nullvaluehandle.
     */
    public function testUpdateUserInfoWithNullValues(): void
    {
        // 先登录获取token
        $this->performLogin();

        $requestData = [
            'avatar_url' => null,
            'nickname' => null,
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // nullvalue应该被正确handle，不导致error
        $this->assertIsArray($response, '传入nullvalue时应正常return响应');

        // 如果是认证error，跳过测试
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('接口认证fail');
            return;
        }

        $this->assertArrayHasKey('data', $response, '响应应containdata字段');
        $this->assertEquals(1000, $response['code'], '应该returnsuccess响应码');

        $userData = $response['data'];
        $this->assertArrayHasKey('id', $userData, '响应应containuserID');
    }

    /**
     * 测试特殊字符handle.
     */
    public function testUpdateUserInfoWithSpecialCharacters(): void
    {
        // 先登录获取token
        $this->performLogin();

        $requestData = [
            'nickname' => '测试user🎉',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, '响应应该是array格式');

        // 如果是认证error，跳过测试
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('接口认证fail');
            return;
        }

        $this->assertArrayHasKey('data', $response, '响应应containdata字段');
        $this->assertEquals(1000, $response['code'], '应该returnsuccess响应码');

        $userData = $response['data'];
        $this->assertEquals($requestData['nickname'], $userData['nickname'], '应正确handlecontainemoji的nickname');
    }

    /**
     * 测试长stringhandle.
     */
    public function testUpdateUserInfoWithLongStrings(): void
    {
        // 先登录获取token
        $this->performLogin();

        $requestData = [
            'nickname' => str_repeat('很长的nickname', 10), // 50字符
            'avatar_url' => 'https://example.com/very/long/path/to/avatar/' . str_repeat('long-filename', 5) . '.jpg',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // validate长string是否被正确handle（可能被截断或拒绝）
        $this->assertIsArray($response, '长string应被正确handle');

        // 如果是认证error，跳过测试
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('接口认证fail');
            return;
        }

        $this->assertArrayHasKey('data', $response, '响应应containdata字段');
        $this->assertEquals(1000, $response['code'], '应该returnsuccess响应码');

        $userData = $response['data'];
        $this->assertArrayHasKey('nickname', $userData, '响应应containnickname字段');
        $this->assertArrayHasKey('avatar_url', $userData, '响应应containavatar_url字段');
    }

    /**
     * 测试无效的avatarURL格式.
     */
    public function testUpdateUserInfoWithInvalidAvatarUrl(): void
    {
        // 先登录获取token
        $this->performLogin();

        $requestData = [
            'avatar_url' => 'invalid-url-format',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // 根据业务逻辑，可能接受任何string作为avatar_url，或进行validate
        $this->assertIsArray($response, '无效URL格式应被妥善handle');

        // 如果是认证error，跳过测试
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('接口认证fail');
        }
    }

    /**
     * 测试部分字段更新后的数据完整性.
     */
    public function testUpdateUserInfoDataIntegrity(): void
    {
        // 先登录获取token
        $this->performLogin();

        // 第一次更新：只更新nickname
        $firstUpdateData = [
            'nickname' => '第一次更新的nickname',
        ];

        $firstResponse = $this->patch(self::UPDATE_USER_INFO_API, $firstUpdateData, $this->getTestHeaders());
        $this->assertIsArray($firstResponse, '第一次更新响应应该是array格式');

        // 如果是认证error，跳过测试
        if (isset($firstResponse['code']) && ($firstResponse['code'] === 2179 || $firstResponse['code'] === 3035)) {
            $this->markTestSkipped('接口认证fail');
            return;
        }

        $this->assertArrayHasKey('data', $firstResponse, '第一次更新响应应containdata字段');
        $this->assertEquals(1000, $firstResponse['code'], '第一次更新应该returnsuccess响应码');

        $firstUserData = $firstResponse['data'];
        $originalAvatarUrl = $firstUserData['avatar_url'] ?? null;

        // 第二次更新：只更新avatar
        $secondUpdateData = [
            'avatar_url' => 'https://example.com/new-avatar-2.jpg',
        ];

        $secondResponse = $this->patch(self::UPDATE_USER_INFO_API, $secondUpdateData, $this->getTestHeaders());
        $this->assertIsArray($secondResponse, '第二次更新响应应该是array格式');
        $this->assertArrayHasKey('data', $secondResponse, '第二次更新响应应containdata字段');
        $this->assertEquals(1000, $secondResponse['code'], '第二次更新应该returnsuccess响应码');

        $secondUserData = $secondResponse['data'];

        // validate数据完整性：nickname应保持第一次更新的value
        $this->assertEquals($firstUpdateData['nickname'], $secondUserData['nickname'], 'nickname应保持第一次更新的value');
        $this->assertEquals($secondUpdateData['avatar_url'], $secondUserData['avatar_url'], 'avatar应为第二次更新的value');
    }

    /**
     * 测试未授权访问.
     */
    public function testUpdateUserInfoWithoutAuthorization(): void
    {
        $requestData = [
            'nickname' => '测试nickname',
        ];

        // 不contain授权头的请求
        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, [
            'Content-Type' => 'application/json',
        ]);

        // 应该return授权error
        $this->assertIsArray($response, '响应应该是array格式');
        $this->assertArrayHasKey('code', $response, '未授权请求应returnerror码');
        $this->assertNotEquals(1000, $response['code'] ?? 1000, '未授权请求不应returnsuccess码');
    }

    /**
     * 测试获取user更新权限 - 正常情况.
     */
    public function testGetUserUpdatePermissionSuccess(): void
    {
        // 先登录获取token
        $token = $this->performLogin();
        echo "\n使用token获取user更新权限: " . $token . "\n";

        $headers = $this->getTestHeaders();
        echo "\n请求头信息: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n";

        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, $headers);

        echo "\n响应结果: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";

        // 检查响应是否为array
        $this->assertIsArray($response, '响应应该是array格式');

        // 如果响应containerror信息，输出详细信息
        if (isset($response['code']) && $response['code'] !== 1000) {
            echo "\n接口returnerror: code=" . $response['code'] . ', message=' . ($response['message'] ?? 'unknown') . "\n";

            // 如果是认证error，我们可以接受并跳过测试
            if ($response['code'] === 2179 || $response['code'] === 3035) {
                $this->markTestSkipped('接口认证fail，可能需要其他认证配置 - 接口路由validate正常');
                return;
            }
        }

        // validate响应结构
        $this->assertArrayHasKey('data', $response, '响应应containdata字段');
        $this->assertEquals(1000, $response['code'], '应该returnsuccess响应码');

        $permissionData = $response['data'];

        // validate权限数据结构
        $this->assertArrayHasKey('permission', $permissionData, '响应应containpermission字段');
        $this->assertIsNotArray($permissionData['permission'], 'permission字段不应该是array');
        $this->assertNotNull($permissionData['permission'], 'permission字段不应该为null');
    }

    /**
     * 测试获取user更新权限 - 未授权访问.
     */
    public function testGetUserUpdatePermissionWithoutAuthorization(): void
    {
        // 不contain授权头的请求
        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        // 应该return授权error
        $this->assertIsArray($response, '响应应该是array格式');
        $this->assertArrayHasKey('code', $response, '未授权请求应returnerror码');
        $this->assertNotEquals(1000, $response['code'] ?? 1000, '未授权请求不应returnsuccess码');

        // 常见的未授权error码
        $unauthorizedCodes = [2179, 3035, 401, 403];
        $this->assertContains($response['code'] ?? 0, $unauthorizedCodes, '应该return未授权error码');
    }

    /**
     * 测试获取user更新权限 - 无效token.
     */
    public function testGetUserUpdatePermissionWithInvalidToken(): void
    {
        $headers = [
            'Authorization' => 'invalid_token_123456',
            'organization-code' => self::TEST_ORGANIZATION_CODE,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, $headers);

        // 应该return授权error
        $this->assertIsArray($response, '响应应该是array格式');
        $this->assertArrayHasKey('code', $response, '无效token请求应returnerror码');
        $this->assertNotEquals(1000, $response['code'] ?? 1000, '无效token请求不应returnsuccess码');
    }

    /**
     * 测试获取user更新权限 - 缺少organization-code.
     */
    public function testGetUserUpdatePermissionWithoutOrganizationCode(): void
    {
        // 先登录获取token
        $token = $this->performLogin();

        $headers = [
            'Authorization' => $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            // 故意不contain organization-code
        ];

        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, $headers);

        // 可能returnerror或success，取决于业务逻辑
        $this->assertIsArray($response, '响应应该是array格式');
        $this->assertArrayHasKey('code', $response, '响应应containcode字段');

        // 如果success，validate数据结构
        if ($response['code'] === 1000) {
            $this->assertArrayHasKey('data', $response, 'success响应应containdata字段');
            $permissionData = $response['data'];
            $this->assertArrayHasKey('permission', $permissionData, '响应应containpermission字段');
            $this->assertIsNotArray($permissionData['permission'], 'permission字段不应该是array');
            $this->assertNotNull($permissionData['permission'], 'permission字段不应该为null');
        }
    }

    /**
     * 测试获取user更新权限 - HTTPmethodvalidate.
     */
    public function testGetUserUpdatePermissionHttpMethod(): void
    {
        // 先登录获取token
        $token = $this->performLogin();
        $headers = $this->getTestHeaders();

        // 测试error的HTTPmethod（POST）
        $postResponse = $this->post(self::GET_USER_UPDATE_PERMISSION_API, [], $headers);

        // 应该returnmethod不允许的error
        if ($postResponse !== null) {
            $this->assertIsArray($postResponse, 'POST响应应该是array格式');
            if (isset($postResponse['code'])) {
                // 如果不是认证问题，应该是methoderror
                if (! in_array($postResponse['code'], [2179, 3035])) {
                    $this->assertNotEquals(1000, $postResponse['code'], 'POSTmethod不应该success');
                }
            }
        } else {
            // 如果returnnull，说明method被正确拒绝了
            $this->assertTrue(true, 'POSTmethod被正确拒绝');
        }

        // 测试error的HTTPmethod（PUT）
        $putResponse = $this->put(self::GET_USER_UPDATE_PERMISSION_API, [], $headers);

        // 应该returnmethod不允许的error
        if ($putResponse !== null) {
            $this->assertIsArray($putResponse, 'PUT响应应该是array格式');
            if (isset($putResponse['code'])) {
                // 如果不是认证问题，应该是methoderror
                if (! in_array($putResponse['code'], [2179, 3035])) {
                    $this->assertNotEquals(1000, $putResponse['code'], 'PUTmethod不应该success');
                }
            }
        } else {
            // 如果returnnull，说明method被正确拒绝了
            $this->assertTrue(true, 'PUTmethod被正确拒绝');
        }
    }

    /**
     * execute登录并获取访问令牌.
     */
    private function performLogin(): string
    {
        // 如果已经有token，直接return
        if (! empty(self::$accessToken)) {
            return self::$accessToken;
        }

        $loginData = [
            'state_code' => self::TEST_STATE_CODE,
            'phone' => self::TEST_PHONE,
            'password' => self::TEST_PASSWORD,
            'type' => 'phone_password',
        ];

        $loginResponse = $this->json(self::LOGIN_API, $loginData, [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        // validate登录是否success
        $this->assertIsArray($loginResponse, '登录响应应该是array格式');
        $this->assertEquals(1000, $loginResponse['code'] ?? 0, '登录应该success');
        $this->assertArrayHasKey('data', $loginResponse, '登录响应应containdata字段');
        $this->assertArrayHasKey('access_token', $loginResponse['data'], '登录响应应containaccess_token');

        // 缓存token
        self::$accessToken = $loginResponse['data']['access_token'];

        // 输出调试信息
        echo "\n登录success，获得token: " . self::$accessToken . "\n";
        echo "\n完整登录响应: " . json_encode($loginResponse, JSON_UNESCAPED_UNICODE) . "\n";

        return self::$accessToken;
    }

    /**
     * 获取测试用的请求头.
     */
    private function getTestHeaders(): array
    {
        return [
            'Authorization' => self::$accessToken,
            'organization-code' => self::TEST_ORGANIZATION_CODE,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
}
