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
        if (Schema::hasTable('delightful_super_agent_workspaces')) {
            return;
        }
        Schema::create('delightful_super_agent_workspaces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 64)->comment('user ID');
            $table->string('user_organization_code', 64)->comment('user organization code');
            $table->string('chat_conversation_id', 64)->comment('chat conversation ID');
            $table->string('name', 64)->comment('workspace name');
            $table->tinyInteger('is_archived')->default(0)->comment('whether archived 0no 1yes');
            $table->string('created_uid', 64)->default('')->comment('creator user ID');
            $table->string('updated_uid', 64)->default('')->comment('updater user ID');
            $table->datetimes();
            $table->softDeletes();
            $table->unsignedBigInteger('current_topic_id')->nullable()->comment('current topic ID');
            $table->tinyInteger('status')->default(0)->comment('status 0:normal 1:not displayed 2：deleted');
            $table->index(['user_id'], 'idx_user_id');
            $table->index(['chat_conversation_id'], 'idx_chat_conversation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_general_agent_workspaces');
    }
};
