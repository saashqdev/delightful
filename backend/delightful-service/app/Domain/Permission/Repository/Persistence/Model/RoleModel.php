<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Permission\Repository\Persistence\Model;

use App\Infrastructure\Core\AbstractModel;
use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * rolemodel.
 *
 * @property int $id primary keyID
 * @property string $name rolename
 * @property array $permission_key rolepermissionlist
 * @property string $organization_code organizationencoding
 * @property null|array $permission_tag permissiontag，用于前端展示category
 * @property int $is_display 是否显示
 * @property int $status status: 0=禁用, 1=启用
 * @property null|string $created_uid create者userID
 * @property null|string $updated_uid update者userID
 * @property Carbon $created_at createtime
 * @property Carbon $updated_at updatetime
 * @property null|Carbon $deleted_at deletetime
 */
class RoleModel extends AbstractModel
{
    use Snowflake;
    use SoftDeletes;

    /**
     * statusconstant.
     */
    public const int STATUS_DISABLED = 0;

    public const int STATUS_ENABLED = 1;

    /**
     * 与model关联的table名.
     */
    protected ?string $table = 'delightful_roles';

    /**
     * 可批量赋value的property.
     */
    protected array $fillable = [
        'id',
        'name',
        'permission_key',
        'organization_code',
        'permission_tag',
        'is_display',
        'status',
        'created_uid',
        'updated_uid',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * propertytype转换.
     */
    protected array $casts = [
        'id' => 'int',
        'name' => 'string',
        'permission_key' => 'array',
        'organization_code' => 'string',
        'permission_tag' => 'array',
        'is_display' => 'int',
        'status' => 'int',
        'created_uid' => 'string',
        'updated_uid' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * getpermissionlist.
     */
    public function getPermissions(): array
    {
        return $this->permission_key ?? [];
    }

    /**
     * setpermissionlist.
     */
    public function setPermissions(array $permissions): void
    {
        $this->permission_key = $permissions;
    }

    /**
     * getpermissiontag.
     */
    public function getPermissionTag(): ?array
    {
        return $this->permission_tag;
    }

    /**
     * setpermissiontag.
     */
    public function setPermissionTag(?array $permissionTag): void
    {
        $this->permission_tag = $permissionTag;
    }

    /**
     * checkrole是否启用.
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 启用role.
     */
    public function enable(): void
    {
        $this->status = self::STATUS_ENABLED;
    }

    /**
     * 禁用role.
     */
    public function disable(): void
    {
        $this->status = self::STATUS_DISABLED;
    }
}
