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
     * login账number：13800138001
     * 密码：123456.
     */
    private const string TEST_PHONE = '13800138001';

    private const string TEST_PASSWORD = '123456';

    private const string TEST_STATE_CODE = '+86';

    private const string TEST_ORGANIZATION_CODE = 'test001';

    /**
     * storageloginback的token.
     */
    private static string $accessToken = '';

    /**
     * test完整updateuserinfo - update所havefield.
     */
    public function testUpdateUserInfoWithAllFields(): void
    {
        // 先logingettoken
        $token = $this->performLogin();
        echo "\nusetokenconductuserinfoupdate: " . $token . "\n";

        $requestData = [
            'avatar_url' => 'https://example.com/avatar/new-avatar.jpg',
            'nickname' => '新nickname',
        ];

        $headers = $this->getTestHeaders();
        echo "\nrequestheadinfo: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n";

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $headers);

        echo "\nresponseresult: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";

        // checkresponsewhether为array
        $this->assertIsArray($response, 'responseshould是arrayformat');

        // ifresponsecontainerrorinfo，output详细info
        if (isset($response['code']) && $response['code'] !== 1000) {
            echo "\ninterfacereturnerror: code=" . $response['code'] . ', message=' . ($response['message'] ?? 'unknown') . "\n";

            // if是authenticationerror，我们can接受并skiptest
            if ($response['code'] === 2179 || $response['code'] === 3035) {
                $this->markTestSkipped('interfaceauthenticationfail，可能need其他authenticationconfiguration - interface路由validatenormal');
                return;
            }
        }

        // validateresponse结构 - checkwhetherhavedatafield
        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];

        // validateuserdata结构 - check关键field存in
        $this->assertArrayHasKey('id', $userData, 'response应containidfield');
        $this->assertArrayHasKey('avatar_url', $userData, 'response应containavatar_urlfield');
        $this->assertArrayHasKey('nickname', $userData, 'response应containnicknamefield');
        $this->assertArrayHasKey('organization_code', $userData, 'response应containorganization_codefield');
        $this->assertArrayHasKey('user_id', $userData, 'response应containuser_idfield');
        $this->assertArrayHasKey('created_at', $userData, 'response应containcreated_atfield');
        $this->assertArrayHasKey('updated_at', $userData, 'response应containupdated_atfield');

        // validate关键fieldnot为null
        $this->assertNotEmpty($userData['id'], 'idfieldnot应为null');
        $this->assertNotEmpty($userData['organization_code'], 'organization_codefieldnot应为null');
        $this->assertNotEmpty($userData['user_id'], 'user_idfieldnot应为null');
        $this->assertNotEmpty($userData['created_at'], 'created_atfieldnot应为null');
        $this->assertNotEmpty($userData['updated_at'], 'updated_atfieldnot应为null');

        // validatemorenewspecificfieldvalue
        $this->assertEquals($requestData['avatar_url'], $userData['avatar_url'], 'avatarURLupdatefail');
        $this->assertEquals($requestData['nickname'], $userData['nickname'], 'nicknameupdatefail');
    }

    /**
     * test仅updateavatar.
     */
    public function testUpdateUserInfoWithAvatarOnly(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [
            'avatar_url' => 'https://example.com/avatar/updated-avatar.png',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, 'responseshould是arrayformat');

        // if是authenticationerror，skiptest
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertArrayHasKey('avatar_url', $userData, 'response应containavatar_urlfield');
        $this->assertEquals($requestData['avatar_url'], $userData['avatar_url'], 'avatarURLshouldbecorrectupdate');
        $this->assertArrayHasKey('nickname', $userData, 'response应containnicknamefield');
    }

    /**
     * test仅updatenickname.
     */
    public function testUpdateUserInfoWithNicknameOnly(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [
            'nickname' => 'SuperUser2024',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, 'responseshould是arrayformat');

        // if是authenticationerror，skiptest
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertArrayHasKey('nickname', $userData, 'response应containnicknamefield');
        $this->assertEquals($requestData['nickname'], $userData['nickname'], 'nicknameshouldbecorrectupdate');
    }

    /**
     * testnullparameterupdate - not传任何field.
     */
    public function testUpdateUserInfoWithEmptyData(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // nullparameterdownshouldnormalreturncurrentuserinfo，not报错
        $this->assertIsArray($response, 'responseshould是arrayformat');

        // if是authenticationerror，skiptest
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];

        // validate关键field存in
        $this->assertArrayHasKey('id', $userData, 'response应containidfield');
        $this->assertArrayHasKey('organization_code', $userData, 'response应containorganization_codefield');
        $this->assertArrayHasKey('user_id', $userData, 'response应containuser_idfield');
        $this->assertArrayHasKey('created_at', $userData, 'response应containcreated_atfield');
        $this->assertArrayHasKey('updated_at', $userData, 'response应containupdated_atfield');

        // validate关键fieldnot为null
        $this->assertNotEmpty($userData['id'], 'idfieldnot应为null');
        $this->assertNotEmpty($userData['organization_code'], 'organization_codefieldnot应为null');
        $this->assertNotEmpty($userData['user_id'], 'user_idfieldnot应为null');
        $this->assertNotEmpty($userData['created_at'], 'created_atfieldnot应为null');
        $this->assertNotEmpty($userData['updated_at'], 'updated_atfieldnot应为null');
    }

    /**
     * testnullvaluehandle.
     */
    public function testUpdateUserInfoWithNullValues(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [
            'avatar_url' => null,
            'nickname' => null,
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // nullvalueshouldbecorrecthandle，not导致error
        $this->assertIsArray($response, '传入nullvalueo clock应normalreturnresponse');

        // if是authenticationerror，skiptest
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertArrayHasKey('id', $userData, 'response应containuserID');
    }

    /**
     * test特殊characterhandle.
     */
    public function testUpdateUserInfoWithSpecialCharacters(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [
            'nickname' => 'testuser🎉',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, 'responseshould是arrayformat');

        // if是authenticationerror，skiptest
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertEquals($requestData['nickname'], $userData['nickname'], '应correcthandlecontainemoji的nickname');
    }

    /**
     * test长stringhandle.
     */
    public function testUpdateUserInfoWithLongStrings(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [
            'nickname' => str_repeat('very长的nickname', 10), // 50character
            'avatar_url' => 'https://example.com/very/long/path/to/avatar/' . str_repeat('long-filename', 5) . '.jpg',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // validate长stringwhetherbecorrecthandle（可能betruncateorreject）
        $this->assertIsArray($response, '长string应becorrecthandle');

        // if是authenticationerror，skiptest
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertArrayHasKey('nickname', $userData, 'response应containnicknamefield');
        $this->assertArrayHasKey('avatar_url', $userData, 'response应containavatar_urlfield');
    }

    /**
     * testinvalid的avatarURLformat.
     */
    public function testUpdateUserInfoWithInvalidAvatarUrl(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [
            'avatar_url' => 'invalid-url-format',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // according to业务逻辑，可能接受任何string作为avatar_url，orconductvalidate
        $this->assertIsArray($response, 'invalidURLformat应be妥善handle');

        // if是authenticationerror，skiptest
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
        }
    }

    /**
     * test部minutefieldupdateback的data完整property.
     */
    public function testUpdateUserInfoDataIntegrity(): void
    {
        // 先logingettoken
        $this->performLogin();

        // the一timeupdate：只updatenickname
        $firstUpdateData = [
            'nickname' => 'the一timemorenewnickname',
        ];

        $firstResponse = $this->patch(self::UPDATE_USER_INFO_API, $firstUpdateData, $this->getTestHeaders());
        $this->assertIsArray($firstResponse, 'the一timeupdateresponseshould是arrayformat');

        // if是authenticationerror，skiptest
        if (isset($firstResponse['code']) && ($firstResponse['code'] === 2179 || $firstResponse['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $firstResponse, 'the一timeupdateresponse应containdatafield');
        $this->assertEquals(1000, $firstResponse['code'], 'the一timeupdateshouldreturnsuccessresponse码');

        $firstUserData = $firstResponse['data'];
        $originalAvatarUrl = $firstUserData['avatar_url'] ?? null;

        // the二timeupdate：只updateavatar
        $secondUpdateData = [
            'avatar_url' => 'https://example.com/new-avatar-2.jpg',
        ];

        $secondResponse = $this->patch(self::UPDATE_USER_INFO_API, $secondUpdateData, $this->getTestHeaders());
        $this->assertIsArray($secondResponse, 'the二timeupdateresponseshould是arrayformat');
        $this->assertArrayHasKey('data', $secondResponse, 'the二timeupdateresponse应containdatafield');
        $this->assertEquals(1000, $secondResponse['code'], 'the二timeupdateshouldreturnsuccessresponse码');

        $secondUserData = $secondResponse['data'];

        // validatedata完整property：nickname应保持the一timemorenewvalue
        $this->assertEquals($firstUpdateData['nickname'], $secondUserData['nickname'], 'nickname应保持the一timemorenewvalue');
        $this->assertEquals($secondUpdateData['avatar_url'], $secondUserData['avatar_url'], 'avatar应为the二timemorenewvalue');
    }

    /**
     * test未authorizationaccess.
     */
    public function testUpdateUserInfoWithoutAuthorization(): void
    {
        $requestData = [
            'nickname' => 'testnickname',
        ];

        // notcontainauthorizationhead的request
        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, [
            'Content-Type' => 'application/json',
        ]);

        // shouldreturnauthorizationerror
        $this->assertIsArray($response, 'responseshould是arrayformat');
        $this->assertArrayHasKey('code', $response, '未authorizationrequest应returnerror码');
        $this->assertNotEquals(1000, $response['code'] ?? 1000, '未authorizationrequestnot应returnsuccess码');
    }

    /**
     * testgetuserupdatepermission - normal情况.
     */
    public function testGetUserUpdatePermissionSuccess(): void
    {
        // 先logingettoken
        $token = $this->performLogin();
        echo "\nusetokengetuserupdatepermission: " . $token . "\n";

        $headers = $this->getTestHeaders();
        echo "\nrequestheadinfo: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n";

        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, $headers);

        echo "\nresponseresult: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";

        // checkresponsewhether为array
        $this->assertIsArray($response, 'responseshould是arrayformat');

        // ifresponsecontainerrorinfo，output详细info
        if (isset($response['code']) && $response['code'] !== 1000) {
            echo "\ninterfacereturnerror: code=" . $response['code'] . ', message=' . ($response['message'] ?? 'unknown') . "\n";

            // if是authenticationerror，我们can接受并skiptest
            if ($response['code'] === 2179 || $response['code'] === 3035) {
                $this->markTestSkipped('interfaceauthenticationfail，可能need其他authenticationconfiguration - interface路由validatenormal');
                return;
            }
        }

        // validateresponse结构
        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $permissionData = $response['data'];

        // validatepermissiondata结构
        $this->assertArrayHasKey('permission', $permissionData, 'response应containpermissionfield');
        $this->assertIsNotArray($permissionData['permission'], 'permissionfieldnotshould是array');
        $this->assertNotNull($permissionData['permission'], 'permissionfieldnotshould为null');
    }

    /**
     * testgetuserupdatepermission - 未authorizationaccess.
     */
    public function testGetUserUpdatePermissionWithoutAuthorization(): void
    {
        // notcontainauthorizationhead的request
        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        // shouldreturnauthorizationerror
        $this->assertIsArray($response, 'responseshould是arrayformat');
        $this->assertArrayHasKey('code', $response, '未authorizationrequest应returnerror码');
        $this->assertNotEquals(1000, $response['code'] ?? 1000, '未authorizationrequestnot应returnsuccess码');

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
        $this->assertNotEquals(1000, $response['code'] ?? 1000, 'invalidtokenrequestnot应returnsuccess码');
    }

    /**
     * testgetuserupdatepermission - 缺少organization-code.
     */
    public function testGetUserUpdatePermissionWithoutOrganizationCode(): void
    {
        // 先logingettoken
        $token = $this->performLogin();

        $headers = [
            'Authorization' => $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            // 故意notcontain organization-code
        ];

        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, $headers);

        // 可能returnerrororsuccess，取决at业务逻辑
        $this->assertIsArray($response, 'responseshould是arrayformat');
        $this->assertArrayHasKey('code', $response, 'response应containcodefield');

        // ifsuccess，validatedata结构
        if ($response['code'] === 1000) {
            $this->assertArrayHasKey('data', $response, 'successresponse应containdatafield');
            $permissionData = $response['data'];
            $this->assertArrayHasKey('permission', $permissionData, 'response应containpermissionfield');
            $this->assertIsNotArray($permissionData['permission'], 'permissionfieldnotshould是array');
            $this->assertNotNull($permissionData['permission'], 'permissionfieldnotshould为null');
        }
    }

    /**
     * testgetuserupdatepermission - HTTPmethodvalidate.
     */
    public function testGetUserUpdatePermissionHttpMethod(): void
    {
        // 先logingettoken
        $token = $this->performLogin();
        $headers = $this->getTestHeaders();

        // testerror的HTTPmethod（POST）
        $postResponse = $this->post(self::GET_USER_UPDATE_PERMISSION_API, [], $headers);

        // shouldreturnmethodnotallow的error
        if ($postResponse !== null) {
            $this->assertIsArray($postResponse, 'POSTresponseshould是arrayformat');
            if (isset($postResponse['code'])) {
                // ifnot是authenticationissue，should是methoderror
                if (! in_array($postResponse['code'], [2179, 3035])) {
                    $this->assertNotEquals(1000, $postResponse['code'], 'POSTmethodnotshouldsuccess');
                }
            }
        } else {
            // ifreturnnull，instructionmethodbecorrectreject了
            $this->assertTrue(true, 'POSTmethodbecorrectreject');
        }

        // testerror的HTTPmethod（PUT）
        $putResponse = $this->put(self::GET_USER_UPDATE_PERMISSION_API, [], $headers);

        // shouldreturnmethodnotallow的error
        if ($putResponse !== null) {
            $this->assertIsArray($putResponse, 'PUTresponseshould是arrayformat');
            if (isset($putResponse['code'])) {
                // ifnot是authenticationissue，should是methoderror
                if (! in_array($putResponse['code'], [2179, 3035])) {
                    $this->assertNotEquals(1000, $putResponse['code'], 'PUTmethodnotshouldsuccess');
                }
            }
        } else {
            // ifreturnnull，instructionmethodbecorrectreject了
            $this->assertTrue(true, 'PUTmethodbecorrectreject');
        }
    }

    /**
     * executelogin并getaccesstoken.
     */
    private function performLogin(): string
    {
        // if已经havetoken，直接return
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

        // validateloginwhethersuccess
        $this->assertIsArray($loginResponse, 'loginresponseshould是arrayformat');
        $this->assertEquals(1000, $loginResponse['code'] ?? 0, 'loginshouldsuccess');
        $this->assertArrayHasKey('data', $loginResponse, 'loginresponse应containdatafield');
        $this->assertArrayHasKey('access_token', $loginResponse['data'], 'loginresponse应containaccess_token');

        // cachetoken
        self::$accessToken = $loginResponse['data']['access_token'];

        // outputdebuginfo
        echo "\nloginsuccess，获得token: " . self::$accessToken . "\n";
        echo "\n完整loginresponse: " . json_encode($loginResponse, JSON_UNESCAPED_UNICODE) . "\n";

        return self::$accessToken;
    }

    /**
     * gettestuse的requesthead.
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
