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
        if (Schema::hasTable('magic_super_agent_topics')) {
            return;
        }
        Schema::create('magic_super_agent_topics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 64)->default('')->comment('user ID');
            $table->unsignedBigInteger('workspace_id')->default(0)->comment('workspace ID');
            $table->string('chat_conversation_id', 64)->default('')->comment('chat conversation ID');
            $table->string('chat_topic_id', 64)->default('')->comment('chat topic ID');
            $table->string('sandbox_id', 64)->default('')->comment('sandboxid');
            $table->string('current_task_id', 64)->default('')->comment('current task ID');
            $table->string('current_task_status', 64)->default('')->comment('current task status: waiting, running, finished, error');
            $table->string('topic_name', 64)->default('')->comment('topic name');
            $table->string('work_dir', 255)->default('')->comment('workspace directory');
            $table->string('created_uid', 64)->default('')->comment('creator user ID');
            $table->string('updated_uid', 64)->default('')->comment('updater user ID');
            $table->datetimes();
            $table->softDeletes()->comment('deleted time');

            // create index
            $table->index(['user_id', 'workspace_id'], 'idx_user_workspace');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_general_agent_topics');
    }
};
