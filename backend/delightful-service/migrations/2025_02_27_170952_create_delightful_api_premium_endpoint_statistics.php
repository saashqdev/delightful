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
        // tableexistsinthennotexecute
        if (Schema::hasTable('delightful_api_premium_endpoint_statistics')) {
            return;
        }

        Schema::create('delightful_api_premium_endpoint_statistics', function (Blueprint $table) {
            $table->bigIncrements('id');
            // thiswithincanimplementtoaccesspointrequeststatisticslogic
            // for example:recordrequestcount,requesttype,resourceconsumeetc
            $table->string('endpoint_id', 64)->nullable()->default(null)->comment('accesspointid');
            $table->integer('request_count')->nullable()->default(null)->comment('requestcount');
            $table->integer('request_success_count')->nullable()->default(null)->comment('requestsuccesscount');
            $table->integer('request_error_count')->nullable()->default(null)->comment('requestfailcount');
            $table->double('request_success_rate')->nullable()->default(null)->comment('requestsuccessrate,mostbigvaluefor 100,notwith%');
            $table->integer('request_average_time')->nullable()->default(null)->comment('requestaveragetime,unitmillisecondssecond');
            $table->integer('request_max_time')->nullable()->default(null)->comment('requestconsumemostbigtime,unitmillisecondssecond');
            $table->integer('request_min_time')->nullable()->default(null)->comment('requestconsumemostsmalltime,unitmillisecondssecond');
            // statisticstimesegment
            $table->bigInteger('statistics_time')->nullable()->default(null)->comment('statisticstimesegment');
            // statisticslevelother:0-secondlevel,1-minutesecondslevel,2-hourlevel,3-daylevel
            $table->tinyInteger('statistics_level')->nullable()->default(null)->comment('statisticslevelother:0-secondlevel,1-minutesecondslevel,2-hourlevel,3-daylevel');
            $table->datetimes();
            $table->unique(['endpoint_id', 'statistics_time', 'statistics_level'], 'unique_endpoint_id_statistics_level_time');
            $table->comment('accesspointrequeststatisticstable');
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
