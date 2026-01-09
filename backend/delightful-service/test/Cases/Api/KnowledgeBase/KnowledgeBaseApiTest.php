<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace HyperfTest\Cases\Api\KnowledgeBase;

use App\Domain\KnowledgeBase\Entity\ValueObject\FragmentMode;
use App\Domain\KnowledgeBase\Entity\ValueObject\KnowledgeType;
use App\Domain\KnowledgeBase\Entity\ValueObject\SearchType;
use App\Domain\KnowledgeBase\Entity\ValueObject\SourceType;
use App\ErrorCode\FlowErrorCode;
use App\Infrastructure\Util\Text\TextPreprocess\ValueObject\TextPreprocessRule;
use Hyperf\Snowflake\IdGeneratorInterface;
use HyperfTest\HttpTestCase;

/**
 * @internal
 * @coversNothing
 */
class KnowledgeBaseApiTest extends HttpTestCase
{
    public const string API = '/api/v1/knowledge-bases';

    protected function setUp(): void
    {
        $this->clearTestKnowledgeBaseData();
        // Open during test environment self-test, will delete all user knowledge bases
        $this->deleteAllKnowledgeBase();
        parent::setUp();
    }

    public function testCreateKnowledgeBase()
    {
        $data = [
            'name' => 'Test Knowledge Base',
            'description' => 'This is a test knowledge base description',
            'icon' => 'DT001/588417216353927169/4c9184f37cff01bcdc32dc486ec36961/Oz_iUDWyjYwLxME31WwFn.jpg',
            'enabled' => true,
            'is_draft' => true,
            'embedding_config' => ['model_id' => 'dmeta-embedding'],
            'retrieve_config' => [
                'top_k' => 4,
                'weights' => null,
                'search_method' => 'graph_search',
                'reranking_model' => ['reranking_model_name' => 'BAAI/bge-reranker-large'],
                'score_threshold' => 0.67,
                'reranking_enable' => true,
                'score_threshold_enabled' => true,
            ],
            'fragment_config' => [
                'mode' => FragmentMode::NORMAL->value,
                'normal' => [
                    'text_preprocess_rule' => [
                        TextPreprocessRule::REPLACE_WHITESPACE->value,
                        TextPreprocessRule::REMOVE_URL_EMAIL->value,
                    ],
                    'segment_rule' => [
                        'separator' => '\n',
                        'chunk_size' => 50,
                        'chunk_overlap' => 10,
                    ],
                ],
            ],
        ];

        $knowledgeBase = $this->createKnowledgeBase($data);

        $this->assertIsString($knowledgeBase['id']);
        $this->assertIsString($knowledgeBase['code']);
        $this->assertSame('Test Knowledge Base', $knowledgeBase['name']);
        $this->assertSame('This is a test knowledge base description', $knowledgeBase['description']);
        $this->assertTrue($knowledgeBase['enabled']);
        $this->assertIsString($knowledgeBase['organization_code']);
        $this->assertSame(KnowledgeType::UserKnowledgeBase->value, $knowledgeBase['type']);
        $this->assertIsString($knowledgeBase['created_at']);
        $this->assertIsString($knowledgeBase['updated_at']);
        $this->assertIsInt($knowledgeBase['word_count']);
        $this->assertIsInt($knowledgeBase['document_count']);
        $this->assertIsString($knowledgeBase['icon']);
    }

    /**
     * Test searching knowledge base by name.
     */
    public function testGetKnowledgeBaseList1()
    {
        $name = 'test_' . md5((string) di(IdGeneratorInterface::class)->generate());
        $created = $this->createKnowledgeBase(['name' => $name]);
        $res = $this->post(self::API . '/queries', ['name' => $name], $this->getCommonHeaders());
        $this->assertSame(1000, $res['code'], $res['message']);

        $this->assertCount(1, $res['data']['list']);
        $knowledgeBase = $res['data']['list'][0];
        $this->assertSame($created['code'], $knowledgeBase['code']);
        $this->assertSame($name, $knowledgeBase['name']);
        $this->assertIsString($knowledgeBase['description']);
        $this->assertTrue($knowledgeBase['enabled']);
        $this->assertIsInt($knowledgeBase['word_count']);
        $this->assertIsInt($knowledgeBase['document_count']);
        $this->assertSame(SourceType::EXTERNAL_FILE->value, $knowledgeBase['source_type']);
    }

