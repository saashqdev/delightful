<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
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
            $table->string('external_id', 64)->comment('business id')->index();
            $table->string('name', 64)->comment('name');
            $table->dateTimeTz('expect_time')->comment('expected execution time');
            $table->dateTimeTz('actual_time')->nullable()->comment('actual execution time');
            $table->tinyInteger('type')->default(2)->comment('type');
            $table->integer('cost_time')->default(0)->comment('duration milliseconds');
            $table->integer('retry_times')->default(0)->comment('remaining retries');
            $table->tinyInteger('status')->default(0)->comment('status');
            $table->json('callback_method')->comment('callback method');
            $table->json('callback_params')->comment('callback parameters');
            $table->string('remark', 255)->default('')->comment('remark');
            $table->string('creator', 64)->default('')->comment('creator');
            $table->dateTimeTz('created_at')->comment('created at');
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
