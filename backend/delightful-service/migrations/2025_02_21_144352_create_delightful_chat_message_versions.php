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
        if (Schema::hasTable('delightful_chat_message_versions')) {
            return;
        }
        Schema::create('delightful_chat_message_versions', function (Blueprint $table) {
            $table->bigIncrements('version_id');
            $table->string('delightful_message_id', 64)->comment('delightful_chat_message table delightful_message_id');
            $table->longText('message_content')->comment('messagecontent');
            $table->index(['delightful_message_id', 'version_id'], 'idx_delightful_message_id_version_id');
            $table->timestamps();
            $table->comment('messageversiontable,recordmessageversioninformation');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_chat_message_versions');
    }
};
