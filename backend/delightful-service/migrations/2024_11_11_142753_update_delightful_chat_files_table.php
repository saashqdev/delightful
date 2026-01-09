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
        Schema::table('delightful_chat_files', static function (Blueprint $table) {
            if (Schema::hasColumn('delightful_chat_files', 'file_name')) {
                // 文件名
                $table->string('file_name', 256)->comment('文件名')->change();
            } else {
                // 文件名
                $table->string('file_name', 256)->comment('文件名');
            }

            if (Schema::hasColumn('delightful_chat_files', 'file_extension')) {
                // 文件扩展名
                $table->string('file_extension', 64)->comment('文件后缀')->change();
            } else {
                // 文件扩展名
                $table->string('file_extension', 64)->comment('文件后缀');
            }

            if (Schema::hasColumn('delightful_chat_files', 'file_type')) {
                // 文件type
                $table->integer('file_type')->comment('文件type')->change();
            } else {
                // 文件type
                $table->integer('file_type')->comment('文件type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_chat_files', function (Blueprint $table) {
        });
    }
};
