<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // table存在就不execute
        if (Schema::hasTable('delightful_api_premium_endpoint_statistics')) {
            return;
        }

        Schema::create('delightful_api_premium_endpoint_statistics', function (Blueprint $table) {
            $table->bigIncrements('id');
            // 这里canimplement对接入点request的statistics逻辑
            // for example：recordrequest次数、requesttype、资源consume等
            $table->string('endpoint_id', 64)->nullable()->default(null)->comment('接入点id');
            $table->integer('request_count')->nullable()->default(null)->comment('request次数');
            $table->integer('request_success_count')->nullable()->default(null)->comment('requestsuccess次数');
            $table->integer('request_error_count')->nullable()->default(null)->comment('requestfail次数');
            $table->double('request_success_rate')->nullable()->default(null)->comment('requestsuccess率,最大value为 100，不带%');
            $table->integer('request_average_time')->nullable()->default(null)->comment('request平均time，单位毫秒');
            $table->integer('request_max_time')->nullable()->default(null)->comment('requestconsume的最大time，单位毫秒');
            $table->integer('request_min_time')->nullable()->default(null)->comment('requestconsume的最小time，单位毫秒');
            // statisticstime段
            $table->bigInteger('statistics_time')->nullable()->default(null)->comment('statisticstime段');
            // statistics级别：0-秒级，1-分钟级，2-小时级，3-天级
            $table->tinyInteger('statistics_level')->nullable()->default(null)->comment('statistics级别：0-秒级，1-分钟级，2-小时级，3-天级');
            $table->datetimes();
            $table->unique(['endpoint_id', 'statistics_time', 'statistics_level'], 'unique_endpoint_id_statistics_level_time');
            $table->comment('接入点requeststatisticstable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_premium_endpoint_statistics');
    }
};
