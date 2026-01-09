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
        Schema::table('delightful_chat_sequences', function (Blueprint $table) {
            // 检查deleted_at字段是否存在，if不存在则添加软delete字段
            if (! Schema::hasColumn('delightful_chat_sequences', 'deleted_at')) {
                $table->softDeletes()->comment('软deletion time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_chat_sequences', function (Blueprint $table) {
            // 回滚时deletedeleted_at字段（仅在字段存在时）
            if (Schema::hasColumn('delightful_chat_sequences', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
