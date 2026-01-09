<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;
use Hyperf\DbConnection\Db;

class CreateDelightfulFileCleanupRecordsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delightful_file_cleanup_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code', 50)->comment('organizationencoding');
            $table->string('file_key', 500)->comment('filestoragekey');
            $table->string('file_name', 255)->comment('filename');
            $table->unsignedBigInteger('file_size')->default(0)->comment('filesize(字节)');
            $table->string('bucket_type', 20)->default('private')->comment('storage桶type');
            $table->string('source_type', 50)->comment('来源type(batch_compress,upload_tempetc)');
            $table->string('source_id', 100)->nullable()->comment('来源ID(optional的业务标识)');
            $table->timestamp('expire_at')->comment('expiretime');
            $table->tinyInteger('status')->default(0)->comment('status:0=待清理,1=已清理,2=清理failed');
            $table->tinyInteger('retry_count')->default(0)->comment('retrycount');
            $table->text('error_message')->nullable()->comment('errorinformation');
            $table->timestamp('created_at')->default(Db::raw('CURRENT_TIMESTAMP'))->comment('creation time');
            $table->timestamp('updated_at')->default(Db::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('update time');

            $table->index(['expire_at', 'status'], 'idx_expire_status');
            $table->index(['organization_code'], 'idx_organization_code');
            $table->index(['source_type'], 'idx_source_type');
            $table->index(['created_at'], 'idx_created_at');
            $table->index(['file_key', 'organization_code'], 'idx_file_key_org');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_file_cleanup_records');
    }
}
