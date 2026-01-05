<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMagicFlowAIModels extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('magic_flow_ai_models', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code')->default('')->comment('Organization code');
            $table->string('name', 100)->default('')->comment('Model name');
            $table->string('label', 100)->default('')->comment('Display label');
            $table->string('model_name', 100)->default('')->comment('Model identifier');
            $table->json('tags')->nullable()->comment('Tags');
            $table->json('default_configs')->nullable()->comment('Default model configs');
            $table->boolean('enabled')->default(1)->comment('Enabled');
            $table->string('implementation', 100)->default('')->comment('Implementation class');
            $table->text('implementation_config')->nullable()->comment('Implementation config');
            $table->boolean('support_embedding')->default(false)->comment('Supports embeddings');
            $table->bigInteger('vector_size')->default(0)->comment('Vector size');

            $table->string('created_uid')->default('')->comment('Creator user ID');
            $table->timestamp('created_at')->nullable()->comment('Created at');
            $table->string('updated_uid')->default('')->comment('Updater user ID');
            $table->timestamp('updated_at')->nullable()->comment('Updated at');
            $table->timestamp('deleted_at')->nullable()->comment('Deleted at');

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_flow_ai_models');
    }
}
