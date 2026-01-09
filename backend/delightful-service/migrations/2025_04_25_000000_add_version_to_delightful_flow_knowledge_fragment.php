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
        // 修改表结构，添加新field
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            // check是否已存在field，避免重复添加
            if (! Schema::hasColumn('delightful_flow_knowledge_fragment', 'version')) {
                $table->unsignedInteger('version')->default(1)->comment('version number');
            }
        });

        // delete重复的索引
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            if (Schema::hasIndex('delightful_flow_knowledge_fragment', 'knowledge_base_fragments_document_code_index')) {
                $table->dropIndex('knowledge_base_fragments_document_code_index');
            }
        });

        // 添加new复合索引
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            // check是否已存在索引，避免重复添加
            if (! Schema::hasIndex('delightful_flow_knowledge_fragment', 'idx_knowledge_document_version')) {
                $table->index(['knowledge_code', 'document_code', 'version'], 'idx_knowledge_document_version');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            // delete新添加的索引
            if (Schema::hasIndex('delightful_flow_knowledge_fragment', 'idx_knowledge_document_version')) {
                $table->dropIndex('idx_knowledge_document_version');
            }

            // restore原有的索引
            if (! Schema::hasIndex('delightful_flow_knowledge_fragment', 'knowledge_base_fragments_document_code_index')) {
                $table->index(['document_code'], 'knowledge_base_fragments_document_code_index');
            }

            // check是否已存在field，避免重复delete
            if (Schema::hasColumn('delightful_flow_knowledge_fragment', 'version')) {
                $table->dropColumn('version');
            }
        });
    }
};
