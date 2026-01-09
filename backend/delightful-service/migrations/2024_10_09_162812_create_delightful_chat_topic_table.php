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
        // topictable
        Schema::create('delightful_chat_topics', static function (Blueprint $table) {
            $table->bigIncrements('id');
            // topic id
            $table->string('topic_id', 64)->comment('topic id. differentconversationwindowmiddle,topicidone致');
            // topicname
            $table->string('name', 50)->comment('topicname');
            // topicdescription
            $table->text('description')->comment('topicdescription');
            // belong toconversationID
            $table->bigInteger('conversation_id')->comment('belong toconversationID');
            // organizationencoding
            $table->string('organization_code', 64)->comment('organizationencoding');
            // topiccome源
            $table->string('source_id', 64)->default('')->comment('topiccome源. such as甲createonetopic,乙topicidthencomefromat甲.need同update.');
            # index
            $table->index(['conversation_id'], 'idx_conversation_id');
            $table->index(['topic_id'], 'idx_topic_id');
            $table->timestamps();
            $table->softDeletes();
            $table->comment('topictable');
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
