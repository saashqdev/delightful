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
        // 修改table结构，添加新field
        Schema::table('delightful_flow_knowledge', function (Blueprint $table) {
            $table->unsignedBigInteger('word_count')->default(0)->comment('字数statistics');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('delightful_flow_knowledge', 'delightful_flow_knowledge');
        Schema::table('delightful_flow_knowledge', function (Blueprint $table) {
            $table->dropColumn('word_count');
        });
    }
};
