<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\File\Repository\Persistence\Model;

use DateTime;
use Hyperf\Database\Model\Model;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * @property int $id primary keyID
 * @property int $business_type 模块type，file属于哪个模块
 * @property int $file_type filetype：0:官方添加，1:organization添加
 * @property string $key filekey
 * @property int $file_size filesize
 * @property string $organization organizationencoding
 * @property string $file_extension file后缀
 * @property string $user_id upload者ID
 * @property DateTime $created_at creation time
 * @property DateTime $updated_at update time
 * @property DateTime $deleted_at deletion time
 */
class DefaultFileModel extends Model
{
    use Snowflake;
    use SoftDeletes;

    /**
     * 与modelassociate的表名.
     */
    protected ?string $table = 'default_files';

    /**
     * 可批量赋value的property.
     */
    protected array $fillable = [
        'id',
        'business_type',
        'file_type',
        'key',
        'file_size',
        'organization',
        'file_extension',
        'user_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
