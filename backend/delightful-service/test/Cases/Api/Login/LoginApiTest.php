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
 * user登录sessionAPItest
 */
class LoginApiTest extends AbstractHttpTest
{
    public const string API = '/api/v1/sessions';

    /**
     * test手机号密码登录.
     */
    public function testPhonePasswordLogin(): string
    {
        // 构造请求parameter - 手机号密码登录
        $requestData = [
            'state_code' => '+86',
            'phone' => '13812345678', // test环境中不存在的账号
            'password' => '123456',
            'type' => 'phone_password',
        ];

        // 发送POST请求
        $response = $this->json(self::API, $requestData, [
            'User-Agent' => 'PHPUnit Test',
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
            'Connection' => 'keep-alive',
            'Cookie' => 'sl-session=UetTdUM44WeDs3Dd1UeJaQ==',
        ]);
        $expectData = [
            'code' => 1000,
            'message' => '请求success',
            'data' => [
                'access_token' => 'delightful:xxx',
                'bind_phone' => true,
                'is_perfect_password' => false,
                'user_info' => [
                    'id' => 'xxxxx',
                    'real_name' => '管理员1',
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
     * test手机号不存在.
     */
    public function testPhoneNotExists(): void
    {
        // 构造请求parameter - test手机号不存在
        $requestData = [
            'state_code' => '+86',
            'phone' => '19999999999', // use一个确定不存在的手机号
            'password' => '123456',
            'type' => 'phone_password',
        ];

        // 发送POST请求
        $response = $this->json(self::API, $requestData);
        // expect手机号不存在时return相应的error码和message
        $expectData = [
            'code' => AuthenticationErrorCode::AccountNotFound->value,
        ];

        $this->assertArrayEquals($expectData, $response);
    }

    /**
     * testvalid的 token verify
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
     * testinvalid的 token verify
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
            'message' => 'authorization 不legal',
            'data' => null,
        ];

        $this->assertArrayValueTypesEquals($expectData, $response);
    }
}
