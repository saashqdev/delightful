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
        Schema::create('knowledge_base_parent_fragments', function (Blueprint $table) {
            // primary key
            $table->bigIncrements('id');

            // yuandata
            $table->string('knowledge_base_code', 255);
            $table->string('knowledge_base_document_code', 255)->comment('associateknowledge basedocumentcode');
            $table->string('organization_code')->comment('organizationencoding');

            // 操asrecord
            $table->string('created_uid', 255)->comment('create者ID');
            $table->string('updated_uid', 255)->comment('update者ID');

            // statustimepoint
            $table->datetimes();
            $table->softDeletes();

            $table->index(['knowledge_base_code', 'knowledge_base_document_code'], 'index_knowledge_base_code_document_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_parent_fragments');
    }
};
