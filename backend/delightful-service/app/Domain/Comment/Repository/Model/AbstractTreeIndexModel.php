<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Comment\Repository\Model;

use App\Infrastructure\Core\AbstractModel;

/**
 * @property int $id
 * @property int $ancestor_id 祖先sectionpointid, commentstable的主键id
 * @property int $descendant_id back代sectionpointid, commentstable的主键id
 * @property int $distance 祖先sectionpointtoback代sectionpoint的距离
 * @property string $organization_code organizationcode
 * @property string $created_at
 * @property string $updated_at
 */
class AbstractTreeIndexModel extends AbstractModel
{
}
