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
        Schema::table('delightful_chat_message_versions', function (Blueprint $table) {
            // message_type field，if没有则添加
            if (! Schema::hasColumn('delightful_chat_message_versions', 'message_type')) {
                $table->string('message_type', 64)->nullable()->comment('message类型');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
