<?php
declare(strict_types=1);

/** * Copyright (c) Be Delightful , Distributed under the MIT software license */ 

namespace Delightful\BeDelightful\Domain\Share\Repository\Model;

use App\Infrastructure\Core\AbstractModel;
use Hyperf\Database\Model\Softdelete s;
/** * ResourceShareModel. * * @property int $id ID * @property string $resource_id ResourceID * @property int $resource_type ResourceType * @property string $resource_name ResourceName * @property string $share_code Share code * @property int $share_type ShareType * @property null|string $password Password * @property bool $is_password_enabled whether EnabledPasswordProtected * @property null|string $expire_at Expiration time * @property int $view_count View * @property string $created_uid creator user ID * @property string $updated_uid Updateuser ID * @property string $organization_code OrganizationCode * @property null|string $target_ids TargetIDs * @property null|array $extra ExtraProperty * @property bool $is_enabled whether Enabled * @property string $created_at Creation time * @property string $updated_at Update time * @property null|string $deleted_at Deletion time */

class ResourceShareModel extends AbstractModel 
{
 use Softdelete s;
/** * table . */ protected ?string $table = 'magic_resource_shares'; /** * primary key . */ 
    protected string $primaryKey = 'id'; /** * BatchValueProperty. */ 
    protected array $fillable = [ 'id', 'resource_id', 'resource_type', 'resource_name', 'share_code', 'share_type', 'password', 'is_password_enabled', 'expire_at', 'view_count', 'created_uid', 'updated_uid', 'organization_code', 'target_ids', 'extra', 'is_enabled', 'deleted_at', ]; /** * automatic TypeConvert. */ 
    protected array $casts = [ 'id' => 'integer', 'resource_type' => 'integer', 'share_type' => 'integer', 'view_count' => 'integer', 'target_ids' => 'json', 'extra' => 'json', 'is_enabled' => 'boolean', 'is_password_enabled' => 'boolean', 'created_at' => 'string', 'updated_at' => 'string', 'deleted_at' => 'string', 'expire_at' => 'string', ]; 
}
 
