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
        if (Schema::hasTable('delightful_chat_files')) {
            return;
        }
        Schema::create('delightful_chat_files', static function (Blueprint $table) {
            $table->bigIncrements('file_id');
            // 上传者的 user_id
            $table->string('user_id', 128)->comment('上传者的user_id');
            // 消息id
            $table->string('delightful_message_id', 64)->comment('消息id');
            // 组织编码
            $table->string('organization_code', 64)->comment('组织编码');
            // 文件key
            $table->string('file_key', 256)->comment('文件key');
            // 文件大小
            $table->unsignedBigInteger('file_size')->comment('文件大小');
            // 消息id索引
            $table->index('delightful_message_id', 'idx_delightful_message_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('', function (Blueprint $table) {
        });
    }
};
