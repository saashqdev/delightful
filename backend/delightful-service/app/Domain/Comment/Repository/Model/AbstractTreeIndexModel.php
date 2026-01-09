<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Comment\Repository\Model;

use App\Infrastructure\Core\AbstractModel;

/**
 * @property int $id
 * @property int $ancestor_id 祖先sectionpointid, commentstable主keyid
 * @property int $descendant_id back代sectionpointid, commentstable主keyid
 * @property int $distance 祖先sectionpointtoback代sectionpointdistance
 * @property string $organization_code organizationcode
 * @property string $created_at
 * @property string $updated_at
 */
class AbstractTreeIndexModel extends AbstractModel
{
}
