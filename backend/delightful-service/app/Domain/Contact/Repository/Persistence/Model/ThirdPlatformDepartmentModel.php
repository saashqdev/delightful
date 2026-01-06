<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Contact\Repository\Persistence\Model;

use Hyperf\DbConnection\Model\Model;

class ThirdPlatformDepartmentModel extends Model
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'delightful_contact_third_platform_departments';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'id',
        'delightful_department_id',
        'delightful_organization_code',
        'third_leader_user_id',
        'third_department_id',
        'third_parent_department_id',
        'third_name',
        'third_i18n_name',
        'third_platform_type',
        'third_platform_departments_extra',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = [
        'id' => 'string',
    ];
}
