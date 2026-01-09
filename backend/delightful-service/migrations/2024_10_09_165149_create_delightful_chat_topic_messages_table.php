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
        // topic相closemessagetable
        // topiccontain message_id list. notinseqtableaddtopicidfield,avoidseq承载featuretoo多,needaddtoo多index
        Schema::create('delightful_chat_topic_messages', static function (Blueprint $table) {
            // messageid
            $table->bigIncrements('seq_id')->comment('message序columnid.notinseqtableaddtopicidfield,avoidseq承载featuretoo多,needaddtoo多index');
            // sessionid. 冗remainderfield
            $table->string('conversation_id', 64)->comment('messagebelong tosessionid');
            // organizationencoding. 冗remainderfield
            $table->string('organization_code', 64)->comment('organizationencoding');
            // topicid
            $table->unsignedBigInteger('topic_id')->comment('messagebelong totopicid');
            # index
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
