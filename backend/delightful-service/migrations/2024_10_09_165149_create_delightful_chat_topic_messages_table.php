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
        if (Schema::hasTable('delightful_chat_topic_messages')) {
            return;
        }
        // 话题的相关messagetable
        // 话题contain的 message_id list. 不在seqtable加话题idfield,避免seq承载的功能太多,need加太多索引
        Schema::create('delightful_chat_topic_messages', static function (Blueprint $table) {
            // messageid
            $table->bigIncrements('seq_id')->comment('message的序列id.不在seqtable加话题idfield,避免seq承载的功能太多,need加太多索引');
            // sessionid. 冗余field
            $table->string('conversation_id', 64)->comment('message所属sessionid');
            // organization编码. 冗余field
            $table->string('organization_code', 64)->comment('organization编码');
            // 话题id
            $table->unsignedBigInteger('topic_id')->comment('message所属话题id');
            # 索引
            $table->index(['conversation_id', 'topic_id'], 'idx_conversation_topic_id');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_chat_topic_messages');
    }
};
