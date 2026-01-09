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
        // topicrelatedclosemessagetable
        // topiccontain message_id list. notinseqtableaddtopicidfield,avoidseqcarryfeaturetoomultiple,needaddtoomultipleindex
        Schema::create('delightful_chat_topic_messages', static function (Blueprint $table) {
            // messageid
            $table->bigIncrements('seq_id')->comment('messagesequencecolumnid.notinseqtableaddtopicidfield,avoidseqcarryfeaturetoomultiple,needaddtoomultipleindex');
            // sessionid. redundantremainderfield
            $table->string('conversation_id', 64)->comment('messagebelong tosessionid');
            // organizationencoding. redundantremainderfield
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
