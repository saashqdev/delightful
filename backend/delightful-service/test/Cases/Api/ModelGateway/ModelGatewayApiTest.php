<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\ModelGateway;

use Hyperf\Coroutine\Parallel;
use HyperfTest\Cases\Api\AbstractHttpTest;
use Throwable;

/**
 * @internal
 */
class ModelGatewayApiTest extends AbstractHttpTest
{
    /**
     * defaulttestmodel.
     */
    private const DEFAULT_MODEL = 'deepseek-v3';

    /**
     * test chatCompletions methodhighcanuseproperty.
     */
    public function testHighAvaiable()
    {
        // buildtestdata
        $expectedResponse = [
            'id' => '',
            'object' => 'chat.completion',
            'created' => 0,
            'choices' => [
                [
                    'finish_reason' => '',
                    'index' => 0,
                    'logprobs' => null,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'NOT_EMPTY',
                    ],
                ],
            ],
            'usage' => [
                'completion_tokens' => 0,
                'prompt_tokens' => 0,
                'total_tokens' => 0,
                'prompt_tokens_details' => [],
            ],
        ];

        // createone Parallel instance,setmostbigandhaircountfor 10
        $parallel = new Parallel(10);

        // definitionmultipledifferentrequestscenario
        $scenario = $this->buildRequestData([
            'business_params' => [
                'organization_id' => '000',
                'user_id' => '9527',
                'business_id' => '000',
            ],
        ]);

        // addandhairtask
        $index = 0;
        $count = 10; // enteronestepdecreasetestquantity,as long ashaveonesuccessthenline
        while ($index < $count) {
            $parallel->add(function () use ($scenario, $index, $expectedResponse) {
                try {
                    // sendHTTPrequest
                    $response = $this->json('/v1/chat/completions', $scenario, $this->getTestHeaders());
                    // assertresultcontainexpectedcontent
                    $this->assertArrayValueTypesEquals($expectedResponse, $response);

                    return [
                        'success' => true,
                        'index' => $index,
                        'content' => $response['choices'][0]['message']['content'] ?? '',
                    ];
                } catch (Throwable $e) {
                    // directlyreturnfailinfo,notconductretry
                    return [
                        'success' => false,
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                    ];
                }
            });
            ++$index;
        }
        // execute haveandhairtaskandgetresult
        $results = $parallel->wait();
        // statisticssuccessandfailrequest
        $successCount = 0;
        foreach ($results as $result) {
            if ($result['success']) {
                ++$successCount;
            } else {
                // recordfailinfo,butnotassertfail
                echo "Request {$result['index']} failed: {$result['error']} (code: {$result['error_code']})" . PHP_EOL;
            }
        }

        // ensureat leasthaveonerequestsuccess
        $this->assertGreaterThan(0, $successCount, 'at leastshouldhaveonerequestsuccess');

        // outputsuccessrate
        $successRate = ($successCount / $count) * 100;
        echo PHP_EOL;
        echo "testHighAvaiable requestsuccessrate:{$successRate}% ({$successCount}/" . $count . ')' . PHP_EOL;
    }

    /**
     * test chatCompletions methodbasicfeature.
     */
    public function testChatCompletions(): void
    {
        // constructrequestparameter
        $requestData = $this->buildRequestData([
            'business_params' => [
                'organization_id' => '000',
                'user_id' => '9527',
                'business_id' => '000',
            ],
        ]);

        // sendPOSTrequest
        $response = $this->json('/v1/chat/completions', $requestData, $this->getTestHeaders());
        // verify整responsestructure
        $expectedResponse = [
            'id' => '',
            'object' => 'chat.completion',
            'created' => 0,
            'choices' => [
                [
                    'finish_reason' => '',
                    'index' => 0,
                    'logprobs' => null,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'NOT_EMPTY',
                    ],
                ],
            ],
            'usage' => [
                'completion_tokens' => 0,
                'prompt_tokens' => 0,
                'total_tokens' => 0,
                'prompt_tokens_details' => [],
            ],
        ];
        $this->assertArrayValueTypesEquals($expectedResponse, $response, 'responsestructureandtypeverifyfail');
    }

    /**
     * test embeddings methodbasicfeature.
     */
    public function testEmbeddings(): void
    {
        // constructtoquantityembeddingrequestparameter
        $requestData = [
            'model' => self::DEFAULT_MODEL,
            'input' => 'thisisoneuseattesttext',
            'business_params' => [
                'organization_id' => '000',
                'user_id' => '9527',
                'business_id' => '000',
            ],
        ];

        // sendPOSTrequest
        $response = $this->json('/v1/embeddings', $requestData, $this->getTestHeaders());

        // verifyresponsestructure
        $expectedResponse = [
            'object' => 'list',
            'data' => [
                [
                    'object' => 'embedding',
                    'embedding' => [
                        -0.01833024,
                        0.02034276,
                        -0.018185195,
                        0.013144831,
                    ],
                    'index' => 0,
                ],
            ],
            'model' => self::DEFAULT_MODEL,
            'usage' => [
                'prompt_tokens' => 0,
                'total_tokens' => 0,
            ],
        ];
        $this->assertArrayValueTypesEquals($expectedResponse, $response, 'responsestructureandtypeverifyfail');
    }

    /**
     * providetestusecommonusemessagedata.
     */
    private function getTestMessages(): array
    {
        return [
            [
                'role' => 'system',
                'content' => 'youisonehelphand',
            ],
            [
                'role' => 'user',
                'content' => 'yougood',
            ],
        ];
    }

    /**
     * providetestuserequesthead.
     */
    private function getTestHeaders(): array
    {
        return [
            'api-key' => env('UNIT_TEST_USER_TOKEN'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * buildfoundationrequestdata.
     */
    private function buildRequestData(array $overrides = []): array
    {
        $default = [
            'model' => self::DEFAULT_MODEL,
            'messages' => $this->getTestMessages(),
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ];

        return array_merge($default, $overrides);
    }
}