    /**
     * Test searching for enabled knowledge bases.
     */
    public function testGetKnowledgeBaseList2()
    {
        $name = 'test_' . md5((string) di(IdGeneratorInterface::class)->generate());
        $created = $this->createKnowledgeBase(['name' => $name]);
        $res = $this->post(self::API . '/queries', ['name' => $name, 'search_type' => SearchType::ENABLED->value], $this->getCommonHeaders());
        $this->assertSame(1000, $res['code'], $res['message']);

        $this->assertCount(1, $res['data']['list']);
        $knowledgeBase = $res['data']['list'][0];
        $this->assertSame($created['code'], $knowledgeBase['code']);
    }

    /**
     * Test searching for disabled knowledge bases.
     */
    public function testGetKnowledgeBaseList3()
    {
        $name = 'test_' . md5((string) di(IdGeneratorInterface::class)->generate());
        $knowledgeBase = $this->createKnowledgeBase(['name' => $name]);
        $knowledgeBaseCode = $knowledgeBase['code'];
        $res = $this->post(self::API . '/queries', ['name' => $name, 'search_type' => SearchType::DISABLED->value], $this->getCommonHeaders());
        $this->assertSame(1000, $res['code'], $res['message']);
        $this->assertCount(0, $res['data']['list']);

        // After changing status to disabled, can find in list
        $this->updateKnowledgeBase($knowledgeBaseCode, ['name' => $name, 'description' => '1', 'enabled' => false]);
        $res = $this->post(self::API . '/queries', ['name' => $name, 'search_type' => SearchType::DISABLED->value], $this->getCommonHeaders());
        $this->assertSame(1000, $res['code'], $res['message']);
        $this->assertCount(1, $res['data']['list']);
        $this->assertSame($knowledgeBaseCode, $res['data']['list'][0]['code']);
    }

    public function testUpdateKnowledgeBase()
    {
        $knowledgeBase = $this->createKnowledgeBase();
        $code = $knowledgeBase['code'];
        $data = [
            'name' => 'Updated Knowledge Base',
            'description' => 'This is the updated knowledge base description',
            'enabled' => false,
            'retrieve_config' => [
                'top_k' => 4,
                'weights' => null,
                'search_method' => 'graph_search',
                'reranking_model' => ['reranking_model_name' => 'BAAI/bge-reranker-large'],
                'score_threshold' => 0.67,
                'reranking_enable' => true,
                'score_threshold_enabled' => true,
            ],
            'fragment_config' => [
                'mode' => FragmentMode::NORMAL->value,
                'normal' => [
                    'text_preprocess_rule' => [
                        TextPreprocessRule::REPLACE_WHITESPACE->value,
                        TextPreprocessRule::REMOVE_URL_EMAIL->value,
                    ],
                    'segment_rule' => [
                        'separator' => ' ',
                        'chunk_size' => 50,
                        'chunk_overlap' => 10,
                    ],
                ],
            ],
            'embedding_config' => [
                'model_id' => 'dmeta-embedding',
            ],
        ];

        $this->updateKnowledgeBase($code, $data);

        $res = $this->get(self::API . '/' . $code, [], $this->getCommonHeaders());
        $knowledgeBase = $res['data'];
        $this->assertSame('Updated Knowledge Base', $knowledgeBase['name']);
        $this->assertSame('This is the updated knowledge base description', $knowledgeBase['description']);
        $this->assertFalse($knowledgeBase['enabled']);
        $this->assertSame([
            'search_method' => 'graph_search',
            'top_k' => 4,
            'score_threshold' => 0.67,
            'score_threshold_enabled' => true,
            'reranking_mode' => 'weighted_score',
            'reranking_enable' => true,
            'weights' => [
                'vector_setting' => [
                    'vector_weight' => 1,
                    'embedding_model_name' => '',
                    'embedding_provider_name' => '',
                ],
                'keyword_setting' => [
                    'keyword_weight' => 0,
                ],
                'graph_setting' => [
                    'relation_weight' => 0.5,
                    'max_depth' => 2,
                    'include_properties' => true,
                    'timeout' => 5,
                    'retry_count' => 3,
                ],
            ],
            'reranking_model' => [
                'reranking_model_name' => 'BAAI/bge-reranker-large',
                'reranking_provider_name' => '',
            ],
        ], $knowledgeBase['retrieve_config']);

        $this->assertSame([
            'mode' => FragmentMode::NORMAL->value,
            'normal' => [
                'text_preprocess_rule' => [
                    TextPreprocessRule::REPLACE_WHITESPACE->value,
                    TextPreprocessRule::REMOVE_URL_EMAIL->value,
                ],
                'segment_rule' => [
                    'separator' => ' ',
                    'chunk_size' => 50,
                    'chunk_overlap' => 10,
                ],
            ],
            'parent_child' => null,
        ], $knowledgeBase['fragment_config']);

        $this->assertSame(['model_id' => 'dmeta-embedding'], $knowledgeBase['embedding_config']);
    }

