<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

use function Hyperf\Config\config;

class CreateTaskSchedulerCrontab extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('task_scheduler.table_names.task_scheduler_crontab', 'task_scheduler_crontab'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id', 64)->comment('业务 id')->index();
            $table->string('name', 64)->comment('name');
            $table->string('crontab', 64)->comment('crontab表达式');
            $table->dateTime('last_gen_time')->nullable()->comment('最后generatetime');
            $table->boolean('enabled')->default(true)->comment('是否enable');
            $table->integer('retry_times')->default(0)->comment('总retrycount');
            $table->json('callback_method')->comment('callbackmethod');
            $table->json('callback_params')->comment('callbackparameter');
            $table->string('remark', 255)->default('')->comment('note');
            $table->dateTime('deadline')->nullable()->comment('结束time');
            $table->string('creator', 64)->default('')->comment('create人');
            $table->dateTime('created_at')->comment('creation time');

            $table->index(['last_gen_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('task_scheduler.table_names.task_scheduler_crontab', 'task_scheduler_crontab'));
    }
}
