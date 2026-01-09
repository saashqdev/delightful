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
        // table存inthennotexecute
        if (Schema::hasTable('delightful_api_premium_endpoint_statistics')) {
            return;
        }

        Schema::create('delightful_api_premium_endpoint_statistics', function (Blueprint $table) {
            $table->bigIncrements('id');
            // 这withincanimplement对接入pointrequest的statistics逻辑
            // for example：recordrequestcount、requesttype、resourceconsumeetc
            $table->string('endpoint_id', 64)->nullable()->default(null)->comment('接入pointid');
            $table->integer('request_count')->nullable()->default(null)->comment('requestcount');
            $table->integer('request_success_count')->nullable()->default(null)->comment('requestsuccesscount');
            $table->integer('request_error_count')->nullable()->default(null)->comment('requestfailcount');
            $table->double('request_success_rate')->nullable()->default(null)->comment('requestsuccessrate,most大value为 100，not带%');
            $table->integer('request_average_time')->nullable()->default(null)->comment('request平均time，unit毫second');
            $table->integer('request_max_time')->nullable()->default(null)->comment('requestconsume的most大time，unit毫second');
            $table->integer('request_min_time')->nullable()->default(null)->comment('requestconsume的most小time，unit毫second');
            // statisticstimesegment
            $table->bigInteger('statistics_time')->nullable()->default(null)->comment('statisticstimesegment');
            // statisticslevel别：0-secondlevel，1-minute钟level，2-hourlevel，3-daylevel
            $table->tinyInteger('statistics_level')->nullable()->default(null)->comment('statisticslevel别：0-secondlevel，1-minute钟level，2-hourlevel，3-daylevel');
            $table->datetimes();
            $table->unique(['endpoint_id', 'statistics_time', 'statistics_level'], 'unique_endpoint_id_statistics_level_time');
            $table->comment('接入pointrequeststatisticstable');
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
