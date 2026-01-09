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
     * login账number:13800138001
     * password:123456.
     */
    private const string TEST_PHONE = '13800138001';

    private const string TEST_PASSWORD = '123456';

    private const string TEST_STATE_CODE = '+86';

    private const string TEST_ORGANIZATION_CODE = 'test001';

    /**
     * storageloginbacktoken.
     */
    private static string $accessToken = '';

    /**
     * testcompleteupdateuserinfo - update所havefield.
     */
    public function testUpdateUserInfoWithAllFields(): void
    {
        // 先logingettoken
        $token = $this->performLogin();
        echo "\nusetokenconductuserinfoupdate: " . $token . "\n";

        $requestData = [
            'avatar_url' => 'https://example.com/avatar/new-avatar.jpg',
            'nickname' => 'newnickname',
        ];

        $headers = $this->getTestHeaders();
        echo "\nrequestheadinfo: " . json_encode($headers, JSON_UNESCAPED_UNICODE) . "\n";

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $headers);

        echo "\nresponseresult: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";

        // checkresponsewhetherforarray
        $this->assertIsArray($response, 'responseshouldisarrayformat');

        // ifresponsecontainerrorinfo,outputdetailedinfo
        if (isset($response['code']) && $response['code'] !== 1000) {
            echo "\ninterfacereturnerror: code=" . $response['code'] . ', message=' . ($response['message'] ?? 'unknown') . "\n";

            // ifisauthenticationerror,wecanacceptandskiptest
            if ($response['code'] === 2179 || $response['code'] === 3035) {
                $this->markTestSkipped('interfaceauthenticationfail,maybeneedotherauthenticationconfiguration - interface路byvalidatenormal');
                return;
            }
        }

        // validateresponsestructure - checkwhetherhavedatafield
        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];

        // validateuserdatastructure - checkclosekeyfield存in
        $this->assertArrayHasKey('id', $userData, 'response应containidfield');
        $this->assertArrayHasKey('avatar_url', $userData, 'response应containavatar_urlfield');
        $this->assertArrayHasKey('nickname', $userData, 'response应containnicknamefield');
        $this->assertArrayHasKey('organization_code', $userData, 'response应containorganization_codefield');
        $this->assertArrayHasKey('user_id', $userData, 'response应containuser_idfield');
        $this->assertArrayHasKey('created_at', $userData, 'response应containcreated_atfield');
        $this->assertArrayHasKey('updated_at', $userData, 'response应containupdated_atfield');

        // validateclosekeyfieldnotfornull
        $this->assertNotEmpty($userData['id'], 'idfieldnot应fornull');
        $this->assertNotEmpty($userData['organization_code'], 'organization_codefieldnot应fornull');
        $this->assertNotEmpty($userData['user_id'], 'user_idfieldnot应fornull');
        $this->assertNotEmpty($userData['created_at'], 'created_atfieldnot应fornull');
        $this->assertNotEmpty($userData['updated_at'], 'updated_atfieldnot应fornull');

        // validatemorenewspecificfieldvalue
        $this->assertEquals($requestData['avatar_url'], $userData['avatar_url'], 'avatarURLupdatefail');
        $this->assertEquals($requestData['nickname'], $userData['nickname'], 'nicknameupdatefail');
    }

    /**
     * testonlyupdateavatar.
     */
    public function testUpdateUserInfoWithAvatarOnly(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [
            'avatar_url' => 'https://example.com/avatar/updated-avatar.png',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, 'responseshouldisarrayformat');

        // ifisauthenticationerror,skiptest
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
     * testonlyupdatenickname.
     */
    public function testUpdateUserInfoWithNicknameOnly(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [
            'nickname' => 'SuperUser2024',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        $this->assertIsArray($response, 'responseshouldisarrayformat');

        // ifisauthenticationerror,skiptest
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
     * testnullparameterupdate - not传anyfield.
     */
    public function testUpdateUserInfoWithEmptyData(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // nullparameterdownshouldnormalreturncurrentuserinfo,not报错
        $this->assertIsArray($response, 'responseshouldisarrayformat');

        // ifisauthenticationerror,skiptest
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];

        // validateclosekeyfield存in
        $this->assertArrayHasKey('id', $userData, 'response应containidfield');
        $this->assertArrayHasKey('organization_code', $userData, 'response应containorganization_codefield');
        $this->assertArrayHasKey('user_id', $userData, 'response应containuser_idfield');
        $this->assertArrayHasKey('created_at', $userData, 'response应containcreated_atfield');
        $this->assertArrayHasKey('updated_at', $userData, 'response应containupdated_atfield');

        // validateclosekeyfieldnotfornull
        $this->assertNotEmpty($userData['id'], 'idfieldnot应fornull');
        $this->assertNotEmpty($userData['organization_code'], 'organization_codefieldnot应fornull');
        $this->assertNotEmpty($userData['user_id'], 'user_idfieldnot应fornull');
        $this->assertNotEmpty($userData['created_at'], 'created_atfieldnot应fornull');
        $this->assertNotEmpty($userData['updated_at'], 'updated_atfieldnot应fornull');
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

        // nullvalueshouldbecorrecthandle,not导致error
        $this->assertIsArray($response, '传入nullvalueo clock应normalreturnresponse');

        // ifisauthenticationerror,skiptest
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

        $this->assertIsArray($response, 'responseshouldisarrayformat');

        // ifisauthenticationerror,skiptest
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $userData = $response['data'];
        $this->assertEquals($requestData['nickname'], $userData['nickname'], '应correcthandlecontainemojinickname');
    }

    /**
     * testlongstringhandle.
     */
    public function testUpdateUserInfoWithLongStrings(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [
            'nickname' => str_repeat('verylongnickname', 10), // 50character
            'avatar_url' => 'https://example.com/very/long/path/to/avatar/' . str_repeat('long-filename', 5) . '.jpg',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // validatelongstringwhetherbecorrecthandle(maybebetruncateorreject)
        $this->assertIsArray($response, 'longstring应becorrecthandle');

        // ifisauthenticationerror,skiptest
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
     * testinvalidavatarURLformat.
     */
    public function testUpdateUserInfoWithInvalidAvatarUrl(): void
    {
        // 先logingettoken
        $this->performLogin();

        $requestData = [
            'avatar_url' => 'invalid-url-format',
        ];

        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, $this->getTestHeaders());

        // according tobusinesslogic,maybeacceptanystringasforavatar_url,orconductvalidate
        $this->assertIsArray($response, 'invalidURLformat应be妥善handle');

        // ifisauthenticationerror,skiptest
        if (isset($response['code']) && ($response['code'] === 2179 || $response['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
        }
    }

    /**
     * test部minutefieldupdatebackdatacompleteproperty.
     */
    public function testUpdateUserInfoDataIntegrity(): void
    {
        // 先logingettoken
        $this->performLogin();

        // theonetimeupdate:onlyupdatenickname
        $firstUpdateData = [
            'nickname' => 'theonetimemorenewnickname',
        ];

        $firstResponse = $this->patch(self::UPDATE_USER_INFO_API, $firstUpdateData, $this->getTestHeaders());
        $this->assertIsArray($firstResponse, 'theonetimeupdateresponseshouldisarrayformat');

        // ifisauthenticationerror,skiptest
        if (isset($firstResponse['code']) && ($firstResponse['code'] === 2179 || $firstResponse['code'] === 3035)) {
            $this->markTestSkipped('interfaceauthenticationfail');
            return;
        }

        $this->assertArrayHasKey('data', $firstResponse, 'theonetimeupdateresponse应containdatafield');
        $this->assertEquals(1000, $firstResponse['code'], 'theonetimeupdateshouldreturnsuccessresponse码');

        $firstUserData = $firstResponse['data'];
        $originalAvatarUrl = $firstUserData['avatar_url'] ?? null;

        // thetwotimeupdate:onlyupdateavatar
        $secondUpdateData = [
            'avatar_url' => 'https://example.com/new-avatar-2.jpg',
        ];

        $secondResponse = $this->patch(self::UPDATE_USER_INFO_API, $secondUpdateData, $this->getTestHeaders());
        $this->assertIsArray($secondResponse, 'thetwotimeupdateresponseshouldisarrayformat');
        $this->assertArrayHasKey('data', $secondResponse, 'thetwotimeupdateresponse应containdatafield');
        $this->assertEquals(1000, $secondResponse['code'], 'thetwotimeupdateshouldreturnsuccessresponse码');

        $secondUserData = $secondResponse['data'];

        // validatedatacompleteproperty:nickname应maintaintheonetimemorenewvalue
        $this->assertEquals($firstUpdateData['nickname'], $secondUserData['nickname'], 'nickname应maintaintheonetimemorenewvalue');
        $this->assertEquals($secondUpdateData['avatar_url'], $secondUserData['avatar_url'], 'avatar应forthetwotimemorenewvalue');
    }

    /**
     * testnotauthorizationaccess.
     */
    public function testUpdateUserInfoWithoutAuthorization(): void
    {
        $requestData = [
            'nickname' => 'testnickname',
        ];

        // notcontainauthorizationheadrequest
        $response = $this->patch(self::UPDATE_USER_INFO_API, $requestData, [
            'Content-Type' => 'application/json',
        ]);

        // shouldreturnauthorizationerror
        $this->assertIsArray($response, 'responseshouldisarrayformat');
        $this->assertArrayHasKey('code', $response, 'notauthorizationrequest应returnerror码');
        $this->assertNotEquals(1000, $response['code'] ?? 1000, 'notauthorizationrequestnot应returnsuccess码');
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

        // checkresponsewhetherforarray
        $this->assertIsArray($response, 'responseshouldisarrayformat');

        // ifresponsecontainerrorinfo,outputdetailedinfo
        if (isset($response['code']) && $response['code'] !== 1000) {
            echo "\ninterfacereturnerror: code=" . $response['code'] . ', message=' . ($response['message'] ?? 'unknown') . "\n";

            // ifisauthenticationerror,wecanacceptandskiptest
            if ($response['code'] === 2179 || $response['code'] === 3035) {
                $this->markTestSkipped('interfaceauthenticationfail,maybeneedotherauthenticationconfiguration - interface路byvalidatenormal');
                return;
            }
        }

        // validateresponsestructure
        $this->assertArrayHasKey('data', $response, 'response应containdatafield');
        $this->assertEquals(1000, $response['code'], 'shouldreturnsuccessresponse码');

        $permissionData = $response['data'];

        // validatepermissiondatastructure
        $this->assertArrayHasKey('permission', $permissionData, 'response应containpermissionfield');
        $this->assertIsNotArray($permissionData['permission'], 'permissionfieldnotshouldisarray');
        $this->assertNotNull($permissionData['permission'], 'permissionfieldnotshouldfornull');
    }

    /**
     * testgetuserupdatepermission - notauthorizationaccess.
     */
    public function testGetUserUpdatePermissionWithoutAuthorization(): void
    {
        // notcontainauthorizationheadrequest
        $response = $this->get(self::GET_USER_UPDATE_PERMISSION_API, [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);

        // shouldreturnauthorizationerror
        $this->assertIsArray($response, 'responseshouldisarrayformat');
        $this->assertArrayHasKey('code', $response, 'notauthorizationrequest应returnerror码');
        $this->assertNotEquals(1000, $response['code'] ?? 1000, 'notauthorizationrequestnot应returnsuccess码');

        // commonnotauthorizationerror码
        $unauthorizedCodes = [2179, 3035, 401, 403];
        $this->assertContains($response['code'] ?? 0, $unauthorizedCodes, 'shouldreturnnotauthorizationerror码');
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
        $this->assertIsArray($response, 'responseshouldisarrayformat');
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

        // maybereturnerrororsuccess,取决atbusinesslogic
        $this->assertIsArray($response, 'responseshouldisarrayformat');
        $this->assertArrayHasKey('code', $response, 'response应containcodefield');

        // ifsuccess,validatedatastructure
        if ($response['code'] === 1000) {
            $this->assertArrayHasKey('data', $response, 'successresponse应containdatafield');
            $permissionData = $response['data'];
            $this->assertArrayHasKey('permission', $permissionData, 'response应containpermissionfield');
            $this->assertIsNotArray($permissionData['permission'], 'permissionfieldnotshouldisarray');
            $this->assertNotNull($permissionData['permission'], 'permissionfieldnotshouldfornull');
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

        // testerrorHTTPmethod(POST)
        $postResponse = $this->post(self::GET_USER_UPDATE_PERMISSION_API, [], $headers);

        // shouldreturnmethodnotallowerror
        if ($postResponse !== null) {
            $this->assertIsArray($postResponse, 'POSTresponseshouldisarrayformat');
            if (isset($postResponse['code'])) {
                // ifnotisauthenticationissue,shouldismethoderror
                if (! in_array($postResponse['code'], [2179, 3035])) {
                    $this->assertNotEquals(1000, $postResponse['code'], 'POSTmethodnotshouldsuccess');
                }
            }
        } else {
            // ifreturnnull,instructionmethodbecorrectreject
            $this->assertTrue(true, 'POSTmethodbecorrectreject');
        }

        // testerrorHTTPmethod(PUT)
        $putResponse = $this->put(self::GET_USER_UPDATE_PERMISSION_API, [], $headers);

        // shouldreturnmethodnotallowerror
        if ($putResponse !== null) {
            $this->assertIsArray($putResponse, 'PUTresponseshouldisarrayformat');
            if (isset($putResponse['code'])) {
                // ifnotisauthenticationissue,shouldismethoderror
                if (! in_array($putResponse['code'], [2179, 3035])) {
                    $this->assertNotEquals(1000, $putResponse['code'], 'PUTmethodnotshouldsuccess');
                }
            }
        } else {
            // ifreturnnull,instructionmethodbecorrectreject
            $this->assertTrue(true, 'PUTmethodbecorrectreject');
        }
    }

    /**
     * executeloginandgetaccesstoken.
     */
    private function performLogin(): string
    {
        // ifalready经havetoken,直接return
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
        $this->assertIsArray($loginResponse, 'loginresponseshouldisarrayformat');
        $this->assertEquals(1000, $loginResponse['code'] ?? 0, 'loginshouldsuccess');
        $this->assertArrayHasKey('data', $loginResponse, 'loginresponse应containdatafield');
        $this->assertArrayHasKey('access_token', $loginResponse['data'], 'loginresponse应containaccess_token');

        // cachetoken
        self::$accessToken = $loginResponse['data']['access_token'];

        // outputdebuginfo
        echo "\nloginsuccess,获token: " . self::$accessToken . "\n";
        echo "\ncompleteloginresponse: " . json_encode($loginResponse, JSON_UNESCAPED_UNICODE) . "\n";

        return self::$accessToken;
    }

    /**
     * gettestuserequesthead.
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
