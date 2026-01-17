<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Agent\Repository\Persistence\Model;

use App\Infrastructure\Core\AbstractModel;
use DateTime;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * @property int $id Snowflake ID
 * @property string $organization_code Organization code
 * @property string $code Unique code
 * @property string $name Agent name
 * @property string $description Agent description
 * @property array $icon Agent icon
 * @property int $icon_type Icon type
 * @property array $prompt System prompt
 * @property array $tools Tools list
 * @property int $type Agent type
 * @property bool $enabled Whether enabled
 * @property string $creator Creator
 * @property DateTime $created_at Creation time
 * @property string $modifier Modifier
 * @property DateTime $updated_at Update time
 * @property null|DateTime $deleted_at Deletion time
 */
class BeDelightfulAgentModel extends AbstractModel
{
    use Snowflake;
    use SoftDeletes;

    protected ?string $table = 'delightful_be_delightful_agents';

    protected array $fillable = [
        'id',
        'organization_code',
        'code',
        'name',
        'description',
        'icon',
        'icon_type',
        'prompt',
        'tools',
        'type',
        'enabled',
        'creator',
        'created_at',
        'modifier',
        'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'organization_code' => 'string',
        'code' => 'string',
        'name' => 'string',
        'description' => 'string',
        'icon' => 'array',
        'icon_type' => 'integer',
        'prompt' => 'array',
        'tools' => 'array',
        'type' => 'integer',
        'enabled' => 'boolean',
        'creator' => 'string',
        'modifier' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
}
