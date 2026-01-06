<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace App\Domain\Flow\Repository\Persistence\Model;

use App\Infrastructure\Core\AbstractModel;
use Carbon\Carbon;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * @property int $id
 * @property string $message_id
 * @property int $type
 * @property string $model
 * @property string $analysis_result
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class DelightfulFlowMultiModalLogModel extends AbstractModel
{
    use Snowflake;

    public bool $timestamps = true;

    protected ?string $table = 'magic_flow_multi_modal_logs';

    protected array $fillable = [
        'id', 'message_id', 'type', 'model', 'analysis_result', 'created_at', 'updated_at',
    ];

    protected array $casts = [
        'id' => 'integer',
        'message_id' => 'string',
        'type' => 'integer',
        'model' => 'string',
        'analysis_result' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
