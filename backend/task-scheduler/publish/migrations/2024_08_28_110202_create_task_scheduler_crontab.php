<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the software license
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
            $table->string('external_id', 64)->comment('business id')->index();
            $table->string('name', 64)->comment('name');
            $table->string('crontab', 64)->comment('crontab expression');
            $table->dateTime('last_gen_time')->nullable()->comment('last generation time');
            $table->boolean('enabled')->default(true)->comment('enabled');
            $table->integer('retry_times')->default(0)->comment('total retry attempts');
            $table->json('callback_method')->comment('callback method');
            $table->json('callback_params')->comment('callback parameters');
            $table->string('remark', 255)->default('')->comment('remark');
            $table->dateTime('deadline')->nullable()->comment('end time');
            $table->string('creator', 64)->default('')->comment('creator');
            $table->dateTime('created_at')->comment('created at');

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
