<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddDocumentFileToKnowledgeBaseDocuments extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('knowledge_base_documents', function (Blueprint $table) {
            $table->json('document_file')->nullable()->comment('document文件information');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_base_documents', function (Blueprint $table) {
            $table->dropColumn('document_file');
        });
    }
}
