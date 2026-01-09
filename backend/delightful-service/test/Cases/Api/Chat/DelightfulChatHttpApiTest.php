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
     * testsession窗口中的chat补全feature.
     */
    public function testConversationChatCompletions(): void
    {
        // 构造requestparameter
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

        // setrequest头
        $headers = [
            // todo mock authorization的校验
            'authorization' => '',
            'Content-Type' => 'application/json',
        ];

        // sendPOSTrequest到correct的interfacepath
        $response = $this->json('/api/v2/delightful/conversation/chatCompletions', $requestData, $headers);

        // verifyresponse码
        $this->assertEquals(1000, $response['code'] ?? 0, 'response码应为1000');
        $this->assertEquals('ok', $response['message'] ?? '', 'responsemessage应为ok');

        // 定义expect的response结构
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

        // useassertArrayValueTypesEqualsverifyresponse结构
        $this->assertArrayValueTypesEquals($expectedStructure, $response, 'response结构不符合expected');

        // 额外verifyrole是否是assistant（这个是精确valueverify）
        $this->assertEquals('assistant', $response['data']['choices'][0]['message']['role'], 'role应为assistant');
    }

    /**
     * testsession窗口中的chat补全feature - parameterverifyfail.
     */
    public function testConversationChatCompletionsWithInvalidParams(): void
    {
        // 构造缺少必要parameter的request
        $requestData = [
            // 缺少 conversation_id
            'topic_id' => 'test_topic_id',
            'message' => '你好，testmessage',
        ];

        // setrequest头
        $headers = [
            'Authorization' => env('TEST_TOKEN', 'test_token'),
            'Content-Type' => 'application/json',
        ];

        // sendPOSTrequest到correct的interfacepath
        $response = $this->json('/api/v2/delightful/chat/chatCompletions', $requestData, $headers);

        // 定义expect的errorresponse结构
        $expectedErrorStructure = [
            'code' => 0, // expected不是1000的code，但具体数value可能不确定，所以这里只是占位
            'message' => '', // 只verify存在messagefield，具体content可能不确定
        ];

        // verifyresponseshould是parameterverifyerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, '缺少必要parameter时，response码不应为1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'errorresponse结构不符合expected');
    }

    /**
     * testsession窗口中的chat补全feature - authorizationverifyfail.
     */
    public function testConversationChatCompletionsWithInvalidAuth(): void
    {
        // 构造requestparameter
        $requestData = [
            'conversation_id' => 'test_conversation_id',
            'message' => '你好，testmessage',
        ];

        // setinvalid的request头
        $headers = [
            'Authorization' => 'invalid_token',
            'Content-Type' => 'application/json',
        ];

        // sendPOSTrequest到correct的interfacepath
        $response = $this->json('/api/v2/delightful/chat/chatCompletions', $requestData, $headers);

        // 定义expect的errorresponse结构
        $expectedErrorStructure = [
            'code' => 0, // expected不是1000的code，具体数value可能不确定
            'message' => '', // 只verify存在messagefield，具体content可能不确定
        ];

        // verifyresponseshould是authorizationerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, 'invalidauthorization时，response码不应为1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'authorizationerrorresponse结构不符合expected');
    }
}
