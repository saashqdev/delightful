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
            $table->string('topic_id', 64)->comment('话题 id. differentconversationwindowmiddle,话题idone致');
            // 话题name
            $table->string('name', 50)->comment('话题name');
            // 话题description
            $table->text('description')->comment('话题description');
            // 所属conversationID
            $table->bigInteger('conversation_id')->comment('所属conversationID');
            // organizationencoding
            $table->string('organization_code', 64)->comment('organizationencoding');
            // 话题come源
            $table->string('source_id', 64)->default('')->comment('话题come源. such as甲createone话题,乙话题idthencomefromat甲.need同update.');
            # index
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