    public function testDeleteKnowledgeBase()
    {
        $knowledgeBase = $this->createKnowledgeBase();
        $code = $knowledgeBase['code'];

        $res = $this->get(self::API . '/' . $code, [], $this->getCommonHeaders());
        $this->assertSame(1000, $res['code']);
        $this->assertNotEmpty($res['data']);

        $res = $this->delete(self::API . '/' . $code, [], $this->getCommonHeaders());
        $this->assertSame(1000, $res['code'], $res['message']);

        $res = $this->get(self::API . '/' . $code, [], $this->getCommonHeaders());
        $this->assertSame(FlowErrorCode::KnowledgeValidateFailed->value, $res['code']);
    }

    public function testCreateDocument()
    {
        $createData = [
            'fragment_config' => [
                'mode' => FragmentMode::NORMAL->value,
                'normal' => [
                    'text_preprocess_rule' => [
                        TextPreprocessRule::REPLACE_WHITESPACE->value,
                        TextPreprocessRule::REMOVE_URL_EMAIL->value,
                    ],
                    'segment_rule' => [
                        'separator' => '\n\n',
                        'chunk_size' => 50,
                        'chunk_overlap' => 10,
                    ],
                ],
                'parent_child' => null,
            ],
        ];
        $document = $this->createDocument($createData);
        $this->assertNotEmpty($document['code']);
        $this->assertSame('test.txt', $document['name']);
        $this->assertIsInt($document['doc_type']);
        $this->assertTrue($document['enabled']);
        $this->assertSame(['source' => 'test'], $document['doc_metadata']);
        $this->assertSame($createData['fragment_config'], $document['fragment_config']);
        $this->assertSame(['model_id' => 'dmeta-embedding'], $document['embedding_config']);
        $this->assertArrayHasKey('knowledge_base_code', $document);
    }

    public function testUpdateDocument()
    {
        $document = $this->createDocument();

        $newFragmentConfig = [
            'mode' => 1,
            'normal' => [
                'text_preprocess_rule' => [
                    1,
                ],
                'segment_rule' => [
                    'separator' => '**',
                    'chunk_size' => 200,
                    'chunk_overlap' => 20,
                ],
            ],
            'parent_child' => null,
        ];

        $updateData = [
            'name' => 'Updated Document Name',
            'enabled' => false,
            'doc_metadata' => ['source' => 'updated'],
            'fragment_config' => $newFragmentConfig,
        ];

        $res = $this->put(
            sprintf('%s/%s/documents/%s', self::API, $document['knowledge_base_code'], $document['code']),
            $updateData,
            $this->getCommonHeaders()
        );

        $this->assertSame(1000, $res['code'], $res['message']);
        $this->assertSame($document['code'], $res['data']['code']);
        $this->assertSame($updateData['name'], $res['data']['name']);
        $this->assertSame($updateData['enabled'], $res['data']['enabled']);
        $this->assertSame($updateData['doc_metadata'], $res['data']['doc_metadata']);
        $this->assertSame($newFragmentConfig, $res['data']['fragment_config']);
    }

