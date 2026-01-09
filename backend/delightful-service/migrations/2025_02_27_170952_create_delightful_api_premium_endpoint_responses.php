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
        if (Schema::hasTable('delightful_api_premium_endpoint_responses')) {
            return;
        }

        Schema::create('delightful_api_premium_endpoint_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            // request_id
            $table->string('request_id', 128)->nullable()->default(null)->comment('requestid');
            // endpoint_id
            $table->string('endpoint_id', 64)->nullable()->default(null)->comment('接入pointid');
            // requestparameterlength
            $table->integer('request_length')->nullable()->default(null)->comment('requestparameterlength');
            // responseconsumetime，unit：毫second
            $table->integer('response_time')->nullable()->default(null)->comment('responseconsumetime，unit：毫second');
            // response http status码
            $table->integer('http_status_code')->nullable()->default(null)->comment('response http status码');
            // response业务status码
            $table->integer('business_status_code')->nullable()->default(null)->comment('response业务status码');
            // whetherrequestsuccess
            $table->boolean('is_success')->nullable()->default(null)->comment('whetherrequestsuccess');
            // exceptiontype
            $table->string('exception_type', 255)->comment('exceptiontype')->nullable();
            // exceptioninfo
            $table->text('exception_message')->comment('exceptioninfo')->nullable();
            $table->datetimes();
            $table->index(['request_id'], 'request_id_index');
            // for endpoint_id and created_at add联合index，useat按timerangequery特定端pointresponse
            $table->index(['endpoint_id', 'created_at'], 'endpoint_id_created_at_index');
            $table->comment('接入pointresponserecordtable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_api_premium_endpoint_responses');
    }
};
