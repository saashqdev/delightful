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
     * testsessionwindowmiddlechat补allfeature.
     */
    public function testConversationChatCompletions(): void
    {
        // constructrequestparameter
        $requestData = [
            'conversation_id' => 'test_conversation_id',
            'topic_id' => 'test_topic_id',
            'message' => 'yougood,testmessage',
            'history' => [
                [
                    'role' => 'user',
                    'content' => 'yougood',
                ],
                [
                    'role' => 'assistant',
                    'content' => 'yougood,have什么canhelpyou?',
                ],
            ],
        ];

        // setrequesthead
        $headers = [
            // todo mock authorizationvalidation
            'authorization' => '',
            'Content-Type' => 'application/json',
        ];

        // sendPOSTrequesttocorrectinterfacepath
        $response = $this->json('/api/v2/delightful/conversation/chatCompletions', $requestData, $headers);

        // verifyresponse码
        $this->assertEquals(1000, $response['code'] ?? 0, 'response码应for1000');
        $this->assertEquals('ok', $response['message'] ?? '', 'responsemessage应forok');

        // definitionexpectresponse结构
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
                    'message' => 'yougood,testmessage',
                    'history' => [],
                ],
            ],
        ];

        // useassertArrayValueTypesEqualsverifyresponse结构
        $this->assertArrayValueTypesEquals($expectedStructure, $response, 'response结构notconformexpected');

        // 额outsideverifyrolewhetherisassistant(thisisprecisevalueverify)
        $this->assertEquals('assistant', $response['data']['choices'][0]['message']['role'], 'role应forassistant');
    }

    /**
     * testsessionwindowmiddlechat补allfeature - parameterverifyfail.
     */
    public function testConversationChatCompletionsWithInvalidParams(): void
    {
        // construct缺少必wantparameterrequest
        $requestData = [
            // 缺少 conversation_id
            'topic_id' => 'test_topic_id',
            'message' => 'yougood,testmessage',
        ];

        // setrequesthead
        $headers = [
            'Authorization' => env('TEST_TOKEN', 'test_token'),
            'Content-Type' => 'application/json',
        ];

        // sendPOSTrequesttocorrectinterfacepath
        $response = $this->json('/api/v2/delightful/chat/chatCompletions', $requestData, $headers);

        // definitionexpecterrorresponse结构
        $expectedErrorStructure = [
            'code' => 0, // expectednotis1000code,butspecific数valuemaybenotcertain,所bythiswithinonlyis占位
            'message' => '', // onlyverify存inmessagefield,specificcontentmaybenotcertain
        ];

        // verifyresponseshouldisparameterverifyerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, '缺少必wantparametero clock,response码not应for1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'errorresponse结构notconformexpected');
    }

    /**
     * testsessionwindowmiddlechat补allfeature - authorizationverifyfail.
     */
    public function testConversationChatCompletionsWithInvalidAuth(): void
    {
        // constructrequestparameter
        $requestData = [
            'conversation_id' => 'test_conversation_id',
            'message' => 'yougood,testmessage',
        ];

        // setinvalidrequesthead
        $headers = [
            'Authorization' => 'invalid_token',
            'Content-Type' => 'application/json',
        ];

        // sendPOSTrequesttocorrectinterfacepath
        $response = $this->json('/api/v2/delightful/chat/chatCompletions', $requestData, $headers);

        // definitionexpecterrorresponse结构
        $expectedErrorStructure = [
            'code' => 0, // expectednotis1000code,specific数valuemaybenotcertain
            'message' => '', // onlyverify存inmessagefield,specificcontentmaybenotcertain
        ];

        // verifyresponseshouldisauthorizationerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, 'invalidauthorizationo clock,response码not应for1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'authorizationerrorresponse结构notconformexpected');
    }
}
