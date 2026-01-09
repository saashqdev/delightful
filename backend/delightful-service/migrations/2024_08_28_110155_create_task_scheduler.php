<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

use function Hyperf\Config\config;

class CreateTaskScheduler extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(config('task_scheduler.table_names.task_scheduler', 'task_scheduler'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('external_id', 64)->comment('业务 id')->index();
            $table->string('name', 64)->comment('name');
            $table->dateTimeTz('expect_time')->comment('expectedexecutetime');
            $table->dateTimeTz('actual_time')->nullable()->comment('actualexecutetime');
            $table->tinyInteger('type')->default(2)->comment('type');
            $table->integer('cost_time')->default(0)->comment('耗o clock 毫second');
            $table->integer('retry_times')->default(0)->comment('remainingretrycount');
            $table->tinyInteger('status')->default(0)->comment('status');
            $table->json('callback_method')->comment('callbackmethod');
            $table->json('callback_params')->comment('callbackparameter');
            $table->string('remark', 255)->default('')->comment('note');
            $table->string('creator', 64)->default('')->comment('createperson');
            $table->dateTimeTz('created_at')->comment('creation time');
            $table->index(['status', 'expect_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(config('task_scheduler.table_names.task_scheduler', 'task_scheduler'));
    }
}
