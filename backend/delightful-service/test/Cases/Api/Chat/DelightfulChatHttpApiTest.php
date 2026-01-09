<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\Chat;

use HyperfTest\Cases\Api\AbstractHttpTest;

/**
 * @internal
 */
class DelightfulChatHttpApiTest extends AbstractHttpTest
{
    /**
     * testsession窗口中的chat补全功能.
     */
    public function testConversationChatCompletions(): void
    {
        // 构造请求parameter
        $requestData = [
            'conversation_id' => 'test_conversation_id',
            'topic_id' => 'test_topic_id',
            'message' => '你好，testmessage',
            'history' => [
                [
                    'role' => 'user',
                    'content' => '你好',
                ],
                [
                    'role' => 'assistant',
                    'content' => '你好，有什么can帮助你的吗？',
                ],
            ],
        ];

        // set请求头
        $headers = [
            // todo mock authorization的校验
            'authorization' => '',
            'Content-Type' => 'application/json',
        ];

        // 发送POST请求到correct的接口路径
        $response = $this->json('/api/v2/delightful/conversation/chatCompletions', $requestData, $headers);

        // verify响应码
        $this->assertEquals(1000, $response['code'] ?? 0, '响应码应为1000');
        $this->assertEquals('ok', $response['message'] ?? '', '响应message应为ok');

        // 定义expect的响应结构
        $expectedStructure = [
            'data' => [
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => '',
                        ],
                    ],
                ],
                'request_info' => [
                    'conversation_id' => 'test_conversation_id',
                    'topic_id' => 'test_topic_id',
                    'message' => '你好，testmessage',
                    'history' => [],
                ],
            ],
        ];

        // useassertArrayValueTypesEqualsverify响应结构
        $this->assertArrayValueTypesEquals($expectedStructure, $response, '响应结构不符合expected');

        // 额外verifyrole是否是assistant（这个是精确valueverify）
        $this->assertEquals('assistant', $response['data']['choices'][0]['message']['role'], 'role应为assistant');
    }

    /**
     * testsession窗口中的chat补全功能 - parameterverifyfail.
     */
    public function testConversationChatCompletionsWithInvalidParams(): void
    {
        // 构造缺少必要parameter的请求
        $requestData = [
            // 缺少 conversation_id
            'topic_id' => 'test_topic_id',
            'message' => '你好，testmessage',
        ];

        // set请求头
        $headers = [
            'Authorization' => env('TEST_TOKEN', 'test_token'),
            'Content-Type' => 'application/json',
        ];

        // 发送POST请求到correct的接口路径
        $response = $this->json('/api/v2/delightful/chat/chatCompletions', $requestData, $headers);

        // 定义expect的error响应结构
        $expectedErrorStructure = [
            'code' => 0, // expected不是1000的code，但具体数value可能不确定，所以这里只是占位
            'message' => '', // 只verify存在messagefield，具体content可能不确定
        ];

        // verify响应should是parameterverifyerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, '缺少必要parameter时，响应码不应为1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'error响应结构不符合expected');
    }

    /**
     * testsession窗口中的chat补全功能 - authorizationverifyfail.
     */
    public function testConversationChatCompletionsWithInvalidAuth(): void
    {
        // 构造请求parameter
        $requestData = [
            'conversation_id' => 'test_conversation_id',
            'message' => '你好，testmessage',
        ];

        // setinvalid的请求头
        $headers = [
            'Authorization' => 'invalid_token',
            'Content-Type' => 'application/json',
        ];

        // 发送POST请求到correct的接口路径
        $response = $this->json('/api/v2/delightful/chat/chatCompletions', $requestData, $headers);

        // 定义expect的error响应结构
        $expectedErrorStructure = [
            'code' => 0, // expected不是1000的code，具体数value可能不确定
            'message' => '', // 只verify存在messagefield，具体content可能不确定
        ];

        // verify响应should是authorizationerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, 'invalidauthorization时，响应码不应为1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'authorizationerror响应结构不符合expected');
    }
}
