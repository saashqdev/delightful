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
                // filename
                $table->string('file_name', 256)->comment('filename')->change();
            } else {
                // filename
                $table->string('file_name', 256)->comment('filename');
            }

            if (Schema::hasColumn('delightful_chat_files', 'file_extension')) {
                // fileextensionname
                $table->string('file_extension', 64)->comment('filebacksuffix')->change();
            } else {
                // fileextensionname
                $table->string('file_extension', 64)->comment('filebacksuffix');
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
