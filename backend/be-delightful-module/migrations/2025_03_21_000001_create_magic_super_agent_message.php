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
        if (Schema::hasTable('magic_super_agent_message')) {
            return;
        }
        Schema::create('magic_super_agent_message', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sender_type', 32)->comment('sender type(user/ai)');
            $table->string('sender_uid', 64)->comment('sender ID');
            $table->string('receiver_uid', 64)->comment('receiver ID');
            $table->string('message_id', 64)->unique()->comment('message ID');
            $table->string('type', 32)->comment('message type');
            $table->string('task_id', 64)->comment('task ID');
            $table->bigInteger('topic_id')->nullable()->comment('topic ID');
            $table->string('status', 32)->nullable()->comment('task status');
            $table->text('content')->comment('message content');
            $table->json('steps')->nullable()->comment('step information');
            $table->json('tool')->nullable()->comment('tool call information');
            $table->string('event', 64)->comment('event type');
            $table->integer('send_timestamp')->comment('send timestamp');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['task_id', 'type'], 'idx_task_type');
            $table->index(['sender_uid', 'created_at'], 'idx_sender_created');
            $table->index(['receiver_uid', 'created_at'], 'idx_receiver_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_general_agent_message');
    }
};
