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
        // 修改表结构，添加新字段
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            // 检查是否已存在字段，避免重复添加
            if (! Schema::hasColumn('delightful_flow_knowledge_fragment', 'parent_fragment_id')) {
                $table->unsignedBigInteger('parent_fragment_id')->nullable()->comment('父片段id')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            // 检查是否已存在字段，避免重复delete
            if (Schema::hasColumn('delightful_flow_knowledge_fragment', 'parent_fragment_id')) {
                $table->dropColumn('parent_fragment_id');
            }
        });
    }
};
