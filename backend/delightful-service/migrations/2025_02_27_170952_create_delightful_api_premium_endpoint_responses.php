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
        // table存在就不执行
        if (Schema::hasTable('delightful_api_premium_endpoint_responses')) {
            return;
        }

        Schema::create('delightful_api_premium_endpoint_responses', function (Blueprint $table) {
            $table->bigIncrements('id');
            // request_id
            $table->string('request_id', 128)->nullable()->default(null)->comment('请求id');
            // endpoint_id
            $table->string('endpoint_id', 64)->nullable()->default(null)->comment('接入点id');
            // 请求parameterlength
            $table->integer('request_length')->nullable()->default(null)->comment('请求parameterlength');
            // 响应消耗的time，单位：毫秒
            $table->integer('response_time')->nullable()->default(null)->comment('响应消耗的time，单位：毫秒');
            // 响应 http status码
            $table->integer('http_status_code')->nullable()->default(null)->comment('响应 http status码');
            // 响应的业务status码
            $table->integer('business_status_code')->nullable()->default(null)->comment('响应的业务status码');
            // 是否请求success
            $table->boolean('is_success')->nullable()->default(null)->comment('是否请求success');
            // exceptiontype
            $table->string('exception_type', 255)->comment('exceptiontype')->nullable();
            // exceptioninfo
            $table->text('exception_message')->comment('exceptioninfo')->nullable();
            $table->datetimes();
            $table->index(['request_id'], 'request_id_index');
            // 为 endpoint_id 和 created_at 添加联合索引，用于按timerangequery特定端点的响应
            $table->index(['endpoint_id', 'created_at'], 'endpoint_id_created_at_index');
            $table->comment('接入点响应recordtable');
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
