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
            // checkdeleted_atfieldwhether存in，ifnot存inthen添加软deletefield
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
            // 回滚时deletedeleted_atfield（仅infield存in时）
            if (Schema::hasColumn('delightful_chat_sequences', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
