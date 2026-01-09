<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Repository\Persistence\Model;

use App\Infrastructure\Core\AbstractModel;
use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * roleuserassociatemodel.
 *
 * @property int $id primary keyID
 * @property int $role_id roleID
 * @property string $user_id userID，对应delightful_contact_users.user_id
 * @property string $organization_code organizationencoding
 * @property null|string $assigned_by 分配者userID
 * @property null|Carbon $assigned_at 分配time
 * @property Carbon $created_at creation time
 * @property Carbon $updated_at update time
 * @property null|Carbon $deleted_at deletion time
 */
class RoleUserModel extends AbstractModel
{
    use Snowflake;
    use SoftDeletes;

    /**
     * 与modelassociate的表名.
     */
    protected ?string $table = 'delightful_role_users';

    /**
     * 可批量赋value的property.
     */
    protected array $fillable = [
        'id',
        'role_id',
        'user_id',
        'organization_code',
        'assigned_by',
        'assigned_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * propertytypeconvert.
     */
    protected array $casts = [
        'id' => 'int',
        'role_id' => 'int',
        'user_id' => 'string',
        'organization_code' => 'string',
        'assigned_by' => 'string',
        'assigned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * roleassociate.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(RoleModel::class, 'role_id', 'id');
    }
}
