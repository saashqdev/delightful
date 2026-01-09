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
        if (Schema::hasTable('delightful_chat_topics')) {
            return;
        }
        // 话题表
        Schema::create('delightful_chat_topics', static function (Blueprint $table) {
            $table->bigIncrements('id');
            // 话题 id
            $table->string('topic_id', 64)->comment('话题 id. differentconversation窗口中,话题id一致');
            // 话题名称
            $table->string('name', 50)->comment('话题名称');
            // 话题description
            $table->text('description')->comment('话题description');
            // 所属conversationID
            $table->bigInteger('conversation_id')->comment('所属conversationID');
            // organization编码
            $table->string('organization_code', 64)->comment('organization编码');
            // 话题来源
            $table->string('source_id', 64)->default('')->comment('话题的来源。 such as甲create了one话题，乙的话题id就来自于甲。need同update。');
            # 索引
            $table->index(['conversation_id'], 'idx_conversation_id');
            $table->index(['topic_id'], 'idx_topic_id');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('话题表');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_chat_topics');
    }
};
