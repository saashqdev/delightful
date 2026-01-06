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
     * 优化 delightful_bots 和 delightful_bot_versions 表的多个查询场景性能
     * 添加多个复合索引支持不同的查询模式.
     */
    public function up(): void
    {
        // 优化 delightful_bots 表
        Schema::table('delightful_bots', function (Blueprint $table) {
            // 1. 优化 chat-mode/available 接口的 JOIN 查询
            // 对应SQL: delightful_bots.bot_version_id = delightful_bot_versions.id AND delightful_bots.status = '7'
            $table->index(['bot_version_id', 'status'], 'idx_bot_version_status');

            // 2. 优化企业助理查询 (queriesAgents方法)
            // 对应SQL: WHERE organization_code = ? AND status = ?
            $table->index(['organization_code', 'status'], 'idx_organization_status');
        });

        // 优化 delightful_bot_versions 表
        Schema::table('delightful_bot_versions', function (Blueprint $table) {
            // 先删除已存在的单字段索引，避免索引冗余
            if (Schema::hasIndex('delightful_bot_versions', 'delightful_bot_versions_organization_code_index')) {
                $table->dropIndex('delightful_bot_versions_organization_code_index');
            }

            // 3. 优化企业发布状态查询
            // 对应SQL: WHERE organization_code = ? AND enterprise_release_status = ?
            $table->index(['organization_code', 'enterprise_release_status'], 'idx_organization_enterprise_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
