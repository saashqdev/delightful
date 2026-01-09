<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Persistence\Model;

use App\Infrastructure\Core\AbstractModel;
use DateTime;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * @property int $id 雪花ID
 * @property string $organization_code organizationencoding
 * @property string $delightful_id 账号DelightfulID
 * @property string $user_id userID
 * @property string $key setting键
 * @property array $value setting值
 * @property string $creator create者
 * @property DateTime $created_at create时间
 * @property string $modifier 修改者
 * @property DateTime $updated_at update时间
 */
class UserSettingModel extends AbstractModel
{
    use Snowflake;

    protected ?string $table = 'delightful_user_settings';

    protected array $fillable = [
        'id',
        'organization_code',
        'delightful_id',
        'user_id',
        'key',
        'value',
        'creator',
        'created_at',
        'modifier',
        'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'organization_code' => 'string',
        'delightful_id' => 'string',
        'user_id' => 'string',
        'key' => 'string',
        'value' => 'json',
        'creator' => 'string',
        'created_at' => 'datetime',
        'modifier' => 'string',
        'updated_at' => 'datetime',
    ];
}
