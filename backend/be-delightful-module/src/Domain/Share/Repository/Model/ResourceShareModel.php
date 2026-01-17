<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\BeDelightful\Domain\Share\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Hyperf\Database\Model\SoftDeletes;

/**
 * Resource share model.
 *
 * @property int $id ID
 * @property string $resource_id Resource ID
 * @property int $resource_type Resource type
 * @property string $resource_name Resource name
 * @property string $share_code Share code
 * @property int $share_type Share type
 * @property null|string $password Access password
 * @property bool $is_password_enabled Whether password protection is enabled
 * @property null|string $expire_at Expiration time
 * @property int $view_count View count
 * @property string $created_uid Creator user ID
 * @property string $updated_uid Updater user ID
 * @property string $organization_code Organization code
 * @property null|string $target_ids Target IDs
 * @property null|array $extra Extra attributes
 * @property bool $is_enabled Whether enabled
 * @property string $created_at Creation time
 * @property string $updated_at Update time
 * @property null|string $deleted_at Deletion time
 */
class ResourceShareModel extends AbstractModel
{
    use SoftDeletes;

    /**
     * Table name.
     */
    protected ?string $table = 'delightful_resource_shares';

    /**
     * Primary key.
     */
    protected string $primaryKey = 'id';

    /**
     * Mass assignable attributes.
     */
    protected array $fillable = [
        'id',
        'resource_id',
        'resource_type',
        'resource_name',
        'share_code',
        'share_type',
        'password',
        'is_password_enabled',
        'expire_at',
        'view_count',
        'created_uid',
        'updated_uid',
        'organization_code',
        'target_ids',
        'extra',
        'is_enabled',
        'deleted_at',
    ];

    /**
     * Automatic type casting.
     */
    protected array $casts = [
        'id' => 'integer',
        'resource_type' => 'integer',
        'share_type' => 'integer',
        'view_count' => 'integer',
        'target_ids' => 'json',
        'extra' => 'json',
        'is_enabled' => 'boolean',
        'is_password_enabled' => 'boolean',
        'created_at' => 'string',
        'updated_at' => 'string',
        'deleted_at' => 'string',
        'expire_at' => 'string',
    ];
}
