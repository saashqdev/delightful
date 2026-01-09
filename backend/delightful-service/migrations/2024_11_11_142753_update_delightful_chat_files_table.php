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
                // file名
                $table->string('file_name', 256)->comment('file名')->change();
            } else {
                // file名
                $table->string('file_name', 256)->comment('file名');
            }

            if (Schema::hasColumn('delightful_chat_files', 'file_extension')) {
                // fileextension名
                $table->string('file_extension', 64)->comment('file后缀')->change();
            } else {
                // fileextension名
                $table->string('file_extension', 64)->comment('file后缀');
            }

            if (Schema::hasColumn('delightful_chat_files', 'file_type')) {
                // filetype
                $table->integer('file_type')->comment('filetype')->change();
            } else {
                // filetype
                $table->integer('file_type')->comment('filetype');
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
