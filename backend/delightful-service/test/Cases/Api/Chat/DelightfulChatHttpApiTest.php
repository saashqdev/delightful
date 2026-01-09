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
     * testsession窗口middle的chat补allfeature.
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
                    'content' => '你好，have什么canhelp你的吗？',
                ],
            ],
        ];

        // setrequesthead
        $headers = [
            // todo mock authorization的校验
            'authorization' => '',
            'Content-Type' => 'application/json',
        ];

        // sendPOSTrequesttocorrect的interfacepath
        $response = $this->json('/api/v2/delightful/conversation/chatCompletions', $requestData, $headers);

        // verifyresponse码
        $this->assertEquals(1000, $response['code'] ?? 0, 'response码应为1000');
        $this->assertEquals('ok', $response['message'] ?? '', 'responsemessage应为ok');

        // definitionexpect的response结构
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
        $this->assertArrayValueTypesEquals($expectedStructure, $response, 'response结构not符合expected');

        // 额outsideverifyrolewhether是assistant（这是精确valueverify）
        $this->assertEquals('assistant', $response['data']['choices'][0]['message']['role'], 'role应为assistant');
    }

    /**
     * testsession窗口middle的chat补allfeature - parameterverifyfail.
     */
    public function testConversationChatCompletionsWithInvalidParams(): void
    {
        // 构造缺少必要parameter的request
        $requestData = [
            // 缺少 conversation_id
            'topic_id' => 'test_topic_id',
            'message' => '你好，testmessage',
        ];

        // setrequesthead
        $headers = [
            'Authorization' => env('TEST_TOKEN', 'test_token'),
            'Content-Type' => 'application/json',
        ];

        // sendPOSTrequesttocorrect的interfacepath
        $response = $this->json('/api/v2/delightful/chat/chatCompletions', $requestData, $headers);

        // definitionexpect的errorresponse结构
        $expectedErrorStructure = [
            'code' => 0, // expectednot是1000的code，butspecific数value可能not确定，所by这within只是占位
            'message' => '', // 只verify存inmessagefield，specificcontent可能not确定
        ];

        // verifyresponseshould是parameterverifyerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, '缺少必要parametero clock，response码not应为1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'errorresponse结构not符合expected');
    }

    /**
     * testsession窗口middle的chat补allfeature - authorizationverifyfail.
     */
    public function testConversationChatCompletionsWithInvalidAuth(): void
    {
        // 构造requestparameter
        $requestData = [
            'conversation_id' => 'test_conversation_id',
            'message' => '你好，testmessage',
        ];

        // setinvalid的requesthead
        $headers = [
            'Authorization' => 'invalid_token',
            'Content-Type' => 'application/json',
        ];

        // sendPOSTrequesttocorrect的interfacepath
        $response = $this->json('/api/v2/delightful/chat/chatCompletions', $requestData, $headers);

        // definitionexpect的errorresponse结构
        $expectedErrorStructure = [
            'code' => 0, // expectednot是1000的code，specific数value可能not确定
            'message' => '', // 只verify存inmessagefield，specificcontent可能not确定
        ];

        // verifyresponseshould是authorizationerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, 'invalidauthorizationo clock，response码not应为1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'authorizationerrorresponse结构not符合expected');
    }
}
