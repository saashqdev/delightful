<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateDelightfulFlowKnowledge extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('magic_flow_knowledge')) {
            Schema::create('magic_flow_knowledge', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code')->default('')->unique()->comment('Unique knowledge base code');
                $table->integer('version')->default(1)->comment('Version');
                $table->string('name')->default('')->comment('Knowledge base name');
                $table->string('description')->default('')->comment('Knowledge base description');
                $table->tinyInteger('type')->default(1)->comment('Knowledge base type 1 self-built text 2 Delightful knowledge base cloud document');
                $table->boolean('enabled')->default(1)->comment('1 enabled 0 disabled');
                $table->tinyInteger('sync_status')->default(0)->comment('Sync status 0 not synced 1 success 2 failed');
                $table->tinyInteger('sync_times')->default(0)->comment('Sync attempts');
                $table->string('sync_status_message', 1000)->default('')->comment('Sync status message');
                $table->string('model')->default('')->comment('Embedding model');
                $table->string('vector_db')->default('')->comment('Vector database');

                $table->string('organization_code')->default('')->comment('Organization code');
                $table->string('created_uid')->default('')->comment('Created by user ID');
                $table->timestamp('created_at')->nullable()->comment('Created at');
                $table->string('updated_uid')->default('')->comment('Updated by user ID');
                $table->timestamp('updated_at')->nullable()->comment('Updated at');
                $table->timestamp('deleted_at')->nullable()->comment('Deleted at');
            });
        }

        if (! Schema::hasTable('magic_flow_knowledge_fragment')) {
            Schema::create('magic_flow_knowledge_fragment', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('knowledge_code')->default('')->comment('Associated knowledge base code');
                $table->text('content')->nullable(false)->comment('Content fragment');
                $table->json('metadata')->nullable()->comment('Custom metadata');
                $table->string('business_id')->default('')->comment('Business ID');
                $table->tinyInteger('sync_status')->default(0)->comment('Sync status 0 not synced 1 success 2 failed');
                $table->tinyInteger('sync_times')->default(0)->comment('Sync attempts');
                $table->string('sync_status_message', 1000)->default('')->comment('Sync status message');
                $table->string('point_id')->default('')->comment('Fragment ID');
                $table->text('vector')->nullable()->comment('Vector value');

                $table->string('created_uid')->default('')->comment('Created by user ID');
                $table->timestamp('created_at')->nullable()->comment('Created at');
                $table->string('updated_uid')->default('')->comment('Updated by user ID');
                $table->timestamp('updated_at')->nullable()->comment('Updated at');
                $table->timestamp('deleted_at')->nullable()->comment('Deleted at');
            });
        }

        // TODO indexes
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
}
