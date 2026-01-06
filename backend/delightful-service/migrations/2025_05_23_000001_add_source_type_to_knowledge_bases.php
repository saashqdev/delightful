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
        Schema::table('delightful_flow_knowledge', function (Blueprint $table) {
            // 检查是否已存在字段，避免重复添加
            if (! Schema::hasColumn('delightful_flow_knowledge', 'source_type')) {
                $table->integer('source_type')->nullable()->comment('数据源类型');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_flow_knowledge', function (Blueprint $table) {
            // 检查是否已存在字段，避免重复删除
            if (Schema::hasColumn('delightful_flow_knowledge', 'source_type')) {
                $table->dropColumn('source_type');
            }
        });
    }
};
