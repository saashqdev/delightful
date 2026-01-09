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
        Schema::create('knowledge_base_documents', function (Blueprint $table) {
            // primary key
            $table->bigIncrements('id');

            // associatefield
            $table->string('knowledge_base_code', 255)->comment('associateknowledge basecode')->index();

            // document元data
            $table->string('name', 255)->comment('documentname');
            $table->string('description', 255)->comment('description');
            $table->string('code', 255)->comment('documentcode');
            $table->unsignedInteger('version')->default(1)->comment('version');
            $table->boolean('enabled')->default(true)->comment('1 enable 0 disable');
            $table->unsignedInteger('doc_type')->comment('documenttype');
            $table->json('doc_metadata')->nullable()->comment('document元data');
            $table->tinyInteger('sync_status')->default(0)->comment('syncstatus');
            $table->tinyInteger('sync_times')->default(0)->comment('sync次数');
            $table->string('sync_status_message', 1000)->default('')->comment('syncstatusmessage');
            $table->string('organization_code')->comment('organizationencoding');
            $table->unsignedBigInteger('word_count')->default(0)->comment('字数statistics');

            // configurationinfo
            $table->string('embedding_model', 255)->comment('嵌入model');
            $table->string('vector_db', 255)->comment('向量database');
            $table->json('retrieve_config')->nullable()->comment('检索configuration');
            $table->json('fragment_config')->nullable()->comment('分段configuration');
            $table->json('embedding_config')->nullable()->comment('嵌入configuration');
            $table->json('vector_db_config')->nullable()->comment('向量databaseconfiguration');

            // 操作record
            $table->string('created_uid', 255)->comment('create者ID');
            $table->string('updated_uid', 255)->comment('update者ID');

            // statustime点
            $table->datetimes();
            $table->softDeletes();

            $table->unique(['code', 'version'], 'unique_code_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_documents');
    }
};
