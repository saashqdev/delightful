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
 * @property int $business_type modepiecetype,filebelongatwhich modepiece
 * @property int $file_type filetype:0:officialadd,1:organizationadd
 * @property string $key filekey
 * @property int $file_size filesize
 * @property string $organization organizationencoding
 * @property string $file_extension filebacksuffix
 * @property string $user_id uploadpersonID
 * @property DateTime $created_at creation time
 * @property DateTime $updated_at update time
 * @property DateTime $deleted_at deletion time
 */
class DefaultFileModel extends Model
{
    use Snowflake;
    use SoftDeletes;

    /**
     * andmodelassociatetablename.
     */
    protected ?string $table = 'default_files';

    /**
     * canbatchquantityassignvalueproperty.
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
