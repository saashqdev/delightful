<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Authentication\Repository\Persistence\Model;

use App\Infrastructure\Core\AbstractModel;
use DateTime;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * when前表先延use之前的表，thenmappingto通use的实体中，all面上线后，need回来more改field名.
 * @property int $id
 * @property string $organization_code
 * @property string $code
 * @property int $type associatetype 1: flow 2: mcp
 * @property string $flow_code associate code
 * @property string $conversation_id
 * @property string $name
 * @property string $description
 * @property string $secret_key
 * @property bool $enabled
 * @property null|DateTime $last_used
 * @property string $created_uid
 * @property string $created_at
 * @property string $updated_uid
 * @property string $updated_at
 */
class ApiKeyProviderModel extends AbstractModel
{
    use Snowflake;
    use SoftDeletes;

    protected ?string $table = 'delightful_flow_api_keys';

    protected array $fillable = [
        'id',
        'organization_code',
        'code',
        'flow_code',
        'conversation_id',
        'type',
        'name',
        'description',
        'secret_key',
        'enabled',
        'last_used',
        'created_uid',
        'created_at',
        'updated_uid',
        'updated_at',
        'deleted_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'organization_code' => 'string',
        'code' => 'string',
        'flow_code' => 'string',
        'conversation_id' => 'string',
        'type' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'secret_key' => 'string',
        'enabled' => 'bool',
        'last_used' => 'datetime',
        'created_uid' => 'string',
        'created_at' => 'datetime',
        'updated_uid' => 'string',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
