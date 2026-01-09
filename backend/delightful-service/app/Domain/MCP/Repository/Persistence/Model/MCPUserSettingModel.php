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
 * @property int $id 雪flowerID
 * @property string $organization_code organizationencoding
 * @property string $user_id userID
 * @property string $mcp_server_id MCPserviceID
 * @property null|array $require_fields requiredfield
 * @property null|array $oauth2_auth_result OAuth2authenticationresult
 * @property null|array $additional_config attachaddconfiguration
 * @property string $creator createperson
 * @property DateTime $created_at creation time
 * @property string $modifier modifyperson
 * @property DateTime $updated_at update time
 */
class MCPUserSettingModel extends AbstractModel
{
    use Snowflake;

    protected ?string $table = 'delightful_mcp_user_settings';

    protected array $fillable = [
        'id',
        'organization_code',
        'user_id',
        'mcp_server_id',
        'require_fields',
        'oauth2_auth_result',
        'additional_config',
        'creator',
        'created_at',
        'modifier',
        'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'organization_code' => 'string',
        'user_id' => 'string',
        'mcp_server_id' => 'string',
        'require_fields' => 'array',
        'oauth2_auth_result' => 'array',
        'additional_config' => 'array',
        'creator' => 'string',
        'modifier' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
