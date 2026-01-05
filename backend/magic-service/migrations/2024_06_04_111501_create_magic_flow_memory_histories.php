<?php

declare(strict_types=1);
/**
 * Copyright (c) Be Delightful , Distributed under the MIT software license
 */
use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateMagicFlowMemoryHistories extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('magic_flow_memory_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('conversation_id')->default('')->comment('Conversation ID');
            $table->string('request_id')->default('')->comment('Request ID');
            $table->tinyInteger('type')->default(0)->comment('Type (1 = LLM)');
            $table->string('role', 80)->default('')->comment('Role');
            $table->json('content')->nullable()->comment('Content');
            $table->string('created_uid')->default('')->comment('Creator user ID');
            $table->timestamp('created_at')->nullable()->comment('Created at');

            $table->index('conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_flow_memory_histories');
    }
}
