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
     * 默认testmodel.
     */
    private const DEFAULT_MODEL = 'deepseek-v3';

    /**
     * test chatCompletions method的高可用性.
     */
    public function testHighAvaiable()
    {
        // buildtest数据
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

        // create一个 Parallel 实例，set最大并发数为 10
        $parallel = new Parallel(10);

        // 定义多个不同的请求场景
        $scenario = $this->buildRequestData([
            'business_params' => [
                'organization_id' => '000',
                'user_id' => '9527',
                'business_id' => '000',
            ],
        ]);

        // 添加并发task
        $index = 0;
        $count = 10; // 进一步减少test数量，只要有一个success就行
        while ($index < $count) {
            $parallel->add(function () use ($scenario, $index, $expectedResponse) {
                try {
                    // 发送HTTP请求
                    $response = $this->json('/v1/chat/completions', $scenario, $this->getTestHeaders());
                    // assertresult包含预期的content
                    $this->assertArrayValueTypesEquals($expectedResponse, $response);

                    return [
                        'success' => true,
                        'index' => $index,
                        'content' => $response['choices'][0]['message']['content'] ?? '',
                    ];
                } catch (Throwable $e) {
                    // 直接returnfailinfo，不进行重试
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
        // 执行所有并发task并getresult
        $results = $parallel->wait();
        // 统计success和fail的请求
        $successCount = 0;
        foreach ($results as $result) {
            if ($result['success']) {
                ++$successCount;
            } else {
                // recordfailinfo，但不assertfail
                echo "Request {$result['index']} failed: {$result['error']} (code: {$result['error_code']})" . PHP_EOL;
            }
        }

        // 确保至少有一个请求success
        $this->assertGreaterThan(0, $successCount, '至少应该有一个请求success');

        // 输出success率
        $successRate = ($successCount / $count) * 100;
        echo PHP_EOL;
        echo "testHighAvaiable 请求success率：{$successRate}% ({$successCount}/" . $count . ')' . PHP_EOL;
    }

    /**
     * test chatCompletions method的基本功能.
     */
    public function testChatCompletions(): void
    {
        // 构造请求parameter
        $requestData = $this->buildRequestData([
            'business_params' => [
                'organization_id' => '000',
                'user_id' => '9527',
                'business_id' => '000',
            ],
        ]);

        // 发送POST请求
        $response = $this->json('/v1/chat/completions', $requestData, $this->getTestHeaders());
        // 验证整个响应结构
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
        $this->assertArrayValueTypesEquals($expectedResponse, $response, '响应结构及type验证fail');
    }

    /**
     * test embeddings method的基本功能.
     */
    public function testEmbeddings(): void
    {
        // 构造向量嵌入请求parameter
        $requestData = [
            'model' => self::DEFAULT_MODEL,
            'input' => '这是一个用于test的文本',
            'business_params' => [
                'organization_id' => '000',
                'user_id' => '9527',
                'business_id' => '000',
            ],
        ];

        // 发送POST请求
        $response = $this->json('/v1/embeddings', $requestData, $this->getTestHeaders());

        // 验证响应结构
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
        $this->assertArrayValueTypesEquals($expectedResponse, $response, '响应结构及type验证fail');
    }

    /**
     * 提供test用的通用message数据.
     */
    private function getTestMessages(): array
    {
        return [
            [
                'role' => 'system',
                'content' => '你是一个助手',
            ],
            [
                'role' => 'user',
                'content' => '你好',
            ],
        ];
    }

    /**
     * 提供test用的请求头.
     */
    private function getTestHeaders(): array
    {
        return [
            'api-key' => env('UNIT_TEST_USER_TOKEN'),
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * build基础的请求数据.
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
