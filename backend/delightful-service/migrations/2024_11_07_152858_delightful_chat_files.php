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
            // upload者 user_id
            $table->string('user_id', 128)->comment('upload者user_id');
            // messageid
            $table->string('delightful_message_id', 64)->comment('messageid');
            // organizationencoding
            $table->string('organization_code', 64)->comment('organizationencoding');
            // filekey
            $table->string('file_key', 256)->comment('filekey');
            // filesize
            $table->unsignedBigInteger('file_size')->comment('filesize');
            // messageid索引
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
