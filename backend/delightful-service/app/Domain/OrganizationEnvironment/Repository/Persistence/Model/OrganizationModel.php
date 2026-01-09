<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\OrganizationEnvironment\Repository\Persistence\Model;

use App\Infrastructure\Core\AbstractModel;
use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * organizationmodel.
 *
 * @property int $id primary keyID
 * @property string $delightful_organization_code
 * @property string $name organizationname
 * @property null|string $platform_type 平台type
 * @property null|string $logo organizationlogo
 * @property null|string $introduction 企业description
 * @property null|string $contact_user 联系人
 * @property null|string $contact_mobile 联系电话
 * @property string $industry_type organization行业type
 * @property null|string $number 企业规模
 * @property int $status status 1:正常 2:禁用
 * @property null|string $creator_id create人
 * @property int $type
 * @property null|int $seats 席位数
 * @property null|string $sync_type 同步type
 * @property null|int $sync_status 同步status
 * @property null|Carbon $sync_time 同步time
 * @property Carbon $created_at createtime
 * @property Carbon $updated_at updatetime
 * @property null|Carbon $deleted_at deletetime
 */
class OrganizationModel extends AbstractModel
{
    use Snowflake;
    use SoftDeletes;

    /**
     * statusconstant.
     */
    public const int STATUS_NORMAL = 1;

    public const int STATUS_DISABLED = 2;

    /**
     * 与model关联的table名.
     */
    protected ?string $table = 'delightful_organizations';

    /**
     * 可批量赋value的property.
     */
    protected array $fillable = [
        'id',
        'delightful_organization_code',
        'name',
        'platform_type',
        'logo',
        'introduction',
        'contact_user',
        'contact_mobile',
        'industry_type',
        'number',
        'status',
        'creator_id',
        'type',
        'seats',
        'sync_type',
        'sync_status',
        'sync_time',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * propertytype转换.
     */
    protected array $casts = [
        'id' => 'int',
        'delightful_organization_code' => 'string',
        'name' => 'string',
        'platform_type' => 'string',
        'logo' => 'string',
        'introduction' => 'string',
        'contact_user' => 'string',
        'contact_mobile' => 'string',
        'industry_type' => 'string',
        'number' => 'string',
        'status' => 'int',
        'creator_id' => 'string',
        'type' => 'int',
        'seats' => 'int',
        'sync_type' => 'string',
        'sync_status' => 'int',
        'sync_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * 启用organization.
     */
    public function enable(): void
    {
        $this->status = self::STATUS_NORMAL;
    }

    /**
     * 禁用organization.
     */
    public function disable(): void
    {
        $this->status = self::STATUS_DISABLED;
    }
}
