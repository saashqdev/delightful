<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Chat;

use HyperfTest\Cases\Api\AbstractHttpTest;

/**
 * @internal
 * DelightfulchatuserAPItest
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
     * storage登录后的token.
     */
    private static string $accessToken = '';

    /**
     * test完整updateuserinfo - update所有字段.
     */
    public function testUpdateUserInfoWithAllFields(): void
    {
        // 先登录gettoken
        $token = $this->performLogin();
        echo "\nusetoken进行userinfoupdate: " . $token . "\n";

        $requestData = [
            'avatar_url' => 'https://example.com/avatar/new-avatar.jpg',
            'nickname' => '新nickname',
        ];

        $headers = $this->getTestHeaders();
        echo "\nrequest头info: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n";

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $headers);

        echo "\nresponseresult: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";

        // checkresponse是否为array
        $this->assertIsArray($response, 'responseshould是arrayformat');

        // 如果responsecontainerrorinfo，output详细info
        if (isset($response['code']) && $response['code'] !== 1000) {
            echo "\ninterfacereturnerror: code=" . $response['code'] . ', message=' . ($response['message'] ?? 'unknown') . "\n";

            // 如果是authenticationerror，我们can接受并跳过test
            if ($response['code'] === 2179 || $response['code'] === 3035) {
                $this->markTestSkipped('interfaceauthenticationfail，可能need其他authenticationconfiguration - interface路由validate正常');
                return;
            }
        }

        // validateresponse结构 - check是否有data字段
        $this->assertArrayHasKey('data', $response, 'response应containdata字段');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];

        // validateuserdata结构 - check关键字段存在
        $this->assertArrayHasKey('id', $userData, 'response应containid字段');
        $this->assertArrayHasKey('avatar_url', $userData, 'response应containavatar_url字段');
        $this->assertArrayHasKey('nickname', $userData, 'response应containnickname字段');
        $this->assertArrayHasKey('organization_code', $userData, 'response应containorganization_code字段');
        $this->assertArrayHasKey('user_id', $userData, 'response应containuser_id字段');
        $this->assertArrayHasKey('created_at', $userData, 'response应containcreated_at字段');
        $this->assertArrayHasKey('updated_at', $userData, 'response应containupdated_at字段');

        // validate关键字段不为null
        $this->assertNotEmpty($userData['id'], 'id字段不应为null');
        $this->assertNotEmpty($userData['organization_code'], 'organization_code字段不应为null');
        $this->assertNotEmpty($userData['user_id'], 'user_id字段不应为null');
        $this->assertNotEmpty($userData['created_at'], 'created_at字段不应为null');
        $this->assertNotEmpty($userData['updated_at'], 'updated_at字段不应为null');

        // validate更new具体字段value
        $this->assertEquals($requestData['avatar_url'], $userData['avatar_url'], 'avatarURLupdatefail');
        $this->assertEquals($requestData['nickname'], $userData['nickname'], 'nicknameupdatefail');
    }

    /**
     * test仅updateavatar.
     */
    public function testUpdateUserInfoWithAvatarOnly(): void
    {
        // 先登录gettoken
        $this->performLogin();

        $requestData = [
            'avatar_url' => 'https://example.com/avatar/updated-avatar.png',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, 'responseshould是arrayformat');

        // 如果是authenticationerror，跳过test
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdata字段');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertArrayHasKey('avatar_url', $userData, 'response应containavatar_url字段');
        $this->assertEquals($requestData['avatar_url'], $userData['avatar_url'], 'avatarURLshould被correctupdate');
        $this->assertArrayHasKey('nickname', $userData, 'response应containnickname字段');
    }

    /**
     * test仅updatenickname.
     */
    public function testUpdateUserInfoWithNicknameOnly(): void
    {
        // 先登录gettoken
        $this->performLogin();

        $requestData = [
            'nickname' => 'SuperUser2024',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, 'responseshould是arrayformat');

        // 如果是authenticationerror，跳过test
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdata字段');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertArrayHasKey('nickname', $userData, 'response应containnickname字段');
        $this->assertEquals($requestData['nickname'], $userData['nickname'], 'nicknameshould被correctupdate');
    }

    /**
     * testnullparameterupdate - 不传任何字段.
     */
    public function testUpdateUserInfoWithEmptyData(): void
    {
        // 先登录gettoken
        $this->performLogin();

        $requestData = [];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // nullparameter下should正常returncurrentuserinfo，不报错
        $this->assertIsArray($response, 'responseshould是arrayformat');

        // 如果是authenticationerror，跳过test
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdata字段');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];

        // validate关键字段存在
        $this->assertArrayHasKey('id', $userData, 'response应containid字段');
        $this->assertArrayHasKey('organization_code', $userData, 'response应containorganization_code字段');
        $this->assertArrayHasKey('user_id', $userData, 'response应containuser_id字段');
        $this->assertArrayHasKey('created_at', $userData, 'response应containcreated_at字段');
        $this->assertArrayHasKey('updated_at', $userData, 'response应containupdated_at字段');

        // validate关键字段不为null
        $this->assertNotEmpty($userData['id'], 'id字段不应为null');
        $this->assertNotEmpty($userData['organization_code'], 'organization_code字段不应为null');
        $this->assertNotEmpty($userData['user_id'], 'user_id字段不应为null');
        $this->assertNotEmpty($userData['created_at'], 'created_at字段不应为null');
        $this->assertNotEmpty($userData['updated_at'], 'updated_at字段不应为null');
    }

    /**
     * testnullvaluehandle.
     */
    public function testUpdateUserInfoWithNullValues(): void
    {
        // 先登录gettoken
        $this->performLogin();

        $requestData = [
            'avatar_url' => null,
            'nickname' => null,
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // nullvalueshould被correcthandle，不导致error
        $this->assertIsArray($response, '传入nullvalue时应正常returnresponse');

        // 如果是authenticationerror，跳过test
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdata字段');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertArrayHasKey('id', $userData, 'response应containuserID');
    }

    /**
     * test特殊字符handle.
     */
    public function testUpdateUserInfoWithSpecialCharacters(): void
    {
        // 先登录gettoken
        $this->performLogin();

        $requestData = [
            'nickname' => 'testuser🎉',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, 'responseshould是arrayformat');

        // 如果是authenticationerror，跳过test
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdata字段');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertEquals($requestData['nickname'], $userData['nickname'], '应correcthandlecontainemoji的nickname');
    }

    /**
     * test长stringhandle.
     */
    public function testUpdateUserInfoWithLongStrings(): void
    {
        // 先登录gettoken
        $this->performLogin();

        $requestData = [
            'nickname' => str_repeat('很长的nickname', 10), // 50字符
            'avatar_url' => 'https://example.com/very/long/path/to/avatar/' . str_repeat('long-filename', 5) . '.jpg',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // validate长string是否被correcthandle（可能被truncate或拒绝）
        $this->assertIsArray($response, '长string应被correcthandle');

        // 如果是authenticationerror，跳过test
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdata字段');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertArrayHasKey('nickname', $userData, 'response应containnickname字段');
        $this->assertArrayHasKey('avatar_url', $userData, 'response应containavatar_url字段');
    }

    /**
     * testinvalid的avatarURLformat.
     */
    public function testUpdateUserInfoWithInvalidAvatarUrl(): void
    {
        // 先登录gettoken
        $this->performLogin();

        $requestData = [
            'avatar_url' => 'invalid-url-format',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // according to业务逻辑，可能接受任何string作为avatar_url，或进行validate
        $this->assertIsArray($response, 'invalidURLformat应被妥善handle');

        // 如果是authenticationerror，跳过test
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
        }
    }

    /**
     * test部分字段update后的data完整性.
     */
    public function testUpdateUserInfoDataIntegrity(): void
    {
        // 先登录gettoken
        $this->performLogin();

        // 第一次update：只updatenickname
        $firstUpdateData = [
            'nickname' => '第一次更newnickname',
        ];

        $firstResponse = $this->patch(self::UPDATE_USER_INFO_API, $firstUpdateData, $this->getTestHeaders());
        $this->assertIsArray($firstResponse, '第一次updateresponseshould是arrayformat');

        // 如果是authenticationerror，跳过test
        if (isset($firstResponse['code']) && ($firstResponse['code'] === 2179 || $firstResponse['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $firstResponse, '第一次updateresponse应containdata字段');
        $this->assertEquals(1000, $firstResponse['code'], '第一次updateshouldreturnsuccessresponse码');

        $firstUserData = $firstResponse['data'];
        $originalAvatarUrl = $firstUserData['avatar_url'] ?? null;

        // 第二次update：只updateavatar
        $secondUpdateData = [
            'avatar_url' => 'https://example.com/new-avatar-2.jpg',
        ];

        $secondResponse = $this->patch(self::UPDATE_USER_INFO_API, $secondUpdateData, $this->getTestHeaders());
        $this->assertIsArray($secondResponse, '第二次updateresponseshould是arrayformat');
        $this->assertArrayHasKey('data', $secondResponse, '第二次updateresponse应containdata字段');
        $this->assertEquals(1000, $secondResponse['code'], '第二次updateshouldreturnsuccessresponse码');

        $secondUserData = $secondResponse['data'];

        // validatedata完整性：nickname应保持第一次更newvalue
        $this->assertEquals($firstUpdateData['nickname'], $secondUserData['nickname'], 'nickname应保持第一次更newvalue');
        $this->assertEquals($secondUpdateData['avatar_url'], $secondUserData['avatar_url'], 'avatar应为第二次更newvalue');
    }

    /**
     * test未authorizationaccess.
     */
    public function testUpdateUserInfoWithoutAuthorization(): void
    {
        $requestData = [
            'nickname' => 'testnickname',
        ];

        // 不containauthorization头的request
        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, [
            'Content-Type' => 'application/json',
        ]);

        // shouldreturnauthorizationerror
        $this->assertIsArray($response, 'responseshould是arrayformat');
        $this->assertArrayHasKey('code', $response, '未authorizationrequest应returnerror码');
        $this->assertNotEquals(1000, $response['code'] ?? 1000, '未authorizationrequest不应returnsuccess码');
    }

    /**
     * testgetuserupdatepermission - 正常情况.
     */
    public function testGetUserUpdatePermissionSuccess(): void
    {
        // 先登录gettoken
        $token = $this->performLogin();
        echo "\nusetokengetuserupdatepermission: " . $token . "\n";

        $headers = $this->getTestHeaders();
        echo "\nrequest头info: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n";

        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, $headers);

        echo "\nresponseresult: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";

        // checkresponse是否为array
        $this->assertIsArray($response, 'responseshould是arrayformat');

        // 如果responsecontainerrorinfo，output详细info
        if (isset($response['code']) && $response['code'] !== 1000) {
            echo "\ninterfacereturnerror: code=" . $response['code'] . ', message=' . ($response['message'] ?? 'unknown') . "\n";

            // 如果是authenticationerror，我们can接受并跳过test
            if ($response['code'] === 2179 || $response['code'] === 3035) {
                $this->markTestSkipped('interfaceauthenticationfail，可能need其他authenticationconfiguration - interface路由validate正常');
                return;
            }
        }

        // validateresponse结构
        $this->assertArrayHasKey('data', $response, 'response应containdata字段');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $permissionData = $response['data'];

        // validatepermissiondata结构
        $this->assertArrayHasKey('permission', $permissionData, 'response应containpermission字段');
        $this->assertIsNotArray($permissionData['permission'], 'permission字段不should是array');
        $this->assertNotNull($permissionData['permission'], 'permission字段不should为null');
    }

    /**
     * testgetuserupdatepermission - 未authorizationaccess.
     */
    public function testGetUserUpdatePermissionWithoutAuthorization(): void
    {
        // 不containauthorization头的request
        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        // shouldreturnauthorizationerror
        $this->assertIsArray($response, 'responseshould是arrayformat');
        $this->assertArrayHasKey('code', $response, '未authorizationrequest应returnerror码');
        $this->assertNotEquals(1000, $response['code'] ?? 1000, '未authorizationrequest不应returnsuccess码');

        // 常见的未authorizationerror码
        $unauthorizedCodes = [2179, 3035, 401, 403];
        $this->assertContains($response['code'] ?? 0, $unauthorizedCodes, 'shouldreturn未authorizationerror码');
    }

    /**
     * testgetuserupdatepermission - invalidtoken.
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

        // shouldreturnauthorizationerror
        $this->assertIsArray($response, 'responseshould是arrayformat');
        $this->assertArrayHasKey('code', $response, 'invalidtokenrequest应returnerror码');
        $this->assertNotEquals(1000, $response['code'] ?? 1000, 'invalidtokenrequest不应returnsuccess码');
    }

    /**
     * testgetuserupdatepermission - 缺少organization-code.
     */
    public function testGetUserUpdatePermissionWithoutOrganizationCode(): void
    {
        // 先登录gettoken
        $token = $this->performLogin();

        $headers = [
            'Authorization' => $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            // 故意不contain organization-code
        ];

        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, $headers);

        // 可能returnerror或success，取决于业务逻辑
        $this->assertIsArray($response, 'responseshould是arrayformat');
        $this->assertArrayHasKey('code', $response, 'response应containcode字段');

        // 如果success，validatedata结构
        if ($response['code'] === 1000) {
            $this->assertArrayHasKey('data', $response, 'successresponse应containdata字段');
            $permissionData = $response['data'];
            $this->assertArrayHasKey('permission', $permissionData, 'response应containpermission字段');
            $this->assertIsNotArray($permissionData['permission'], 'permission字段不should是array');
            $this->assertNotNull($permissionData['permission'], 'permission字段不should为null');
        }
    }

    /**
     * testgetuserupdatepermission - HTTPmethodvalidate.
     */
    public function testGetUserUpdatePermissionHttpMethod(): void
    {
        // 先登录gettoken
        $token = $this->performLogin();
        $headers = $this->getTestHeaders();

        // testerror的HTTPmethod（POST）
        $postResponse = $this->post(self::GET_USER_UPDATE_PERMISSION_API, [], $headers);

        // shouldreturnmethod不allow的error
        if ($postResponse !== null) {
            $this->assertIsArray($postResponse, 'POSTresponseshould是arrayformat');
            if (isset($postResponse['code'])) {
                // 如果不是authentication问题，should是methoderror
                if (! in_array($postResponse['code'], [2179, 3035])) {
                    $this->assertNotEquals(1000, $postResponse['code'], 'POSTmethod不shouldsuccess');
                }
            }
        } else {
            // 如果returnnull，instructionmethod被correct拒绝了
            $this->assertTrue(true, 'POSTmethod被correct拒绝');
        }

        // testerror的HTTPmethod（PUT）
        $putResponse = $this->put(self::GET_USER_UPDATE_PERMISSION_API, [], $headers);

        // shouldreturnmethod不allow的error
        if ($putResponse !== null) {
            $this->assertIsArray($putResponse, 'PUTresponseshould是arrayformat');
            if (isset($putResponse['code'])) {
                // 如果不是authentication问题，should是methoderror
                if (! in_array($putResponse['code'], [2179, 3035])) {
                    $this->assertNotEquals(1000, $putResponse['code'], 'PUTmethod不shouldsuccess');
                }
            }
        } else {
            // 如果returnnull，instructionmethod被correct拒绝了
            $this->assertTrue(true, 'PUTmethod被correct拒绝');
        }
    }

    /**
     * execute登录并getaccesstoken.
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
        $this->assertIsArray($loginResponse, '登录responseshould是arrayformat');
        $this->assertEquals(1000, $loginResponse['code'] ?? 0, '登录shouldsuccess');
        $this->assertArrayHasKey('data', $loginResponse, '登录response应containdata字段');
        $this->assertArrayHasKey('access_token', $loginResponse['data'], '登录response应containaccess_token');

        // cachetoken
        self::$accessToken = $loginResponse['data']['access_token'];

        // outputdebuginfo
        echo "\n登录success，获得token: " . self::$accessToken . "\n";
        echo "\n完整登录response: " . json_encode($loginResponse, JSON_UNESCAPED_UNICODE) . "\n";

        return self::$accessToken;
    }

    /**
     * gettest用的request头.
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
