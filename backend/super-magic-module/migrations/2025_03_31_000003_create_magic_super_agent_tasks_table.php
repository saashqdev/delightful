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
        if (Schema::hasTable('magic_super_agent_task')) {
            return;
        }
        Schema::create('magic_super_agent_task', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('user_id', 64)->comment('user ID。');
            $table->unsignedBigInteger('workspace_id')->comment('workspace ID');
            $table->unsignedBigInteger('topic_id')->comment('topic ID');
            $table->string('task_id', 64)->comment('task ID returned by sandbox service');
            $table->string('sandbox_id', 64)->comment('sandboxid。');
            $table->string('prompt', 5000)->comment('user's question');
            $table->string('attachments', 500)->comment('user uploaded attachment information stored in JSON format');
            $table->string('task_status', 64)->comment('task status waiting, running，finished，error');
            $table->string('work_dir', 255)->comment('workspace directory');
            $table->timestamps();
            $table->softDeletes();

            // create index
            $table->index(['user_id', 'workspace_id'], 'idx_user_workspace');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_general_agent_task');
    }
};
