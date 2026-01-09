<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Comment\Repository\Model;

use App\Infrastructure\Core\AbstractModel;

/**
 * @property int $id
 * @property int $ancestor_id ancestorsectionpointid, commentstablemainkeyid
 * @property int $descendant_id backgenerationsectionpointid, commentstablemainkeyid
 * @property int $distance ancestorsectionpointtobackgenerationsectionpointdistance
 * @property string $organization_code organizationcode
 * @property string $created_at
 * @property string $updated_at
 */
class AbstractTreeIndexModel extends AbstractModel
{
}
