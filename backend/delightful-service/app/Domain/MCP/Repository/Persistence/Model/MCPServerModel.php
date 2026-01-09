<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\MCP\Repository\Persistence\Model;

use App\Infrastructure\Core\AbstractModel;
use DateTime;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * @property int $id 雪flowerID
 * @property string $organization_code organizationencoding
 * @property string $code 唯一encoding
 * @property string $name MCPservicename
 * @property string $description MCPservicedescription
 * @property string $icon MCPservice图标
 * @property string $type servicetype ('sse' or 'stdio')
 * @property bool $enabled whetherenable
 * @property string $external_sse_url outside部SSEserviceURL
 * @property null|array $service_config serviceconfiguration
 * @property string $creator create者
 * @property DateTime $created_at creation time
 * @property string $modifier 修改者
 * @property DateTime $updated_at update time
 */
class MCPServerModel extends AbstractModel
{
    use Snowflake;
    use SoftDeletes;

    protected ?string $table = 'delightful_mcp_servers';

    protected array $fillable = [
        'id',
        'organization_code',
        'code',
        'name',
        'description',
        'icon',
        'type',
        'enabled',
        'external_sse_url',
        'service_config',
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
        'icon' => 'string',
        'type' => 'string',
        'enabled' => 'boolean',
        'external_sse_url' => 'string',
        'service_config' => 'array',
        'creator' => 'string',
        'modifier' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tools(): HasMany
    {
        /* @phpstan-ignore-next-line */
        return $this->hasMany(MCPServerToolModel::class, 'mcp_server_code', 'code');
    }
}
