<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */

namespace Delightful\AsyncEvent\Kernel\Persistence\Model;

use Carbon\Carbon;
use Hyperf\DbConnection\Model\Model;
use Hyperf\Snowflake\Concern\Snowflake;

/**
 * @property int $id
 * @property int $status
 * @property string $event
 * @property string $listener
 * @property int $retry_times
 * @property string $args
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AsyncEventModel extends Model
{
    use Snowflake;

    public function __construct(array $attributes = [])
    {
        // To adapt to hyperf2 and hyperf3 property definition issues, table name needs to be specified manually here
        $this->table = 'async_event_records';
        $this->fillable = [
            'id', 'status', 'event', 'listener', 'retry_times', 'args', 'created_at', 'updated_at',
        ];
        $this->casts = [
            'id' => 'integer',
            'status' => 'integer',
            'retry_times' => 'integer',
            'event' => 'string',
            'listener' => 'string',
            'args' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        parent::__construct($attributes);
    }
}
