<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\MCP\Repository\Persistence\Model;

use App\Infrastructure\Core\AbstractModel;
use DateTime;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * @property int $id primary keyID
 * @property string $organization_code organization编码
 * @property string $mcp_server_code 关联的mcp服务code
 * @property string $name tool名称
 * @property string $description tooldescription
 * @property int $source tool来源
 * @property string $rel_code 关联的toolcode
 * @property string $rel_version_code 关联的toolversioncode
 * @property string $version toolversion
 * @property bool $enabled 是否启用
 * @property array $options toolconfiguration
 * @property array $rel_info 关联的information
 * @property string $creator create者
 * @property DateTime $created_at creation time
 * @property string $modifier 修改者
 * @property DateTime $updated_at update time
 */
class MCPServerToolModel extends AbstractModel
{
    use Snowflake;

    protected ?string $table = 'delightful_mcp_server_tools';

    protected array $fillable = [
        'id',
        'organization_code',
        'mcp_server_code',
        'name',
        'description',
        'source',
        'rel_code',
        'rel_version_code',
        'version',
        'enabled',
        'options',
        'rel_info',
        'creator',
        'created_at',
        'modifier',
        'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'organization_code' => 'string',
        'mcp_server_code' => 'string',
        'name' => 'string',
        'description' => 'string',
        'source' => 'integer',
        'rel_code' => 'string',
        'rel_version_code' => 'string',
        'version' => 'string',
        'enabled' => 'boolean',
        'options' => 'json',
        'rel_info' => 'json',
        'creator' => 'string',
        'modifier' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
