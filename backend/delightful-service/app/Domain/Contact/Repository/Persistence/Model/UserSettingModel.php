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
 * @property int $id snowflowerID
 * @property string $organization_code organizationencoding
 * @property string $delightful_id accountnumberDelightfulID
 * @property string $user_id userID
 * @property string $key settingkey
 * @property array $value settingvalue
 * @property string $creator createperson
 * @property DateTime $created_at createtime
 * @property string $modifier modifyperson
 * @property DateTime $updated_at updatetime
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
