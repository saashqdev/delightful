<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
 */

namespace App\Domain\Admin\Repository\Persistence\Model;

use Hyperf\DbConnection\Model\Model;
use Hyperf\Snowflake\Concern\Snowflake;

class AdminGlobalSettingsModel extends Model
{
    use Snowflake;

    protected ?string $table = 'admin_global_settings';

    protected array $fillable = [
        'id',
        'type',
        'status',
        'extra',
        'organization',
        'created_at',
        'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'extra' => 'json',
        'organization' => 'string',
    ];
}
