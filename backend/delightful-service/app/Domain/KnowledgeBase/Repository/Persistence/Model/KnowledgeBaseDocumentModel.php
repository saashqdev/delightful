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
 * knowledge basedocumentmodel.
 * @property int $id primary keyID
 * @property string $organization_code organizationencoding
 * @property string $knowledge_base_code knowledge baseencoding
 * @property string $name documentname
 * @property string $code documentencoding
 * @property int $version versionnumber
 * @property bool $enabled whetherenable
 * @property int $doc_type documenttype
 * @property array $doc_metadata documentyuandata
 * @property DocumentFileInterface $document_file documentfileinfo
 * @property string $third_platform_type thethree方platformtype
 * @property string $third_file_id thethree方fileID
 * @property int $sync_status syncstatus
 * @property int $sync_times synccount
 * @property string $sync_status_message syncstatusmessage
 * @property string $embedding_model embeddingmodel
 * @property string $vector_db toquantitydatabase
 * @property array $retrieve_config retrieveconfiguration
 * @property array $fragment_config slicesegmentconfiguration
 * @property array $embedding_config embeddingconfiguration
 * @property array $vector_db_config toquantitydatabaseconfiguration
 * @property string $created_uid create者UID
 * @property string $updated_uid update者UID
 * @property string $created_at createtime
 * @property string $updated_at updatetime
 * @property null|string $deleted_at deletetime
 * @property int $word_count word countstatistics
 */
class KnowledgeBaseDocumentModel extends Model
{
    use SoftDeletes;
    use Snowflake;

    /**
     * whetherfrom增.
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
     * canpopulatefield.
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
     * typeconvert.
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
