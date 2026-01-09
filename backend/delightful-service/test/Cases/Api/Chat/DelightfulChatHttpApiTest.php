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
     * testsession窗口中的聊天补全功能.
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
                    'content' => '你好，有什么可以帮助你的吗？',
                ],
            ],
        ];

        // set请求头
        $headers = [
            // todo mock authorization的校验
            'authorization' => '',
            'Content-Type' => 'application/json',
        ];

        // 发送POST请求到正确的接口路径
        $response = $this->json('/api/v2/delightful/conversation/chatCompletions', $requestData, $headers);

        // 验证响应码
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

        // useassertArrayValueTypesEquals验证响应结构
        $this->assertArrayValueTypesEquals($expectedStructure, $response, '响应结构不符合预期');

        // 额外验证role是否是assistant（这个是精确value验证）
        $this->assertEquals('assistant', $response['data']['choices'][0]['message']['role'], 'role应为assistant');
    }

    /**
     * testsession窗口中的聊天补全功能 - parameter验证fail.
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

        // 发送POST请求到正确的接口路径
        $response = $this->json('/api/v2/delightful/chat/chatCompletions', $requestData, $headers);

        // 定义expect的error响应结构
        $expectedErrorStructure = [
            'code' => 0, // 预期不是1000的code，但具体数value可能不确定，所以这里只是占位
            'message' => '', // 只验证存在messagefield，具体content可能不确定
        ];

        // 验证响应应该是parameter验证error
        $this->assertNotEquals(1000, $response['code'] ?? 0, '缺少必要parameter时，响应码不应为1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'error响应结构不符合预期');
    }

    /**
     * testsession窗口中的聊天补全功能 - authorization验证fail.
     */
    public function testConversationChatCompletionsWithInvalidAuth(): void
    {
        // 构造请求parameter
        $requestData = [
            'conversation_id' => 'test_conversation_id',
            'message' => '你好，testmessage',
        ];

        // set无效的请求头
        $headers = [
            'Authorization' => 'invalid_token',
            'Content-Type' => 'application/json',
        ];

        // 发送POST请求到正确的接口路径
        $response = $this->json('/api/v2/delightful/chat/chatCompletions', $requestData, $headers);

        // 定义expect的error响应结构
        $expectedErrorStructure = [
            'code' => 0, // 预期不是1000的code，具体数value可能不确定
            'message' => '', // 只验证存在messagefield，具体content可能不确定
        ];

        // 验证响应应该是authorizationerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, '无效authorization时，响应码不应为1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'authorizationerror响应结构不符合预期');
    }
}
