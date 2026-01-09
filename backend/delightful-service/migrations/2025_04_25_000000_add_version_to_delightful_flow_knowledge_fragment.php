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
        // modifytablestructure,addnewfield
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            // checkwhetheralready存infield,avoidduplicateadd
            if (! Schema::hasColumn('delightful_flow_knowledge_fragment', 'version')) {
                $table->unsignedInteger('version')->default(1)->comment('version number');
            }
        });

        // deleteduplicateindex
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            if (Schema::hasIndex('delightful_flow_knowledge_fragment', 'knowledge_base_fragments_document_code_index')) {
                $table->dropIndex('knowledge_base_fragments_document_code_index');
            }
        });

        // addnew复合index
        Schema::table('delightful_flow_knowledge_fragment', function (Blueprint $table) {
            // checkwhetheralready存inindex,avoidduplicateadd
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
            // deletenewaddindex
            if (Schema::hasIndex('delightful_flow_knowledge_fragment', 'idx_knowledge_document_version')) {
                $table->dropIndex('idx_knowledge_document_version');
            }

            // restore原haveindex
            if (! Schema::hasIndex('delightful_flow_knowledge_fragment', 'knowledge_base_fragments_document_code_index')) {
                $table->index(['document_code'], 'knowledge_base_fragments_document_code_index');
            }

            // checkwhetheralready存infield,avoidduplicatedelete
            if (Schema::hasColumn('delightful_flow_knowledge_fragment', 'version')) {
                $table->dropColumn('version');
            }
        });
    }
};
