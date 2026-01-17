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
        Schema::table('delightful_be_agent_workspace_versions', function (Blueprint $table) {
            // 为 id 字段添加唯一索引
            $table->unique('id', 'idx_unique_id');

            // 单列索引
            $table->index('topic_id', 'idx_topic_id');

            // 复合索引：覆盖多种查询场景（project_id, folder, commit_hash）
            $table->index(['project_id', 'folder', 'commit_hash'], 'idx_project_folder_commit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_be_agent_workspace_versions', function (Blueprint $table) {
            // 删除复合索引
            $table->dropIndex('idx_project_folder_commit');

            // 删除单列索引
            $table->dropIndex('idx_topic_id');

            // 删除唯一索引
            $table->dropUnique('idx_unique_id');
        });
    }
};
