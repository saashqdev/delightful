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
 * @property string $organization_code organizationencoding
 * @property string $mcp_server_code associatemcpservicecode
 * @property string $name toolname
 * @property string $description tooldescription
 * @property int $source toolcomesource
 * @property string $rel_code associatetoolcode
 * @property string $rel_version_code associatetoolversioncode
 * @property string $version toolversion
 * @property bool $enabled whetherenable
 * @property array $options toolconfiguration
 * @property array $rel_info associateinformation
 * @property string $creator createperson
 * @property DateTime $created_at creation time
 * @property string $modifier modifyperson
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
