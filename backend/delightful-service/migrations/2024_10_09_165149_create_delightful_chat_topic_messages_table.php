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
        // 话题相closemessagetable
        // 话题contain message_id list. notinseqtableadd话题idfield,avoidseq承载featuretoo多,needaddtoo多index
        Schema::create('delightful_chat_topic_messages', static function (Blueprint $table) {
            // messageid
            $table->bigIncrements('seq_id')->comment('message序columnid.notinseqtableadd话题idfield,avoidseq承载featuretoo多,needaddtoo多index');
            // sessionid. 冗remainderfield
            $table->string('conversation_id', 64)->comment('message所属sessionid');
            // organizationencoding. 冗remainderfield
            $table->string('organization_code', 64)->comment('organizationencoding');
            // 话题id
            $table->unsignedBigInteger('topic_id')->comment('message所属话题id');
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
