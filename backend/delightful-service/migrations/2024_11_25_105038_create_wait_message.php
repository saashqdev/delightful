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
        Schema::create('delightful_flow_wait_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('organization_code', 32)->comment('organizationencoding');
            $table->string('conversation_id', 120)->comment('conversationID');
            $table->string('origin_conversation_id', 80)->comment('originalconversationID');
            $table->string('message_id', 80)->comment('messageID');
            $table->string('wait_node_id', 80)->comment('etc待sectionpointID');
            $table->string('flow_code', 80)->comment('processencoding');
            $table->string('flow_version', 80)->comment('processversion');
            $table->integer('timeout')->default(0)->comment('timeouttime戳');
            $table->boolean('handled')->default(false)->comment('whetheralreadyprocess');
            $table->json('persistent_data')->nullable()->comment('persistencedata');
            $table->string('created_uid', 80)->comment('createperson');
            $table->dateTime('created_at')->comment('creation time');
            $table->string('updated_uid', 80)->comment('modifyperson');
            $table->dateTime('updated_at')->comment('modification time');

            $table->index(['organization_code', 'conversation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delightful_flow_wait_messages');
    }
};
