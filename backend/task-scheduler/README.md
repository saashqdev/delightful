# dtyq/task-scheduler

## Installation
```
composer require dtyq/task-scheduler
php bin/hyperf.php vendor:publish dtyq/task-scheduler
```

## Usage
```
\Dtyq\TaskScheduler\Service\TaskSchedulerDomainService
```

## Notes
> Supports minute-level invocation only

Scheduling modes
1. Cron scheduling
2. Fixed-time scheduling

Creating scheduled tasks
1. Cron schedules need a timer to generate tasks to run within the next n hours.
2. Generate scheduled tasks based on the task time.

Executing tasks
1. Run due tasks, update status, and emit an error event on failure.
2. After finishing, archive the record to the log table.

Background jobs
1. Daily: delete completed tasks older than n days to prevent the schedule table from growing too large.
2. Every minute: generate tasks to run within the next n days.
3. Every minute: execute tasks whose time has arrived.

Database
1. task_scheduler: records tasks to be executed.
2. task_scheduler_log: archives completed tasks for later review.
3. task_scheduler_crontab: stores cron rules.

## Create the tables
```shell
php bin/hyperf.php migrate
```

```sql
-- auto-generated definition
create table task_scheduler
(
    id              bigint unsigned         not null primary key,
    external_id     varchar(64)             not null comment 'business id',
    name            varchar(64)             not null comment 'name',
    expect_time     datetime                not null comment 'expected execution time',
    actual_time     datetime                null comment 'actual execution time',
    type            tinyint      default 2  not null comment 'schedule type: 1 cron, 2 fixed time',
    cost_time       int          default 0  not null comment 'duration milliseconds',
    retry_times     int          default 0  not null comment 'remaining retries',
    status          tinyint      default 0  not null comment 'status',
    callback_method json                    not null comment 'callback method',
    callback_params json                    not null comment 'callback parameters',
    remark          varchar(255) default '' not null comment 'remark',
    creator         varchar(64)  default '' not null comment 'creator',
    created_at      datetime                not null comment 'created at'
)
    collate = utf8mb4_unicode_ci;

create index task_scheduler_external_id_index
    on task_scheduler (external_id);

create index task_scheduler_status_expect_time_index
    on task_scheduler (status, expect_time);

-- auto-generated definition
create table task_scheduler_crontab
(
    id              bigint unsigned         not null primary key,
    name            varchar(64)             not null comment 'name',
    crontab         varchar(64)             not null comment 'crontab expression',
    last_gen_time   datetime                null comment 'last generation time',
    enabled         tinyint(1)   default 1  not null comment 'enabled',
    retry_times     int          default 0  not null comment 'total retry attempts',
    callback_method json                    not null comment 'callback method',
    callback_params json                    not null comment 'callback parameters',
    remark          varchar(255) default '' not null comment 'remark',
    creator         varchar(64)  default '' not null comment 'creator',
    created_at      datetime                not null comment 'created at'
)
    collate = utf8mb4_unicode_ci;



-- auto-generated definition
-- auto-generated definition
create table task_scheduler_log
(
    id              bigint unsigned         not null primary key,
    task_id         bigint unsigned         not null comment 'task ID',
    external_id     varchar(64)             not null comment 'business identifier',
    name            varchar(64)             not null comment 'name',
    expect_time     datetime                not null comment 'expected execution time',
    actual_time     datetime                null comment 'actual execution time',
    type            tinyint      default 2  not null comment 'type',
    cost_time       int          default 0  not null comment 'duration',
    status          tinyint      default 0  not null comment 'status',
    callback_method json                    not null comment 'callback method',
    callback_params json                    not null comment 'callback parameters',
    remark          varchar(255) default '' not null comment 'remark',
    creator         varchar(64)  default '' not null comment 'creator',
    created_at      datetime                not null comment 'created at',
    result          json                    null comment 'result'
)
    collate = utf8mb4_unicode_ci;

create index task_scheduler_log_external_id_index
    on task_scheduler_log (external_id);

create index task_scheduler_log_status_expect_time_index
    on task_scheduler_log (status, expect_time);

create index task_scheduler_log_task_id_index
    on task_scheduler_log (task_id);
```
