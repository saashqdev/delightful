<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

use function Hyperf\Config\config;

class CreateTaskSchedulerLog extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('task_scheduler.table_names.task_scheduler_log', 'task_scheduler_log'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('task_id')->unsigned()->comment('taskID')->index();
            $table->string('external_id', 64)->comment('业务标识')->index();
            $table->string('name', 64)->comment('name');
            $table->dateTime('expect_time')->comment('expectedexecute时间');
            $table->dateTime('actual_time')->nullable()->comment('actualexecute时间');
            $table->tinyInteger('type')->default(2)->comment('type');
            $table->integer('cost_time')->default(0)->comment('耗时');
            $table->tinyInteger('status')->default(0)->comment('status');
            $table->json('callback_method')->comment('callbackmethod');
            $table->json('callback_params')->comment('callbackparameter');
            $table->string('remark', 255)->default('')->comment('note');
            $table->string('creator', 64)->default('')->comment('create人');
            $table->dateTime('created_at')->comment('creation time');
            $table->json('result')->nullable()->comment('result');
            $table->index(['status', 'expect_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('task_scheduler.table_names.task_scheduler_log', 'task_scheduler_log'));
    }
}
