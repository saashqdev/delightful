<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Login;

use App\ErrorCode\AuthenticationErrorCode;
use HyperfTest\Cases\Api\AbstractHttpTest;

/**
 * @internal
 * userloginsessionAPItest
 */
class LoginApiTest extends AbstractHttpTest
{
    public const string API = '/api/v1/sessions';

    /**
     * testhandmachinenumberpasswordlogin.
     */
    public function testPhonePasswordLogin(): string
    {
        // constructrequestparameter - handmachinenumberpasswordlogin
        $requestData = [
            'state_code' => '+86',
            'phone' => '13812345678', // testenvironmentmiddlenotexistsinaccountnumber
            'password' => '123456',
            'type' => 'phone_password',
        ];

        // sendPOSTrequest
        $response = $this->json(self::API, $requestData, [
            'User-Agent' => 'PHPUnit Test',
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
            'Connection' => 'keep-alive',
            'cookie' => 'sl-session=UetTdUM44WeDs3Dd1UeJaQ==',
        ]);
        $expectData = [
            'code' => 1000,
            'message' => 'requestsuccess',
            'data' => [
                'access_token' => 'delightful:xxx',
                'bind_phone' => true,
                'is_perfect_password' => false,
                'user_info' => [
                    'id' => 'xxxxx',
                    'real_name' => 'administrator1',
                    'avatar' => 'default_avatar',
                    'description' => '',
                    'position' => '',
                    'mobile' => '13812345678',
                    'state_code' => '+86',
                ],
            ],
        ];
        $this->assertSame(1000, $response['code']);
        $this->assertArrayValueTypesEquals($expectData, $response);

        return $response['data']['access_token'];
    }

    /**
     * testhandmachinenumbernotexistsin.
     */
    public function testPhoneNotExists(): void
    {
        // constructrequestparameter - testhandmachinenumbernotexistsin
        $requestData = [
            'state_code' => '+86',
            'phone' => '19999999999', // useonecertainnotexistsinhandmachinenumber
            'password' => '123456',
            'type' => 'phone_password',
        ];

        // sendPOSTrequest
        $response = $this->json(self::API, $requestData);
        // expecthandmachinenumbernotexistsino clockreturncorrespondingerrorcodeandmessage
        $expectData = [
            'code' => AuthenticationErrorCode::AccountNotFound->value,
        ];

        $this->assertArrayEquals($expectData, $response);
    }

    /**
     * testvalid token verify
     * @depends testPhonePasswordLogin
     */
    public function testValidTokenVerification(string $authorization): void
    {
        $response = $this->json('/api/v1/tokens/verify', [
            'authorization' => $authorization,
            'teamshare_login_code' => '',
        ]);
        $this->assertSame(1000, $response['code']);
        $this->assertArrayValueTypesEquals($response, [
            'code' => 1000,
            'message' => 'ok',
            'data' => [
                [
                    'delightful_id' => '1',
                    'delightful_user_id' => '1',
                    'delightful_organization_code' => '1',
                    'teamshare_organization_code' => '1',
                    'teamshare_user_id' => '1',
                ],
            ],
        ]);
    }

    /**
     * testinvalid token verify
     */
    public function testInvalidTokenVerification(): void
    {
        $requestData = [
            'teamshare_login_code' => '',
            'authorization' => 'delightful:invalid_token',
        ];

        $response = $this->json('/api/v1/tokens/verify', $requestData);

        $expectData = [
            'code' => 3103,
            'message' => 'authorization notlegal',
            'data' => null,
        ];

        $this->assertArrayValueTypesEquals($expectData, $response);
    }
}
