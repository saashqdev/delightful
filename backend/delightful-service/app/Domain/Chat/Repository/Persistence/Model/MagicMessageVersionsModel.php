<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Chat\Repository\Persistence\Model;

use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

class DelightfulMessageVersionsModel extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'delightful_chat_message_versions';

    /**
     * The connection name for the model.
     */
    protected ?string $connection = 'default';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = [
        'version_id',
        'delightful_message_id',
        'message_content',
        'message_type',
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