    public function testGetDocumentDetail()
    {
        $document = $this->createDocument();

        $res = $this->get(
            sprintf('%s/%s/documents/%s', self::API, $document['knowledge_base_code'], $document['code']),
            [],
            $this->getCommonHeaders()
        );

        $this->assertSame(1000, $res['code'], $res['message']);
        $data = $res['data'];
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('version', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('doc_type', $data);
        $this->assertArrayHasKey('enabled', $data);
        $this->assertArrayHasKey('sync_status', $data);
        $this->assertArrayHasKey('embedding_model', $data);
        $this->assertArrayHasKey('vector_db', $data);
        $this->assertArrayHasKey('organization_code', $data);
        $this->assertArrayHasKey('created_uid', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_uid', $data);
        $this->assertArrayHasKey('updated_at', $data);
        $this->assertArrayHasKey('fragment_config', $data);
        $this->assertArrayHasKey('embedding_config', $data);
        $this->assertArrayHasKey('retrieve_config', $data);
        $this->assertArrayHasKey('creator_info', $data);
        $this->assertArrayHasKey('modifier_info', $data);
        $this->assertArrayHasKey('word_count', $data);
    }

    public function testGetDocumentList()
    {
        // Create several test documents
        $knowledgeBase = $this->createKnowledgeBase();
        $knowledgeBaseCode = $knowledgeBase['code'];
        $this->createDocument(knowledgeBaseCode: $knowledgeBaseCode);
        $this->createDocument(['name' => 'Test Document 2'], $knowledgeBaseCode);

        $params = [
            'page' => 1,
            'page_size' => 10,
        ];

        $res = $this->post(
            sprintf('%s/%s/documents/queries', self::API, $knowledgeBaseCode),
            $params,
            $this->getCommonHeaders()
        );

        $this->assertSame(1000, $res['code'], $res['message']);
        $this->assertArrayHasKey('total', $res['data']);
        $this->assertArrayHasKey('list', $res['data']);
        $this->assertIsArray($res['data']['list']);
        $this->assertGreaterThanOrEqual(2, count($res['data']['list']));
    }

    public function testDestroyDocument()
    {
        $document = $this->createDocument();

        $res = $this->delete(
            sprintf('%s/%s/documents/%s', self::API, $document['knowledge_base_code'], $document['code']),
            [],
            $this->getCommonHeaders()
        );
        $this->assertSame(1000, $res['code'], $res['message']);

        // Verify document has been deleted
        $res = $this->get(
            sprintf('%s/%s/documents/%s', self::API, $document['knowledge_base_code'], $document['code']),
            [],
            $this->getCommonHeaders()
        );
        $this->assertSame(FlowErrorCode::KnowledgeValidateFailed->value, $res['code']);
        // Verify knowledge base word count becomes 0
        $res = $this->get(self::API . '/' . $document['knowledge_base_code'], [], $this->getCommonHeaders());
        $this->assertSame(1000, $res['code'], $res['message']);
        $this->assertSame(0, $res['data']['word_count']);
    }

    public function testCreateFragment()
    {
        $fragment = $this->createFragment();

        $this->assertIsString($fragment['creator']);
        $this->assertIsString($fragment['modifier']);
        $this->assertIsString($fragment['created_at']);
        $this->assertIsString($fragment['updated_at']);
        $this->assertIsString($fragment['id']);
        $this->assertIsString($fragment['knowledge_base_code']);
        $this->assertIsString($fragment['document_code']);
        $this->assertSame('This is a test fragment content', $fragment['content']);
        $this->assertSame(['page' => 1], $fragment['metadata']);
        $this->assertSame('', $fragment['business_id']);
        $this->assertSame(0, $fragment['sync_status']);
        $this->assertSame('', $fragment['sync_status_message']);
        $this->assertSame(0, $fragment['score']);
    }

    public function testUpdateFragment()
    {
        $fragment = $this->createFragment();

        $updateData = [
            'content' => 'Updated fragment content',
            'metadata' => ['page' => 2],
        ];

        $res = $this->put(
            sprintf(
                '%s/%s/documents/%s/fragments/%s',
                self::API,
                $fragment['knowledge_base_code'],
                $fragment['document_code'],
                $fragment['id']
            ),
            $updateData,
            $this->getCommonHeaders()
        );

        $this->assertSame(1000, $res['code'], $res['message']);
        $this->assertSame($fragment['id'], $res['data']['id']);
        $this->assertSame($updateData['content'], $res['data']['content']);
        $this->assertSame($updateData['metadata'], $res['data']['metadata']);
    }

    public function testGetFragmentList()
    {
        $document = $this->createDocument();
        // Create multiple fragments
        $this->createFragment(['content' => 'Fragment 1'], $document['code'], $document['knowledge_base_code']);
        $this->createFragment(['content' => 'Fragment 2'], $document['code'], $document['knowledge_base_code']);

        $params = [
            'page' => 1,
            'page_size' => 10,
        ];

        $res = $this->post(
            sprintf('%s/%s/documents/%s/fragments/queries', self::API, $document['knowledge_base_code'], $document['code']),
            $params,
            $this->getCommonHeaders()
        );

        $this->assertSame(1000, $res['code'], $res['message']);
        $this->assertArrayHasKey('total', $res['data']);
        $this->assertArrayHasKey('list', $res['data']);
        $this->assertIsArray($res['data']['list']);
        $this->assertCount(4, $res['data']['list']);
    }

    public function testGetFragmentDetail()
    {
        $fragment = $this->createFragment();

        $res = $this->get(
            sprintf(
                '%s/%s/documents/%s/fragments/%s',
                self::API,
                $fragment['knowledge_base_code'],
                $fragment['document_code'],
                $fragment['id']
            ),
            [],
            $this->getCommonHeaders()
        );

        $this->assertSame(1000, $res['code'], $res['message']);
        $data = $res['data'];
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('content', $data);
        $this->assertArrayHasKey('metadata', $data);
        $this->assertArrayHasKey('document_code', $data);
        $this->assertArrayHasKey('knowledge_base_code', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
        $this->assertArrayHasKey('word_count', $data);
        $this->assertSame(1, $data['version']);
    }

    public function testDestroyFragment()
    {
        $fragment = $this->createFragment();

        $res = $this->delete(
            sprintf(
                '%s/%s/documents/%s/fragments/%s',
                self::API,
                $fragment['knowledge_base_code'],
                $fragment['document_code'],
                $fragment['id']
            ),
            [],
            $this->getCommonHeaders()
        );
        $this->assertSame(1000, $res['code'], $res['message']);

        // Verify fragment has been deleted
        $res = $this->get(
            sprintf(
                '%s/%s/documents/%s/fragments/%s',
                self::API,
                $fragment['knowledge_base_code'],
                $fragment['document_code'],
                $fragment['id']
            ),
            [],
            $this->getCommonHeaders()
        );
        $this->assertSame(FlowErrorCode::KnowledgeValidateFailed->value, $res['code']);
    }

    /**
     * Test knowledge base fragment preview function.
     * Pass external file via document_file.
     */
    public function testFragmentPreview1()
    {
        $data = [
            'document_file' => [
                'name' => 'test.md',
                'key' => 'test001/open/4c9184f37cff01bcdc32dc486ec36961/9w-fHAaMI4hY3VEIhhozL.md',
            ],
            'fragment_config' => [
                'mode' => FragmentMode::NORMAL->value,
                'normal' => [
                    'text_preprocess_rule' => [
                        TextPreprocessRule::REPLACE_WHITESPACE->value,
                        TextPreprocessRule::REMOVE_URL_EMAIL->value,
                    ],
                    'segment_rule' => [
                        'separator' => '\n',
                        'chunk_size' => 50,
                        'chunk_overlap' => 10,
                    ],
                ],
            ],
        ];

        $res = $this->post(
            self::API . '/fragments/preview',
            $data,
            $this->getCommonHeaders()
        );

        $this->assertSame(1000, $res['code'], $res['message']);
        $this->assertArrayHasKey('data', $res);
        $this->assertArrayHasKey('total', $res['data']);
        $this->assertArrayHasKey('list', $res['data']);
        $this->assertIsArray($res['data']['list']);

        if (! empty($res['data']['list'])) {
            $fragment = $res['data']['list'][0];
            $this->assertArrayHasKey('id', $fragment);
            $this->assertArrayHasKey('content', $fragment);
            $this->assertArrayHasKey('metadata', $fragment);
            $this->assertArrayHasKey('document_code', $fragment);
            $this->assertArrayHasKey('knowledge_base_code', $fragment);
            $this->assertArrayHasKey('created_at', $fragment);
            $this->assertArrayHasKey('updated_at', $fragment);
            $this->assertArrayHasKey('word_count', $fragment);
        }
    }

    public function testSimilarity()
    {
        // Create test knowledge base
        $knowledgeBase = $this->createKnowledgeBase();
        $code = $knowledgeBase['code'];

        // Create test document
        $document = $this->createDocument([], $code);

        // Create test fragment
        $fragment = $this->createFragment([
            'content' => 'This is a test fragment content for testing similarity query functionality',
        ], $document['code'], $code);

        // execute相似度query
        $query = 'test相似度query';
        $res = $this->post(
            sprintf('%s/%s/fragments/similarity', self::API, $code),
            ['query' => $query],
            $this->getCommonHeaders()
        );

        $this->assertSame(1000, $res['code'], $res['message']);
        $this->assertIsArray($res['data']);

        // verifyreturnresult的结构
        if (! empty($res['data'])) {
            $result = $res['data']['list'][0];
            $this->assertArrayHasKey('id', $result);
            $this->assertArrayHasKey('content', $result);
            $this->assertArrayHasKey('metadata', $result);
            $this->assertArrayHasKey('score', $result);
            $this->assertArrayHasKey('document_code', $result);
            $this->assertArrayHasKey('doc_type', $result);
            $this->assertArrayHasKey('knowledge_base_code', $result);

            // verifyreturn的contentcontainquery关键词
            $this->assertStringContainsString('test', $result['content']);
        }
    }

    /**
     * test重新向量化.
     */
    public function testReVectorized()
    {
        $knowledgeBase = $this->createKnowledgeBase();
        $code = $knowledgeBase['code'];
        $document = $this->createDocument([], $code);
        $documentCode = $document['code'];

        $res = $this->post(
            sprintf('%s/%s/documents/%s/re-vectorized', self::API, $code, $documentCode),
            [],
            $this->getCommonHeaders()
        );
        $this->assertSame(1000, $res['code'], $res['message']);
    }

    // delete所有knowledge base
    public function deleteAllKnowledgeBase()
    {
        // getknowledge baselist
        $res = $this->post(self::API . '/queries', [], $this->getCommonHeaders());
        $this->assertSame(1000, $res['code'], $res['message']);
        $knowledgeBases = $res['data']['list'];
        foreach ($knowledgeBases as $knowledgeBase) {
            $this->delete(self::API . '/' . $knowledgeBase['code'], [], $this->getCommonHeaders());
        }
        $this->assertSame(1000, $res['code'], $res['message']);
    }

    /**
     * createtest文档并return文档data.
     */
    protected function createDocument(array $overrideData = [], ?string $knowledgeBaseCode = null): array
    {
        if (empty($knowledgeBaseCode)) {
            $knowledgeBase = $this->createKnowledgeBase();
            $knowledgeBaseCode = $knowledgeBase['code'];
        }
        $defaultData = [
            'name' => 'test文档',
            'doc_type' => 1,
            'enabled' => true,
            'doc_metadata' => ['source' => 'test'],
            'document_file' => ['name' => 'test.txt', 'key' => 'test001/open/4c9184f37cff01bcdc32dc486ec36961/9w-fHAaMI4hY3VEIhhozL.md'],
        ];

        $data = array_merge($defaultData, $overrideData);
        $res = $this->post(
            sprintf('%s/%s/documents', self::API, $knowledgeBaseCode),
            $data,
            $this->getCommonHeaders()
        );

        $this->assertSame(1000, $res['code'], $res['message']);
        $this->assertArrayHasKey('code', $res['data']);
        $this->assertSame($data['document_file']['name'], $res['data']['name']);
        $this->assertIsInt($res['data']['doc_type']);

        return $res['data'];
    }

    /**
     * 清理testdata.
     */
    protected function clearTestKnowledgeBaseData()
    {
        // according toactual情况implement清理逻辑
        // can直接calldatabase操作deletetestdata
        // 或者call相应的servicemethod
    }

    protected function createKnowledgeBase(array $data = []): array
    {
        $data = array_merge([
            'source_type' => SourceType::EXTERNAL_FILE->value,
            'name' => 'testknowledge base',
            'description' => '这是一个testknowledge basedescription',
            'icon' => 'qqqq',
            'enabled' => true,
            'is_draft' => true,
            'document_files' => [['name' => 'aaa.txt', 'key' => 'test001/open/4c9184f37cff01bcdc32dc486ec36961/9w-fHAaMI4hY3VEIhhozL.md']],
            'fragment_config' => [
                'mode' => FragmentMode::NORMAL->value,
                'normal' => [
                    'text_preprocess_rule' => [
                        TextPreprocessRule::REPLACE_WHITESPACE->value,
                        TextPreprocessRule::REMOVE_URL_EMAIL->value,
                    ],
                    'segment_rule' => [
                        'separator' => '\n\n',
                        'chunk_size' => 50,
                        'chunk_overlap' => 10,
                    ],
                ],
            ],
            'embedding_config' => [
                'model_id' => 'dmeta-embedding',
            ],
        ], $data);

        $res = $this->post(self::API, $data, $this->getCommonHeaders());
        $this->assertSame(1000, $res['code'], $res['message']);

        return $res['data'];
    }

    protected function updateKnowledgeBase(string $code, array $data): array
    {
        $res = $this->put(self::API . '/' . $code, $data, $this->getCommonHeaders());
        $this->assertSame(1000, $res['code'], $res['message']);
        return $res['data'];
    }

    /**
     * createtest片段并returndata.
     */
    protected function createFragment(array $overrideData = [], ?string $documentCode = null, ?string $knowledgeBaseCode = null): array
    {
        if (empty($documentCode)) {
            $document = $this->createDocument();
            $documentCode = $document['code'];
            $knowledgeBaseCode = $document['knowledge_base_code'];
        } else {
            $document = $this->get(
                sprintf('%s/%s/documents/%s', self::API, $knowledgeBaseCode, $documentCode),
                [],
                $this->getCommonHeaders()
            );
            $knowledgeBaseCode = $document['data']['knowledge_base_code'];
        }

        $defaultData = [
            'content' => '这是一个test片段content',
            'metadata' => ['page' => 1],
            'embedding_model' => 'test-model',
            'vector_db' => 'test-db',
        ];

        $data = array_merge($defaultData, $overrideData);
        $res = $this->post(
            sprintf('%s/%s/documents/%s/fragments', self::API, $knowledgeBaseCode, $documentCode),
            $data,
            $this->getCommonHeaders()
        );

        $this->assertSame(1000, $res['code'], $res['message']);
        return $res['data'];
    }
}
