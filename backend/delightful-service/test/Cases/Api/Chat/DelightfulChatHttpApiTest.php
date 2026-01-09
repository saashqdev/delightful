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
                    'content' => 'yougood,havewhatcanhelpyou?',
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

        // verifyresponsecode
        $this->assertEquals(1000, $response['code'] ?? 0, 'responsecodeshouldfor1000');
        $this->assertEquals('ok', $response['message'] ?? '', 'responsemessageshouldforok');

        // definitionexpectresponsestructure
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

        // useassertArrayValueTypesEqualsverifyresponsestructure
        $this->assertArrayValueTypesEquals($expectedStructure, $response, 'responsestructurenotconformexpected');

        // quotaoutsideverifyrolewhetherisassistant(thisisprecisevalueverify)
        $this->assertEquals('assistant', $response['data']['choices'][0]['message']['role'], 'roleshouldforassistant');
    }

    /**
     * testsessionwindowmiddlechat补allfeature - parameterverifyfail.
     */
    public function testConversationChatCompletionsWithInvalidParams(): void
    {
        // constructmissingrequiredwantparameterrequest
        $requestData = [
            // missing conversation_id
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

        // definitionexpecterrorresponsestructure
        $expectedErrorStructure = [
            'code' => 0, // expectednotis1000code,butspecificcountvaluemaybenotcertain, bythiswithinonlyisplaceholder
            'message' => '', // onlyverifyexistsinmessagefield,specificcontentmaybenotcertain
        ];

        // verifyresponseshouldisparameterverifyerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, 'missingrequiredwantparametero clock,responsecodenotshouldfor1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'errorresponsestructurenotconformexpected');
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

        // definitionexpecterrorresponsestructure
        $expectedErrorStructure = [
            'code' => 0, // expectednotis1000code,specificcountvaluemaybenotcertain
            'message' => '', // onlyverifyexistsinmessagefield,specificcontentmaybenotcertain
        ];

        // verifyresponseshouldisauthorizationerror
        $this->assertNotEquals(1000, $response['code'] ?? 0, 'invalidauthorizationo clock,responsecodenotshouldfor1000');
        $this->assertArrayValueTypesEquals($expectedErrorStructure, $response, 'authorizationerrorresponsestructurenotconformexpected');
    }
}
