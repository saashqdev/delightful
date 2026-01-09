<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\KnowledgeBase\Repository\Persistence\Model;

use App\Domain\KnowledgeBase\Entity\ValueObject\DocumentFile\Interfaces\DocumentFileInterface;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * 知识库文档model.
 * @property int $id primary keyID
 * @property string $organization_code organization编码
 * @property string $knowledge_base_code 知识库编码
 * @property string $name 文档name
 * @property string $code 文档编码
 * @property int $version version号
 * @property bool $enabled 是否启用
 * @property int $doc_type 文档type
 * @property array $doc_metadata 文档元数据
 * @property DocumentFileInterface $document_file 文档fileinfo
 * @property string $third_platform_type 第三方平台type
 * @property string $third_file_id 第三方fileID
 * @property int $sync_status syncstatus
 * @property int $sync_times sync次数
 * @property string $sync_status_message syncstatusmessage
 * @property string $embedding_model 嵌入model
 * @property string $vector_db 向量database
 * @property array $retrieve_config 检索configuration
 * @property array $fragment_config 片段configuration
 * @property array $embedding_config 嵌入configuration
 * @property array $vector_db_config 向量databaseconfiguration
 * @property string $created_uid create者UID
 * @property string $updated_uid update者UID
 * @property string $created_at createtime
 * @property string $updated_at updatetime
 * @property null|string $deleted_at deletetime
 * @property int $word_count 字数统计
 */
class KnowledgeBaseDocumentModel extends Model
{
    use SoftDeletes;
    use Snowflake;

    /**
     * 是否自增.
     */
    public bool $incrementing = true;

    /**
     * table名.
     */
    protected ?string $table = 'knowledge_base_documents';

    /**
     * primary key名.
     */
    protected string $primaryKey = 'id';

    /**
     * 可填充field.
     */
    protected array $fillable = [
        'organization_code',
        'knowledge_base_code',
        'name',
        'description',
        'code',
        'version',
        'enabled',
        'doc_type',
        'doc_metadata',
        'document_file',
        'third_platform_type',
        'third_file_id',
        'sync_status',
        'sync_times',
        'sync_status_message',
        'embedding_model',
        'vector_db',
        'retrieve_config',
        'fragment_config',
        'embedding_config',
        'vector_db_config',
        'created_uid',
        'updated_uid',
        'word_count',
    ];

    /**
     * type转换.
     */
    protected array $casts = [
        'id' => 'integer',
        'version' => 'integer',
        'enabled' => 'boolean',
        'doc_type' => 'integer',
        'doc_metadata' => 'json',
        'document_file' => 'json',
        'sync_status' => 'integer',
        'sync_times' => 'integer',
        'retrieve_config' => 'json',
        'fragment_config' => 'json',
        'embedding_config' => 'json',
        'vector_db_config' => 'json',
        'word_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
