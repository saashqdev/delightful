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
        // modify表结构，add新field
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            // checkwhether已存infield，避免重复add
            if (! Schema::hasColumn('delightful_flow_knowledge_fragment', 'document_code')) {
                $table->string('document_code', 255)->default('')->comment('associatedocumentcode')->index();
            }

            if (! Schema::hasColumn('delightful_flow_knowledge_fragment', 'word_count')) {
                $table->unsignedBigInteger('word_count')->default(0)->comment('word countstatistics');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 移exceptadd的field
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            if (Schema::hasColumn('delightful_flow_knowledge_fragment', 'document_code')) {
                $table->dropColumn('document_code');
            }

            if (Schema::hasColumn('delightful_flow_knowledge_fragment', 'word_count')) {
                $table->dropColumn('word_count');
            }
        });

        // restore表名
        Schema::rename('delightful_flow_knowledge_fragment', 'delightful_flow_knowledge_fragment');
    }
};
